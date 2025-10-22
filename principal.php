<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RADCI - Radar de Avaliações</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/lucide@latest"></script>
  <style>
    .gradient-hero {
      background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    }
    .gradient-card {
      background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
    }
    .text-primary-light {
      color: #bbf7d0;
    }
    .bg-primary {
      background-color: #16a34a;
    }
    .text-primary {
      color: #16a34a;
    }
    .animate-fade-in {
      animation: fadeIn 0.8s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body class="min-h-screen flex flex-col">

  <!-- Hero Section -->
  <section class="relative gradient-hero overflow-hidden py-20 lg:py-32 text-white">
    <div class="absolute inset-0 bg-black/10"></div>

    <div class="container relative mx-auto px-4">
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        <!-- Left Side -->
        <div class="space-y-8 animate-fade-in">
          <div class="flex items-center space-x-3 mb-6">
            <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
              <i data-lucide="map-pin" class="w-8 h-8 text-white"></i>
            </div>
            <div>
              <h1 class="text-4xl lg:text-5xl font-bold">RADCI</h1>
              <p class="text-white/90 text-sm lg:text-base font-medium mt-1">
                Radar de Avaliações dos Drivers de uma Cidade Mais Inteligente
              </p>
            </div>
          </div>

          <h2 class="text-3xl lg:text-5xl font-bold leading-tight">
            Transforme sua cidade com a força da 
            <span class="text-primary-light">comunidade</span>
          </h2>

          <p class="text-xl text-white/90 leading-relaxed">
            Relate problemas urbanos, sugira melhorias e acompanhe as mudanças em tempo real. 
            Juntos, construímos uma cidade mais inteligente e acessível para todos.
          </p>

          <div class="flex flex-col sm:flex-row gap-4">
            <a href="login_cadastro.php?tab=cadastro" class="text-lg inline-flex items-center justify-center bg-white text-green-700 font-medium px-6 py-3 rounded-lg hover:bg-gray-100 transition">
              Registre-se
              <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
            </a>
            <a href="login_cadastro.php?tab=login" class="text-lg inline-flex items-center justify-center bg-white/10 border border-white/30 text-white font-medium px-6 py-3 rounded-lg hover:bg-white/20 transition">
              Fazer Login
            </a>
          </div>
        </div>

        <!-- Right Side -->
        <div class="hidden lg:block">
          <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-green-300 to-green-500 rounded-3xl blur-3xl opacity-30 animate-pulse"></div>
            <div class="relative bg-white/10 backdrop-blur-md rounded-3xl p-8 border border-white/20">
              <div class="grid grid-cols-2 gap-4">
                <?php for ($i = 0; $i < 4; $i++): ?>
                  <div class="bg-white/20 rounded-xl p-4 backdrop-blur-sm">
                    <div class="h-20 bg-white/30 rounded-lg mb-2"></div>
                    <div class="h-3 bg-white/40 rounded w-3/4"></div>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="text-center mb-16 animate-fade-in">
        <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
          Como o RADCI funciona
        </h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          Uma plataforma completa para conectar cidadãos e gestores públicos, 
          facilitando a comunicação e acelerando soluções urbanas.
        </p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php
          $features = [
            ["icon" => "camera", "title" => "Relato Visual", "desc" => "Adicione fotos e vídeos para documentar problemas urbanos de forma clara e objetiva"],
            ["icon" => "map-pin", "title" => "Localização Precisa", "desc" => "Sistema de geolocalização para identificar exatamente onde está o problema"],
            ["icon" => "trending-up", "title" => "Acompanhamento", "desc" => "Monitore o progresso das suas ocorrências em tempo real"],
            ["icon" => "users", "title" => "Comunidade Ativa", "desc" => "Junte-se a milhares de cidadãos engajados em melhorar a cidade"]
          ];

          foreach ($features as $feature):
        ?>
          <div class="gradient-card rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-200 animate-fade-in">
            <div class="bg-green-100 w-12 h-12 rounded-xl flex items-center justify-center mb-4">
              <i data-lucide="<?= $feature['icon'] ?>" class="w-6 h-6 text-green-600"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">
              <?= $feature['title'] ?>
            </h3>
            <p class="text-gray-600 leading-relaxed">
              <?= $feature['desc'] ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-20 gradient-hero text-center text-white">
    <div class="container mx-auto px-4">
      <div class="max-w-3xl mx-auto space-y-8 animate-fade-in">
        <h2 class="text-3xl lg:text-4xl font-bold">
          Pronto para transformar sua cidade?
        </h2>
        <p class="text-xl text-white/90">
          Junte-se a milhares de cidadãos que já estão fazendo a diferença. 
          Cadastre-se gratuitamente e comece hoje mesmo!
        </p>
        <a href="login_cadastro.php?tab=cadastro" class="text-lg inline-flex items-center justify-center bg-white text-green-700 font-medium px-6 py-3 rounded-lg hover:bg-gray-100 transition">
          Registre-se
          <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-white border-t border-gray-200 py-12">
    <div class="container mx-auto px-4">
      <div class="flex flex-col items-center space-y-6">
        <div class="flex items-center space-x-3">
          <div class="bg-green-600 p-2 rounded-lg">
            <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900">RADCI</div>
            <div class="text-sm text-gray-500">
              Construindo cidades mais inteligentes
            </div>
          </div>
        </div>
        <div class="text-gray-500 text-sm">
          © 2025 RADCI. Todos os direitos reservados.
        </div>
      </div>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
      }
    });
  </script>
  <?php if (isset($_SESSION['usuario']) || isset($_SESSION['usuario_nome'])) { include __DIR__ . '/mobile_nav.php'; } ?>
</body>
</html>
