<?php
session_start();
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

// Perfil do usuário (1=cidadão, 2=admin RADCI, 3=admin público, 4=secretário)
$userId    = intval($_SESSION['usuario_id'] ?? 0);
$userNome  = $_SESSION['usuario_nome'] ?? '';
$userPerfil= intval($_SESSION['usuario_perfil'] ?? 1);

$primeiroNome = isset($userNome) ? explode(" ", trim($userNome))[0] : "Usuário";

/* -------------------------
   Categorias (SVGs Lucide)
   ------------------------- */
$categories = [
  ['id'=>'saude','name'=>'Saúde','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>'],
  ['id'=>'inovacao','name'=>'Inovação','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-7 7c0 3 2 4 3 5l1 1h6l1-1c1-1 3-2 3-5a7 7 0 0 0-7-7z"/></svg>'],
  ['id'=>'mobilidade','name'=>'Mobilidade','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="10" width="18" height="5" rx="2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>'],
  ['id'=>'politicas','name'=>'Políticas Públicas','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8"/></svg>'],
  ['id'=>'riscos','name'=>'Riscos Urbanos','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>'],
  ['id'=>'sustentabilidade','name'=>'Sustentabilidade','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 3c-4 9 1 14 9 10-3 5-9 7-12 4S4 9 11 3z"/></svg>'],
  ['id'=>'planejamento','name'=>'Planejamento Urbano','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18"/><rect x="14" y="8" width="7" height="13"/></svg>'],
  ['id'=>'educacao','name'=>'Educação','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 12l-10-5-10 5 10 5 10-5z"/><path d="M6 16v2c0 1.1.9 2 2 2h8"/></svg>'],
  ['id'=>'meio','name'=>'Meio Ambiente','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2c3 6 1 10-4 12 2 4 6 5 9 2s4-7-2-14c-1 0-2 0-3 0z"/></svg>'],
  ['id'=>'infraestrutura','name'=>'Infraestrutura da Cidade','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>'],
  ['id'=>'seguranca','name'=>'Segurança Pública','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-4 9-8 10-4-1-8-5-8-10V6l8-4z"/></svg>'],
  ['id'=>'energias','name'=>'Energias Inteligentes','icon'=>'<svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>'],
];

/* -------------------------
   Ocorrências simuladas
   ------------------------- */
$ocorrencias = [
  [
    'categoria' => 'Saúde',
    'descricao'  => 'Posto de saúde com falta de médicos',
    'data'       => '10/10/2025',
    'imagem'     => '',
    'thumb'      => 'data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22200%22><rect width=%22320%22 height=%22200%22 fill=%22%23e5e7eb%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2216%22 fill=%22%236b7280%22>Sem imagem</text></svg>',
    'detalhes'   => 'O posto do bairro está sem atendimento em algumas especialidades desde o início do mês. Pacientes aguardam por horas e exames básicos não estão sendo realizados.',
    'fotos'      => [
      'https://via.placeholder.com/800x500.png?text=Foto+1',
      'https://via.placeholder.com/800x500.png?text=Foto+2'
    ]
  ],
  [
    'categoria' => 'Infraestrutura',
    'descricao'  => 'Buraco em via principal causando trânsito',
    'data'       => '09/10/2025',
    'imagem'     => 'https://via.placeholder.com/400x280.png?text=Infraestrutura',
    'thumb'      => 'https://via.placeholder.com/320x200.png?text=Infra',
    'detalhes'   => 'Grande buraco na Av. Brasil, atrapalhando o tráfego e causando acidentes leves. Caminhões precisam desviar para a faixa do ônibus.',
    'fotos'      => ['https://via.placeholder.com/800x500.png?text=Av.+Brasil']
  ],
  [
    'categoria' => 'Segurança Pública',
    'descricao'  => 'Falta de iluminação em praça pública',
    'data'       => '08/10/2025',
    'imagem'     => 'https://via.placeholder.com/400x280.png?text=Seguran%C3%A7a',
    'thumb'      => 'https://via.placeholder.com/320x200.png?text=Seguran%C3%A7a',
    'detalhes'   => 'A praça central está sem iluminação desde o último apagão. Moradores relatam insegurança e aumento de furtos à noite.',
    'fotos'      => ['https://via.placeholder.com/800x500.png?text=Praca']
  ],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>RADCI - Dashboard</title>
<style>
  .hide-scrollbar::-webkit-scrollbar { display: none; }
  .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  body { background: #ffffff !important; } /* garante fundo branco */
</style>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-white min-h-screen pb-28">

<!-- HEADER -->
<header class="bg-green-700 sticky top-0 z-30 shadow">
  <div class="container mx-auto px-4 py-1 flex items-center justify-end text-white">
    <div class="flex items-center gap-2">
      <button id="bellBtn" class="p-1 rounded hover:bg-white/20 relative" aria-label="Notificações">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.437L4 17h11z"/>
        </svg>
        <span id="bellBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none rounded-full px-1 hidden">0</span>
      </button>
      <a href="minha_conta.php" class="w-8 h-8 bg-white/20 flex items-center justify-center rounded-full hover:bg-white/30" aria-label="Minha conta">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A10 10 0 1112 22a9.969 9.969 0 01-6.879-4.196z"/>
        </svg>
      </a>
    </div>
  </div>

  <div class="container mx-auto px-4 pb-2">
    <h2 class="text-lg md:text-2xl font-bold text-white">Olá, <?= htmlspecialchars($primeiroNome) ?></h2>
    <div class="relative mt-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input id="globalSearch" type="text" placeholder="Busque em todo o RADCI" class="pl-8 w-full h-9 rounded-md border border-gray-200 text-gray-700 bg-white" />
    </div>
  </div>
</header>

<!-- CONTENT -->
<main class="container mx-auto px-8 md:px-12 py-6">

  <!-- CATEGORIAS -->
  <section class="mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-1">Registre ocorrências na sua cidade</h3>
    <p class="text-sm text-gray-500 mb-4">Publique ocorrências ou sugestões e contribua com a melhoria da sua cidade.</p>

    <div class="flex gap-4 overflow-x-auto hide-scrollbar pb-3">
      <?php foreach($categories as $cat): ?>
        <form method="GET" action="registrar_ocorrencia.php" class="flex flex-col items-center min-w-[88px]">
          <input type="hidden" name="categoryId" value="<?= htmlspecialchars($cat['id']) ?>">
          <button type="submit" class="flex flex-col items-center group">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-2 transition-all group-hover:shadow-lg group-hover:scale-105">
              <?= $cat['icon'] ?>
            </div>
            <span class="text-xs text-center text-gray-700 leading-tight max-w-[88px]"><?= htmlspecialchars($cat['name']) ?></span>
          </button>
        </form>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- PESQUISA DE PRIORIDADES (logo abaixo dos ícones) -->
  <section class="mb-8">
    <div class="bg-white rounded-2xl shadow p-6 border border-gray-200 relative">
      <div class="absolute right-6 top-6 text-green-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M3 12l6-6 4 4 8-8"/>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-900 mb-1">Pesquisa de Prioridades</h3>
      <p class="text-sm text-gray-700 mb-2">Ordene o que é mais importante para você</p>
      <p class="text-sm text-gray-500 mb-4">Ajude a definir as prioridades da sua cidade ordenando as categorias</p>
      <button onclick="location.href='prioridades.php'" class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 font-semibold">
        Responder Pesquisa
      </button>
    </div>
  </section>



  <!-- SUAS OCORRÊNCIAS REGISTRADAS -->
  <section class="mb-12 relative">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Suas Ocorrências Registradas</h3>
    <a href="registrar_ocorrencia.php"
       class="md:hidden absolute -top-2 right-2 text-green-700 text-4xl leading-none"
       aria-label="Nova ocorrência">+</a>
  
    <div class="flex gap-4 overflow-x-auto hide-scrollbar px-6 md:px-10 pb-4">
      <?php foreach($ocorrencias as $i => $o): ?>
        <article class="bg-gray-50 rounded-xl shadow min-w-[240px] flex-shrink-0">
            <!-- thumb com fallback quando CDN falha -->
            <img src="<?= htmlspecialchars($o['thumb']) ?>" alt="thumb"
                 class="w-full h-40 object-cover rounded-md"
                 onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22200%22><rect width=%22320%22 height=%22200%22 fill=%22%23e5e7eb%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2216%22 fill=%22%236b7280%22>Sem imagem</text></svg>'" />
          <div class="p-3">
            <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($o['categoria']) ?></h4>
            <p class="text-xs text-gray-600 line-clamp-2 mt-1"><?= htmlspecialchars($o['descricao']) ?></p>
            <p class="text-xs text-gray-400 mt-2"><?= htmlspecialchars($o['data']) ?></p>

            <div class="mt-3 flex items-center justify-between">
              <button onclick="openModal(<?= $i ?>)" class="text-green-700 text-sm font-semibold hover:underline">Ver mais</button>
              <a href="minhas_ocorrencias.php?i=<?= $i ?>" class="text-xs text-gray-500">Ver em página</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
  
      <!-- "+" grande dentro do carrossel (desktop) -->
      <div class="hidden md:flex items-center justify-center min-w-[88px]">
        <a href="registrar_ocorrencia.php"
           class="w-20 h-20 rounded-xl bg-green-600 text-white text-2xl font-bold hover:bg-green-700 flex items-center justify-center"
           aria-label="Criar ocorrência">+</a>
      </div>
    </div>
  </section>

  </main>

  
<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 bg-black/60 z-40 flex items-center justify-center p-4">
  <div class="bg-white w-full max-w-2xl rounded-xl overflow-hidden shadow-xl">
    <div class="relative">
      <button onclick="closeModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl px-3 py-1" aria-label="Fechar">&times;</button>
      <img id="modalMainImage" class="w-full h-60 object-cover" src="" alt="Imagem da ocorrência" />
    </div>

    <div class="p-4">
      <h3 id="modalCategoria" class="text-lg font-bold text-gray-800"></h3>
      <p id="modalData" class="text-xs text-gray-400 mt-1"></p>
      <p id="modalDescricao" class="text-sm text-gray-700 mt-3"></p>

      <!-- galeria -->
      <div id="modalGallery" class="mt-4 flex gap-2 overflow-x-auto hide-scrollbar"></div>

      <div class="mt-4 flex justify-between items-center">
        <a id="modalLinkPage" class="text-sm text-gray-600 hover:underline" href="#">Ver em página</a>
        <button onclick="closeModal()" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-md">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Define uma única vez
const ocorrencias = <?= json_encode($ocorrencias, JSON_UNESCAPED_UNICODE) ?>;

// Modal de ocorrências
function openModal(index) {
  const modal = document.getElementById('modal');
  const data = ocorrencias[index];
  if (!data) return;

  const mainImg = document.getElementById('modalMainImage');
  mainImg.src = (data.fotos && data.fotos.length) ? data.fotos[0] : (data.imagem || '');
  mainImg.onerror = function() {
    this.src = 'data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22500%22><rect width=%22800%22 height=%22500%22 fill=%22%23e5e7eb%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2220%22 fill=%22%236b7280%22>Sem imagem</text></svg>';
  };

  document.getElementById('modalCategoria').textContent = data.categoria || '';
  document.getElementById('modalData').textContent = data.data || '';
  document.getElementById('modalDescricao').textContent = data.detalhes || data.descricao || '';

  const gallery = document.getElementById('modalGallery');
  gallery.innerHTML = '';
  if (data.fotos && data.fotos.length) {
    data.fotos.forEach((src, i) => {
      const img = document.createElement('img');
      img.src = src;
      img.alt = `Foto ${i+1}`;
      img.className = 'w-28 h-20 object-cover rounded-md cursor-pointer';
      img.onerror = function() { this.style.display = 'none'; };
      img.onclick = () => { mainImg.src = src; };
      gallery.appendChild(img);
    });
  }

  const link = document.getElementById('modalLinkPage');
  link.href = `minhas_ocorrencias.php?i=${index}`;

  modal.classList.remove('hidden');
  modal.scrollTop = 0;
}

function closeModal() {
  document.getElementById('modal').classList.add('hidden');
}

// Fecha modal ao clicar fora/ESC
document.getElementById('modal')?.addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') closeModal();
});

// Painel de Status (sininho)
const statusOverlay = document.getElementById('statusOverlay');
const statusPanel   = document.getElementById('statusPanel');
const statusList    = document.getElementById('statusList');
const bellBtn       = document.getElementById('bellBtn');
const statusCloseBtn= document.getElementById('statusClose');

function openStatus() {
  statusOverlay?.classList.remove('hidden');
  statusPanel?.classList.remove('hidden');
  statusPanel?.classList.remove('translate-x-full');
}
function closeStatus() {
  statusOverlay?.classList.add('hidden');
  statusPanel?.classList.add('translate-x-full');
  // esconder após a transição
  setTimeout(() => statusPanel?.classList.add('hidden'), 200);
}
function pushStatus({ type, title, message, status }) {
  if (!statusList) return;
  const item = document.createElement('div');
  item.className = 'border-b last:border-0 py-2';
  item.innerHTML = `
    <div class="text-sm font-semibold text-gray-800">${title || 'Status'}</div>
    <div class="text-xs text-gray-600">${message || ''}</div>
    <div class="text-xs text-gray-400 mt-1">${status || 'enviado'}</div>
  `;
  statusList.prepend(item);
}

bellBtn?.addEventListener('click', openStatus);
statusOverlay?.addEventListener('click', closeStatus);
statusCloseBtn?.addEventListener('click', closeStatus);

// Busca global: Enter -> adiciona status e navega
document.getElementById('globalSearch')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    const q = (this.value || '').trim();
    if (q) { pushStatus({ type:'pesquisa', title:'Nova pesquisa', message:q, status:'enviada' }); }
    window.location.href = 'prioridades.php?q=' + encodeURIComponent(q);
  }
});

// Ícones Lucide
if (window.lucide && typeof lucide.createIcons === 'function') {
  lucide.createIcons();
}
</script>

</body>




<?php include __DIR__ . '/mobile_nav.php'; ?>
</body>
</html>
<?php
// Cidade/UF do usuário para alvo de pesquisa
$userCity = '';
$userUF   = '';
if ($userId) {
    try {
        $stmtU = $pdo->prepare("SELECT municipio, uf FROM usuarios WHERE id = ?");
        $stmtU->execute([$userId]);
        if ($rowU = $stmtU->fetch()) {
            $userCity = trim($rowU['municipio'] ?? '');
            $userUF   = strtoupper(trim($rowU['uf'] ?? ''));
        }
    } catch (Throwable $_) {}
}

// Mapeia perfil do usuário para tipo_destinatario da pesquisa
$perfilToTipo = [
    1 => 'cidadaos',
    2 => 'todos',            // admin RADCI vê geral; se quiser, posso ajustar para outro fluxo
    3 => 'admin_publicos',
    4 => 'secretarios',
];

// Lê colunas das tabelas
$pesqCols = [];
$valCols  = [];
try { $pesqCols = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM pesquisa")->fetchAll()); } catch (Throwable $_) {}
try { $valCols  = array_map(fn($r) => $r['Field'], $pdo->query("SHOW COLUMNS FROM usuarios_validacoes")->fetchAll()); } catch (Throwable $_) {}

$availableSurveys = [];
$answeredSurveys  = [];

// Carrega pesquisas do banco
try {
    $selectPesq = "SELECT * FROM pesquisa ORDER BY id DESC";
    $rows = $pdo->query($selectPesq)->fetchAll();

    // Carrega respostas do usuário, se houver tabela usuários_validacoes
    $answeredIds = [];
    if ($userId && in_array('idUsuario', $valCols) && in_array('idPesquisa', $valCols)) {
        $stmtV = $pdo->prepare("SELECT idPesquisa FROM usuarios_validacoes WHERE idUsuario = ?");
        $stmtV->execute([$userId]);
        $answeredIds = array_map(fn($r) => intval($r['idPesquisa']), $stmtV->fetchAll());
    } else {
        // Fallback por sessão (compatível com fluxo anterior)
        $answeredIds = []; // manter vazio; listagem responderá com $_SESSION abaixo
    }

    foreach ($rows as $r) {
        $sid            = $r['sid']            ?? (string)($r['id'] ?? '');
        $title          = $r['titulo']         ?? ($r['title'] ?? 'Pesquisa');
        $description    = $r['descricao']      ?? ($r['description'] ?? '');
        $recipientType  = $r['tipo_destinatario'] ?? ($r['recipient_type'] ?? 'todos');
        $targetCity     = trim($r['cidade']     ?? ($r['target_city'] ?? ''));
        $targetUF       = strtoupper(trim($r['uf'] ?? ($r['target_uf'] ?? '')));
        $pesqId         = intval($r['id'] ?? 0);

        // Elegibilidade por perfil
        $perfilTipo = $perfilToTipo[$userPerfil] ?? 'cidadaos';
        $forProfile = ($recipientType === 'todos' || $recipientType === $perfilTipo);

        // Elegibilidade por Cidade/UF
        $cityMatch = (!$targetCity || strtolower($targetCity) === strtolower($userCity));
        $ufMatch   = (!$targetUF   || strtoupper($targetUF) === $userUF);

        // Já respondida?
        $answeredByDb   = ($pesqId && in_array($pesqId, $answeredIds));
        $answeredBySess = isset($_SESSION['answered_surveys'][$sid]);

        if ($answeredByDb || $answeredBySess) {
            $answeredSurveys[] = [
                'id'          => $pesqId ?: $sid,
                'sid'         => $sid,
                'title'       => $title,
                'description' => $description,
            ];
            continue;
        }

        if ($forProfile && $cityMatch && $ufMatch) {
            $availableSurveys[] = [
                'id'          => $pesqId ?: $sid,
                'sid'         => $sid,
                'title'       => $title,
                'description' => $description,
            ];
        }
    }
} catch (Throwable $_) {}

$hasSurvey = count($availableSurveys) > 0;

?>
<!-- DASHBOARD (métricas por último) -->
<?php
  $totRegistradas = 0;
  $totConcluidas = 0;
  $totAndamento = 0;

  foreach ($ocorrencias as $o) {
    $status = isset($o['status']) ? strtolower($o['status']) : 'registrada';
    if ($status === 'concluida' || $status === 'concluída') $totConcluidas++;
    elseif ($status === 'andamento' || $status === 'em_andamento' || $status === 'em andamento') $totAndamento++;
    else $totRegistradas++;
  }
?>
<section class="mt-10 px-8 md:px-12">
  <h3 class="text-lg font-bold text-gray-800 mb-3">Dashboard</h3>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 3h18v4H3z"/><path d="M7 7v14"/><path d="M17 7v10"/></svg>
      <div>
        <div class="text-xs text-gray-500">Total Registradas</div>
        <div class="text-2xl font-bold text-gray-800"><?= $totRegistradas ?></div>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"/></svg>
      <div>
        <div class="text-xs text-gray-500">Concluídas</div>
        <div class="text-2xl font-bold text-gray-800"><?= $totConcluidas ?></div>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2"/></svg>
      <div>
        <div class="text-xs text-gray-500">Em andamento</div>
        <div class="text-2xl font-bold text-gray-800"><?= $totAndamento ?></div>
      </div>
    </div>
  </div>
</section>
</main>

<!-- STATUS CENTER -->
<div id="statusOverlay" class="hidden fixed inset-0 z-50 bg-black/40"></div>
<aside id="statusPanel" class="hidden fixed right-0 top-0 h-full w-full sm:w-[380px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-200">
  <div class="flex items-center justify-between px-4 py-3 border-b">
    <h4 class="font-bold text-gray-800">Status</h4>
    <button id="statusClose" onclick="closeStatus()" class="text-gray-600 hover:text-gray-800 p-1" aria-label="Fechar">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <div id="statusList" class="p-3 overflow-y-auto h-[calc(100%-48px)]"></div>
  <div class="p-4 border-t md:hidden">
    <button onclick="closeStatus()" class="w-full bg-green-600 text-white py-2 rounded-md">Voltar ao Dashboard</button>
  </div>
</aside>
</section>
</main>

<!-- STATUS CENTER -->
<div id="statusOverlay" class="hidden fixed inset-0 z-50 bg-black/40"></div>
<aside id="statusPanel" class="hidden fixed right-0 top-0 h-full w-full sm:w-[380px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-200">
  <div class="flex items-center justify-between px-4 py-3 border-b">
    <h4 class="font-bold text-gray-800">Status</h4>
    <button id="statusClose" onclick="closeStatus()" class="text-gray-600 hover:text-gray-800 p-1" aria-label="Fechar">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <div id="statusList" class="p-3 overflow-y-auto h-[calc(100%-48px)]"></div>

  <!-- Voltar ao Dashboard (mobile) -->
  <div class="p-4 border-t md:hidden">
    <button onclick="closeStatus()" class="w-full bg-green-600 text-white py-2 rounded-md">Voltar ao Dashboard</button>
  </div>
</aside>
</section>



</main>

<!-- STATUS CENTER -->
<div id="statusOverlay" class="hidden fixed inset-0 z-50 bg-black/40"></div>
<aside id="statusPanel" class="hidden fixed right-0 top-0 h-full w-full sm:w-[380px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-200">
  <div class="flex items-center justify-between px-4 py-3 border-b">
    <h4 class="font-bold text-gray-800">Status</h4>
    <button id="statusClose" onclick="closeStatus()" class="text-gray-600 hover:text-gray-800 p-1" aria-label="Fechar">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <div id="statusList" class="p-3 overflow-y-auto h-[calc(100%-48px)]"></div>
  <div class="p-4 border-t md:hidden">
    <button onclick="closeStatus()" class="w-full bg-green-600 text-white py-2 rounded-md">Voltar ao Dashboard</button>
  </div>
</aside>
</section>


<!-- Seção: Pesquisas Respondidas -->
<?php if (!empty($answeredSurveys)): ?>
<section class="container mx-auto px-6 py-6 max-w-6xl">
  <h2 class="text-xl font-semibold text-gray-900 mb-3">Pesquisas Respondidas</h2>
  <div class="grid md:grid-cols-2 gap-4">
    <?php foreach ($answeredSurveys as $sv): ?>
      <div class="rounded-lg border border-gray-200 p-4">
        <div class="font-semibold text-gray-900"><?= htmlspecialchars($sv['title']) ?></div>
        <?php if (!empty($sv['description'])): ?>
          <div class="text-gray-600 text-sm mt-1"><?= htmlspecialchars($sv['description']) ?></div>
        <?php endif; ?>
        <div class="text-gray-500 text-xs mt-2">Resposta registrada — edição bloqueada</div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php
?>
