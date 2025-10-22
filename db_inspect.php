<?php
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
$tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);

// Gerar mapeamento completo para código PHP
$mapping = [];
foreach ($tables as $t) {
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
                           FROM INFORMATION_SCHEMA.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                           ORDER BY ORDINAL_POSITION");
    $stmt->execute([$t]);
    $mapping[$t] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Mapeamento Completo do Banco (RADCI)</title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 24px; }
    h1 { margin-bottom: 8px; }
    .info { background: #e3f2fd; padding: 12px; border-radius: 6px; margin: 16px 0; }
    .table { margin: 16px 0; }
    .name { font-weight: 600; color: #1976d2; }
    table { border-collapse: collapse; width: 100%; margin-top: 8px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 13px; }
    th { background: #f5f5f5; }
    .empty { color: #777; }
    .code-block { background: #f8f9fa; border: 1px solid #e9ecef; padding: 16px; border-radius: 6px; margin: 16px 0; }
    .code-block pre { margin: 0; font-family: 'Courier New', monospace; font-size: 12px; }
    .copy-btn { background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin-top: 8px; }
  </style>
</head>
<body>
  <h1>Mapeamento Completo - Banco: <?= htmlspecialchars($dbName) ?></h1>
  
  <div class="info">
    <strong>Instruções:</strong> Copie o mapeamento PHP abaixo e envie para o assistente para que ele adapte o código exatamente às suas tabelas e colunas.
  </div>

  <!-- Mapeamento PHP para copiar -->
  <div class="code-block">
    <strong>Mapeamento PHP das Tabelas:</strong>
    <pre><?php
echo "<?php\n";
echo "// Mapeamento automático do banco '$dbName'\n";
echo "\$db_schema = [\n";
foreach ($mapping as $table => $columns) {
    echo "    '$table' => [\n";
    foreach ($columns as $col) {
        $name = $col['COLUMN_NAME'];
        $type = $col['COLUMN_TYPE'];
        $nullable = $col['IS_NULLABLE'] === 'YES' ? 'true' : 'false';
        $key = $col['COLUMN_KEY'];
        $extra = $col['EXTRA'];
        echo "        '$name' => ['type' => '$type', 'nullable' => $nullable, 'key' => '$key', 'extra' => '$extra'],\n";
    }
    echo "    ],\n";
}
echo "];\n";
echo "?>";
    ?></pre>
    <button class="copy-btn" onclick="copyToClipboard(this.previousElementSibling.textContent)">Copiar Mapeamento</button>
  </div>

  <!-- Visualização das tabelas -->
  <?php if (!$tables): ?>
    <p class="empty">Nenhuma tabela encontrada.</p>
  <?php else: ?>
    <?php foreach ($tables as $t): ?>
      <div class="table">
        <div class="name">📋 Tabela: <?= htmlspecialchars($t) ?> (<?= count($mapping[$t]) ?> colunas)</div>
        <table>
          <thead>
            <tr>
              <th>Coluna</th><th>Tipo</th><th>Nulo</th><th>Default</th><th>Chave</th><th>Extra</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($mapping[$t] as $c): ?>
              <tr>
                <td><strong><?= htmlspecialchars($c['COLUMN_NAME']) ?></strong></td>
                <td><?= htmlspecialchars($c['COLUMN_TYPE']) ?></td>
                <td><?= htmlspecialchars($c['IS_NULLABLE']) ?></td>
                <td><?= htmlspecialchars($c['COLUMN_DEFAULT'] ?? 'NULL') ?></td>
                <td><?= htmlspecialchars($c['COLUMN_KEY']) ?></td>
                <td><?= htmlspecialchars($c['EXTRA']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <script>
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        alert('Mapeamento copiado! Cole no chat para o assistente.');
      });
    }
  </script>
</body>
</html>