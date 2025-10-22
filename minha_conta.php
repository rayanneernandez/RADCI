<?php
session_start();
// ========================================
// Página: Minha Conta
// Descrição: Perfil do usuário com informações pessoais,
// preferências de notificação e logout.
// ========================================

// Simulação de dados de usuário
$usuario = [
  "nome" => "Usuário RADCI",
  "email" => "usuario@email.com",
  "cidade" => "Rio de Janeiro - RJ",
  "membro_desde" => "2025"
];

// Função simples de logout
if (isset($_POST['logout'])) {
  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minha Conta | RADCI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/lucide@latest"></script>
  <style>
    :root {
      --background: 0 0% 100%;
      --foreground: 222.2 47.4% 11.2%;
      --muted: 210 40% 96%;
      --muted-foreground: 215 20.2% 65.1%;
      --card: 0 0% 100%;
      --border: 214.3 31.8% 91.4%;
      --input: 214.3 31.8% 91.4%;
      --primary: 142 71% 45%;
      --primary-light: 142 76% 55%;
      --primary-foreground: 0 0% 100%;
      --destructive: 0 84.2% 60.2%;
      --destructive-foreground: 0 0% 100%;
    }
  </style>
</head>
<body class="min-h-screen bg-white pb-28 md:pb-8 text-[hsl(var(--foreground))]">

  <!-- =========================
       Cabeçalho Principal
  ========================== -->
  <header class="bg-[hsl(var(--card))] border-b border-[hsl(var(--border))] sticky top-0 z-10 shadow-sm">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <div class="bg-[hsl(var(--primary))] p-2 rounded-lg">
          <i data-lucide="map-pin" class="w-6 h-6 text-[hsl(var(--primary-foreground))]"></i>
        </div>
        <div>
          <h1 class="text-xl font-bold text-[hsl(var(--foreground))]">RADCI</h1>
          <p class="text-xs text-[hsl(var(--muted-foreground))]">Minha Conta</p>
        </div>
      </div>

      <!-- Botão Sair (Desktop) -->
      <form method="POST" class="hidden md:flex">
        <button type="submit" name="logout" class="flex items-center px-3 py-2 rounded text-sm text-[hsl(var(--foreground))] hover:bg-[hsl(var(--muted))] transition">
          <i data-lucide="log-out" class="w-4 h-4 mr-2"></i>
          Sair
        </button>
      </form>
    </div>
  </header>

  <!-- =========================
       Conteúdo Principal
  ========================== -->
  <main class="container mx-auto px-4 py-8 max-w-2xl">
  <!-- Voltar ao Dashboard (Mobile) -->
  <div class="md:hidden mb-4">
    <button onclick="location.href='dashboard.php'" class="w-full bg-green-600 text-white py-2 rounded-md flex items-center justify-center">
      <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
      Voltar ao Dashboard
    </button>
  </div>

    <!-- Cabeçalho do Perfil -->
    <div class="flex items-center space-x-4 mb-8">
      <div class="w-20 h-20 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center text-[hsl(var(--primary-foreground))]">
        <i data-lucide="user" class="w-10 h-10"></i>
      </div>
      <div>
        <h2 class="text-2xl font-bold text-[hsl(var(--foreground))]">Olá, <?= htmlspecialchars($usuario["nome"]); ?></h2>
        <p class="text-[hsl(var(--muted-foreground))]">Membro desde <?= htmlspecialchars($usuario["membro_desde"]); ?></p>
      </div>
    </div>

    <!-- Cartão: Informações da Conta -->
    <section class="bg-[hsl(var(--card))] rounded-2xl shadow-md mb-6 p-6 space-y-4 border border-[hsl(var(--border))]">
      <h3 class="text-lg font-semibold">Informações da Conta</h3>
      <p class="text-sm text-[hsl(var(--muted-foreground))] mb-4">Gerencie seus dados pessoais</p>

      <form method="POST" action="#">
        <div class="space-y-2">
          <label for="nome" class="block text-sm font-medium">Nome Completo</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario["nome"]); ?>" class="w-full border border-[hsl(var(--input))] rounded p-2 bg-[hsl(var(--background))]">
        </div>

        <div class="space-y-2">
          <label for="email" class="block text-sm font-medium">E-mail</label>
          <div class="flex items-center gap-2">
            <i data-lucide="mail" class="w-4 h-4 text-[hsl(var(--muted-foreground))]"></i>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario["email"]); ?>" class="w-full border border-[hsl(var(--input))] rounded p-2 bg-[hsl(var(--background))]">
          </div>
        </div>

        <div class="space-y-2">
          <label for="cidade" class="block text-sm font-medium">Cidade</label>
          <div class="flex items-center gap-2">
            <i data-lucide="map-pin" class="w-4 h-4 text-[hsl(var(--muted-foreground))]"></i>
            <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($usuario["cidade"]); ?>" class="w-full border border-[hsl(var(--input))] rounded p-2 bg-[hsl(var(--background))]">
          </div>
        </div>

        <button type="submit" class="w-full mt-4 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] py-2 rounded-lg hover:bg-[hsl(var(--primary-light))] transition">
          Salvar Alterações
        </button>
      </form>
    </section>

    <!-- Cartão: Notificações -->
    <section class="bg-[hsl(var(--card))] rounded-2xl shadow-md mb-6 p-6 border border-[hsl(var(--border))] space-y-4">
      <h3 class="text-lg font-semibold">Notificações</h3>
      <p class="text-sm text-[hsl(var(--muted-foreground))] mb-4">Configure suas preferências de notificação</p>

      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i data-lucide="bell" class="w-4 h-4 text-[hsl(var(--muted-foreground))]"></i>
          <span class="text-sm">Atualizações de ocorrências</span>
        </div>
        <input type="checkbox" checked class="w-4 h-4 accent-[hsl(var(--primary))]">
      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i data-lucide="bell" class="w-4 h-4 text-[hsl(var(--muted-foreground))]"></i>
          <span class="text-sm">Novidades da plataforma</span>
        </div>
        <input type="checkbox" checked class="w-4 h-4 accent-[hsl(var(--primary))]">
      </div>
    </section>

    <!-- Botão Sair (Mobile) -->
    <form method="POST" class="md:hidden">
      <button type="submit" name="logout" class="w-full bg-[hsl(var(--destructive))] text-[hsl(var(--destructive-foreground))] py-2 rounded-lg hover:opacity-90 transition flex items-center justify-center">
        <i data-lucide="log-out" class="w-4 h-4 mr-2"></i>
        Sair da Conta
      </button>
    </form>
  </main>

  <!-- Navegação Mobile -->
  <footer class="fixed bottom-0 left-0 w-full bg-[hsl(var(--card))] border-t border-[hsl(var(--border))] md:hidden">
    <nav class="flex justify-around py-2">
      <?php include __DIR__ . '/mobile_nav.php'; ?>
    </nav>
  </footer>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
      }
    });
  </script>
</body>
</html>
