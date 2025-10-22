<?php
session_start();
$primeiroNome = isset($_SESSION['usuario_nome']) ? explode(" ", trim($_SESSION['usuario_nome']))[0] : "Usuário";

$categories = [
    ['id'=>'saude','name'=>'Saúde','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
    ['id'=>'inovacao','name'=>'Inovação','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3h2v5h-2V3zM4 12h5M15 12h5M6.343 17.657l1.414-1.414M17.657 6.343l-1.414 1.414"/></svg>'],
    ['id'=>'mobilidade','name'=>'Mobilidade','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6a4 4 0 014-4h4v10h-4a4 4 0 01-4-4z"/></svg>'],
    ['id'=>'politicas','name'=>'Políticas Públicas','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>'],
    ['id'=>'riscos','name'=>'Riscos Urbanos','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>'],
    ['id'=>'sustentabilidade','name'=>'Sustentabilidade','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2a10 10 0 00-7.5 17.2L12 22l7.5-2.8A10 10 0 0012 2z"/></svg>'],
    ['id'=>'planejamento','name'=>'Planejamento Urbano','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>'],
    ['id'=>'educacao','name'=>'Educação','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zM12 14v7"/></svg>'],
    ['id'=>'meio','name'=>'Meio Ambiente','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l4 10H8l4-10z"/></svg>'],
    ['id'=>'infraestrutura','name'=>'Infraestrutura da Cidade','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M3 6h18M3 18h18"/></svg>'],
    ['id'=>'seguranca','name'=>'Segurança Pública','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l7 7-7 7-7-7 7-7z"/></svg>'],
    ['id'=>'energias','name'=>'Energias Inteligentes','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>'],
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>RADCI - Prioridades</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .hide-scrollbar::-webkit-scrollbar { display: none; }
  .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  .category-card { transition: all 0.3s ease; }
  .category-card.dragging { opacity: 0.5; transform: scale(1.05); }
</style>
</head>
<body class="bg-muted/10 min-h-screen pb-28">

<header class="bg-white shadow sticky top-0 z-10">
  <div class="container mx-auto px-4 py-4 flex items-center gap-2">
    <button onclick="window.location.href='dashboard.php'" class="flex items-center text-sm font-medium text-gray-700 hover:text-green-600">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Voltar
    </button>
    <h1 class="text-xl font-bold text-gray-800 ml-4">Definir Prioridades</h1>
  </div>
</header>

<div class="container mx-auto px-4 py-8 max-w-full">
  <p class="text-gray-600 mb-6">Arraste as categorias para ordenar por ordem de importância</p>

  <div id="categoryList" class="flex flex-col md:flex-row flex-wrap gap-4 w-full justify-center">
    <?php foreach($categories as $index => $cat): ?>
    <div class="category-card flex items-center gap-4 p-4 bg-white shadow rounded-lg cursor-move hover:shadow-lg flex-1 md:basis-[calc(100%/12-0.5rem)]"
         draggable="true" data-index="<?= $index ?>">
      <div class="w-8 h-8 flex items-center justify-center bg-green-100 rounded-full font-bold text-green-600 position-num"><?= $index+1 ?></div>
      <div class="bg-green-50 rounded-full p-2"><?= $cat['icon'] ?></div>
      <span class="flex-1 font-medium text-gray-800"><?= htmlspecialchars($cat['name']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="flex gap-3 mt-8">
    <button onclick="window.location.href='dashboard.php'" class="flex-1 border border-gray-300 rounded-md py-3 hover:bg-gray-100 font-medium">Cancelar</button>
    <button id="saveBtn" class="flex-1 bg-green-500 text-white py-3 rounded-md hover:bg-green-600 font-medium">Salvar Prioridades</button>
  </div>
</div>

<script>
const categoryList = document.getElementById('categoryList');
let draggedItem = null;

function updateNumbers() {
  document.querySelectorAll('.category-card').forEach((card, idx) => {
    card.querySelector('.position-num').textContent = idx + 1;
  });
}

categoryList.addEventListener('dragstart', e => {
  draggedItem = e.target.closest('.category-card');
  draggedItem.classList.add('dragging');
});

categoryList.addEventListener('dragend', e => {
  draggedItem.classList.remove('dragging');
  draggedItem = null;
  updateNumbers();
});

categoryList.addEventListener('dragover', e => {
  e.preventDefault();
  const target = e.target.closest('.category-card');
  if(target && draggedItem && target !== draggedItem){
    const rect = target.getBoundingClientRect();
    const next = (window.innerWidth >= 768) ? (e.clientX - rect.left)/(rect.right - rect.left) > 0.5 : (e.clientY - rect.top)/(rect.bottom - rect.top) > 0.5;
    categoryList.insertBefore(draggedItem, next ? target.nextSibling : target);
  }
});

document.getElementById('saveBtn').addEventListener('click', () => {
  alert('Prioridades salvas com sucesso!');
  window.location.href = 'dashboard.php';
});

updateNumbers();
</script>

<?php 
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

// Ao finalizar a pesquisa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surveySid = isset($_GET['survey_id']) ? preg_replace('/[^a-zA-Z0-9._-]/','', $_GET['survey_id']) : 'priority';
    $userId    = intval($_SESSION['usuario_id'] ?? 0);

    // Resolve idPesquisa pelo sid (se existir)
    $pesqId = 0;
    try {
        $cols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM pesquisa")->fetchAll());
        if (in_array('sid', $cols)) {
            $stmt = $pdo->prepare("SELECT id FROM pesquisa WHERE sid = ? LIMIT 1");
            $stmt->execute([$surveySid]);
            $row = $stmt->fetch();
            if ($row && isset($row['id'])) $pesqId = intval($row['id']);
        }
    } catch (Throwable $_) {}

    $savedInDb = false;
    try {
        $valCols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM usuarios_validacoes")->fetchAll());
        if ($userId && in_array('idUsuario', $valCols) && in_array('idPesquisa', $valCols)) {
            // Verifica se já respondeu
            $stmtChk = $pdo->prepare("SELECT 1 FROM usuarios_validacoes WHERE idUsuario = ? AND idPesquisa = ? LIMIT 1");
            $stmtChk->execute([$userId, $pesqId]);
            if (!$stmtChk->fetch()) {
                // Insere resposta
                $sql = "INSERT INTO usuarios_validacoes (idUsuario, idPesquisa";
                $vals = [$userId, $pesqId];
                if (in_array('dataRegistro', $valCols)) {
                    $sql .= ", dataRegistro";
                }
                $sql .= ") VALUES (?, ?";
                if (in_array('dataRegistro', $valCols)) {
                    $sql .= ", NOW()";
                }
                $sql .= ")";
                $stmtIns = $pdo->prepare($sql);
                $stmtIns->execute($vals);
            }
            $savedInDb = true;
        }
    } catch (Throwable $_) {}

    // Fallback sessão para ocultar do Dashboard em qualquer cenário
    $_SESSION['answered_surveys'][$surveySid] = true;

    header('Location: dashboard.php?answered=' . urlencode($surveySid));
    exit;
}
 ?>
<?php include __DIR__ . '/mobile_nav.php'; ?>
</body>
</html>


