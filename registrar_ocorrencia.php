<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Inicializa sessão para armazenar dados entre passos
if(!isset($_SESSION['report'])) {
    $_SESSION['report'] = [
        'step' => 1,
        'address' => '',
        'cep' => '',
        'type' => '',
        'description' => '',
        'coordinates' => [-22.9068, -43.1729],
        'files' => []
    ];
}

$step = $_SESSION['report']['step'];

// Processa POST
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Passo atual enviado no formulário
    $postedStep = isset($_POST['step']) ? intval($_POST['step']) : $step;

    // Navegação para trás: não valida, apenas retorna um passo
    if (isset($_POST['navigate']) && $_POST['navigate'] === 'back') {
        $step = max(1, $postedStep - 1);
    } else {
        if ($postedStep === 1) {
            $_SESSION['report']['address'] = $_POST['address'] ?? '';
            $_SESSION['report']['cep'] = $_POST['cep'] ?? '';
            if (isset($_POST['coordinates'])) {
                $_SESSION['report']['coordinates'] = explode(',', $_POST['coordinates']);
            }
            $step = 2;
        } elseif ($postedStep === 2) {
            $_SESSION['report']['type'] = $_POST['type'] ?? '';
            $_SESSION['report']['description'] = $_POST['description'] ?? '';
            // Salva arquivos em pasta temporária pública para pré-visualização
            $filesOut = [];
            if (!empty($_FILES['files']['name'][0])) {
                $previewDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'temp';
                if (!is_dir($previewDir)) { @mkdir($previewDir, 0777, true); }
                foreach ($_FILES['files']['name'] as $i => $name) {
                    $tmp  = $_FILES['files']['tmp_name'][$i] ?? '';
                    $type = $_FILES['files']['type'][$i] ?? '';
                    if ($tmp && is_uploaded_file($tmp)) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $safeName = uniqid('preview_', true) . ($ext ? '.' . $ext : '');
                        $destPath = $previewDir . DIRECTORY_SEPARATOR . $safeName;
                        if (@move_uploaded_file($tmp, $destPath)) {
                            $filesOut[] = [
                                'name' => $name,
                                'type' => $type,
                                'url'  => 'uploads/temp/' . $safeName, // URL pública
                            ];
                        }
                    }
                }
            }
            $_SESSION['report']['preview_files'] = $filesOut;

            $step = 3;
        } else {
            // Finalização (Passo 3) — mostra mensagem e vai para Minhas Ocorrências
            $_SESSION['flash_success'] = 'Ocorrência cadastrada com sucesso!';
            unset($_SESSION['report']); // limpa o estado do wizard
            session_write_close();
            header('Location: minhas_ocorrencias.php');
            exit;
        }
    }

    $_SESSION['report']['step'] = $step;
}

// Dados atuais
$data = $_SESSION['report'];

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar Ocorrência</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous" />
<script defer src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>

<script>
<?php if ($step === 1): ?>
document.addEventListener('DOMContentLoaded', () => {
  const mapEl = document.getElementById('map');
  const coordsEl = document.getElementById('coordinates');
  const cepEl = document.getElementById('cep');
  const addressEl = document.getElementById('address');

  const DEFAULT = [-22.9068, -43.1729];
  let map, marker;
  let lastRequestId = 0;
  let currentAbort = null;
  let refineWatchId = null;
  let cepChangedByUser = false;    // usuário editou o CEP?
  let lastCepFetchedDigits = '';   // evita requisição repetida

  // ÚNICA definição de newRequest
  function newRequest() {
      if (currentAbort) { try { currentAbort.abort(); } catch(_) {} }
      currentAbort = new AbortController();
      lastRequestId++;
      return { id: lastRequestId, signal: currentAbort.signal };
  }

  // Helpers de normalização (texto e UF) e perímetro
  function norm(s) {
    return String(s || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }
  function toUF(s) {
    const n = norm(s);
    if (!n) return '';
    if (n.length === 2) return n;
    const map = {
      'acre':'ac','alagoas':'al','amapa':'ap','amazonas':'am','bahia':'ba','ceara':'ce',
      'distrito federal':'df','df':'df','espirito santo':'es','goias':'go','maranhao':'ma',
      'mato grosso':'mt','mato grosso do sul':'ms','minas gerais':'mg','para':'pa','paraiba':'pb',
      'parana':'pr','pernambuco':'pe','piaui':'pi','rio de janeiro':'rj','rio grande do norte':'rn',
      'rio grande do sul':'rs','rondonia':'ro','roraima':'rr','santa catarina':'sc','sao paulo':'sp',
      'sergipe':'se','tocantins':'to'
    };
    return map[n] || n.slice(0,2);
  }
  function isWithinBounds(lat, lon, bounds) {
    if (!bounds) return true;
    return Number.isFinite(lat) && Number.isFinite(lon) &&
           lon >= bounds.left && lon <= bounds.right &&
           lat >= bounds.bottom && lat <= bounds.top;
  }

  // NOVO: controle de requisições assíncronas
  let reqCounter = 0;
  function newRequest() {
    const controller = new AbortController();
    const id = ++reqCounter;
    lastRequestId = id;
    return { id, signal: controller.signal };
  }

  // NOVO: wrappers de geolocalização
  function getCurrentPosition(options = {}) {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocalização não suportada'));
        return;
      }
      navigator.geolocation.getCurrentPosition(resolve, reject, options);
    });
  }
  function getCurrentPositionWithTimeout(timeoutMs = 8000) {
    return getCurrentPosition({ enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 0 });
  }

  // NOVO: mover mapa/marcador e opcionalmente sincronizar por reverse
  async function applyPosition(lat, lng, opts = {}) {
    if (!map) {
      createMap([lat, lng]);
    } else {
      marker.setLatLng([lat, lng]);
      map.setView([lat, lng]);
    }
    setCoords(lat, lng);
    if (opts.reverse) {
      await doReverse(lat, lng);
    }
  }

  // NOVO: reverse geocoding via API local
  async function doReverse(lat, lng) {
    try {
      const { id, signal } = newRequest();
      const url = `api_reverse.php?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=20`;
      const res = await fetch(url, { signal });
      const data = await res.json();
      if (id !== lastRequestId) return;          // ignora respostas antigas
      if (!data || data.error) return;

      const addr = data.address || {};
      const city = addr.city || addr.town || addr.village || '';
      const uf   = addr.state_code || (addr.state ? (addr.state.match(/[A-Z]{2}/)?.[0] || addr.state) : '');
      const road = addr.road || addr.pedestrian || addr.footway || addr.path || '';
      const number = addr.house_number || '';
      const suburb = addr.suburb || addr.neighbourhood || addr.quarter || addr.hamlet || '';
      const postcode = String(addr.postcode || '').replace(/\D/g, '');

      const left = road ? (number ? `${road}, ${number}` : road) : '';
      const right = city ? `${suburb ? suburb + ', ' : ''}${city}${uf ? ', ' + uf : ''}` : (suburb ? suburb : '');
      const formatted = `${left}${right ? ' - ' + right : ''}`.trim();

      if (formatted) addressEl.value = formatted;
      if (postcode && postcode.length >= 8) cepEl.value = `${postcode.slice(0,5)}-${postcode.slice(5,8)}`;
    } catch (err) {
      console.warn('doReverse error', err);
    }
  }
  if (!mapEl || typeof L === 'undefined') {
    console.error('Leaflet não carregado ou mapa ausente');
    return;
  }

  // Estado e helpers
  // NÃO redeclarar map/marker/DEFAULT/lastRequestId/currentAbort/refineWatchId aqui

  function stopRefine() {
    if (refineWatchId !== null) {
      try { navigator.geolocation.clearWatch(refineWatchId); } catch(_) {}
      refineWatchId = null;
    }
  }

  const accBadge = document.createElement('div');
  accBadge.id = 'gpsBadge';
  accBadge.className = 'absolute top-2 left-2 z-10 px-2 py-1 text-xs bg-black/60 text-white rounded';
  accBadge.style.display = 'none';
  mapEl.appendChild(accBadge);

  function setCoords(lat, lng) { coordsEl.value = `${lat},${lng}`; }
  function formatCepDigits(s) { const d=(s||'').replace(/\D/g,''); return d.length===8?`${d.slice(0,5)}-${d.slice(5)}`:s; }
  function resumir(display_name) { if(!display_name) return ''; const parts=display_name.split(',').map(p=>p.trim()).filter(Boolean); return parts.slice(0,4).join(', '); }

  // Define a criação do mapa ANTES de chamar init()
  function createMap(start) {
    map = L.map(mapEl).setView(start, 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);
    marker = L.marker(start, { draggable: true }).addTo(map);
    setCoords(start[0], start[1]);
    map.invalidateSize();

    // botão "minha localização"
    const LocateControl = L.control({position: 'topright'});
    LocateControl.onAdd = function() {
      const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
      const btn = L.DomUtil.create('a', '', container);
      btn.href = '#';
      btn.title = 'Usar minha localização';
      btn.innerHTML = '📍';
      btn.style.padding = '6px';
      btn.style.fontSize = '16px';
      L.DomEvent.on(btn, 'click', L.DomEvent.stop).on(btn, 'click', async () => {
        stopRefine();
        try {
          const pos = await getCurrentPosition({enableHighAccuracy:true, timeout:10000});
          await applyPosition(pos.coords.latitude, pos.coords.longitude, {reverse:true});
        } catch (err) {
          console.warn('Erro ao obter localização via botão', err);
          alert('Não foi possível obter sua localização.');
        }
      });
      return container;
    };
    LocateControl.addTo(map);

    // Interação manual: para refino e faz reverse
    marker.on('dragend', async () => { stopRefine(); const p = marker.getLatLng(); await applyPosition(p.lat, p.lng, {reverse:true}); });
    map.on('click', async (e) => { stopRefine(); await applyPosition(e.latlng.lat, e.latlng.lng, {reverse:true}); });
    map.on('contextmenu', async (e) => { stopRefine(); await applyPosition(e.latlng.lat, e.latlng.lng, {reverse:true}); });
  }

  // Busca bounding box da cidade/UF para limitar resultados ao perímetro correto
  async function getCityBounds(city, uf) {
      if (!city || !uf) return null;
      const { id, signal } = newRequest();
      try {
          const res = await fetch(`api_geocode.php?q=${encodeURIComponent(`${city}, ${uf} Brasil`)}&limit=1`, { signal });
          if (id !== lastRequestId) return null;
          const data = await res.json();
          if (Array.isArray(data) && data[0] && data[0].boundingbox) {
              const bb = data[0].boundingbox; // [south, north, west, east]
              const south = parseFloat(bb[0]);
              const north = parseFloat(bb[1]);
              const west  = parseFloat(bb[2]);
              const east  = parseFloat(bb[3]);
              return {
                  left: west,
                  top: north,
                  right: east,
                  bottom: south,
                  viewbox: `${west},${north},${east},${south}`,
                  centerLat: parseFloat(data[0].lat),
                  centerLon: parseFloat(data[0].lon)
              };
          }
      } catch (err) { if (err.name !== 'AbortError') console.error('getCityBounds', err); }
      return null;
  }

  // Escolhe melhor candidato com cidade/UF normalizados e filtro pelo perímetro
  function pickBest(results, opts = {}) {
    if (!Array.isArray(results) || results.length === 0) return null;

    const expectedPostal = String(opts.expectedPostal || '').replace(/\D/g,'');
    const expectedCityN = norm(opts.expectedCity || '');
    const expectedUF = toUF(opts.expectedState || '');
    const expectedStreetN = norm(opts.expectedStreet || '');
    const expectedNeighbourhoodN = norm(opts.expectedNeighbourhood || '');
    const expectedHouseNumberN = norm(opts.expectedHouseNumber || '');
    const bounds = opts.bounds || null;
    const strictPostal = !!opts.strictPostal;

    // 1) CEP exato dentro do perímetro
    if (expectedPostal) {
      const cepMatches = results.filter(r => {
        const rp = String(r.address?.postcode || '').replace(/\D/g,'');
        const lat = parseFloat(r.lat), lon = parseFloat(r.lon);
        return rp === expectedPostal && isWithinBounds(lat, lon, bounds);
      });
      if (cepMatches.length) {
        cepMatches.sort((a,b) => (parseFloat(b.importance || 0) - parseFloat(a.importance || 0)));
        return cepMatches[0];
      }
      if (strictPostal) return null;
    }

    // 2) Filtra por cidade e UF normalizados
    const cityUfMatches = results.filter(r => {
      const cityRaw = r.address?.city || r.address?.town || r.address?.village || r.address?.municipality || '';
      const ufRaw = r.address?.state_code || r.address?.state || '';
      const cityN = norm(cityRaw);
      const candUF = toUF(ufRaw);
      const lat = parseFloat(r.lat), lon = parseFloat(r.lon);

      const cityOk = expectedCityN ? cityN.includes(expectedCityN) : true;
      const ufOk = expectedUF ? candUF === expectedUF : true;

      return cityOk && ufOk && isWithinBounds(lat, lon, bounds);
    });

    const baseList = cityUfMatches.length ? cityUfMatches : results;

    // 3) Pontua rua, número e bairro
    const scored = baseList.map(r => {
      const road = r.address?.road || r.address?.pedestrian || r.address?.footway || r.address?.path || '';
      const num  = r.address?.house_number || '';
      const sub  = r.address?.suburb || r.address?.neighbourhood || r.address?.quarter || r.address?.hamlet || '';
      const roadN = norm(road);
      const numN  = norm(num);
      const subN  = norm(sub);

      let score = 0;
      if (expectedStreetN && roadN.includes(expectedStreetN)) score += 60;
      if (expectedHouseNumberN && numN && numN === expectedHouseNumberN) score += 40;
      if (expectedNeighbourhoodN && subN.includes(expectedNeighbourhoodN)) score += 20;

      score += parseFloat(r.importance || 0);
      return { r, score };
    });

    if (strictPostal) {
      const filtered = scored.filter(s => s.score >= 40); // exige pelo menos rua ou número
      if (!filtered.length) return null;
      filtered.sort((a,b) => b.score - a.score);
      return filtered[0].r;
    }

    scored.sort((a,b) => b.score - a.score);
    return (scored[0] || {}).r || null;
  }

  // Geocodificação via API local (texto livre), com pick refinado
  async function geocodeAddress(query, expectedPostal = null, expectedCity = '', expectedState = '', expectedStreet = '', expectedNeighbourhood = '') {
    const { id, signal } = newRequest();
    try {
      const res = await fetch(`api_geocode.php?q=${encodeURIComponent(query)}&limit=5`, { signal });
      if (id !== lastRequestId) return null;
      const results = await res.json();
      if (!Array.isArray(results) || results.length === 0) return null;
      const best = pickBest(results, { expectedPostal, expectedCity, expectedState, expectedStreet, expectedNeighbourhood });
      return best || results[0];
    } catch (err) {
      if (err.name !== 'AbortError') return null;
      return null;
    }
  }

  // CEP → usa bounding box da cidade e sempre move o mapa (com fallback)
  async function fromCepInput(cep) {
    stopRefine();
    const cepDigits = (cep || '').replace(/\D/g,'');
    if (cepDigits.length !== 8) return;
  
    try {
      const { id, signal } = newRequest();
  
      // ViaCEP → estrutura, com fallback se falhar (ex.: 502)
      const via = await fetch(`api_viacep.php?cep=${encodeURIComponent(cepDigits)}`, { signal });
      if (!via.ok) {
        const uPostal = `api_geocode.php?postalcode=${encodeURIComponent(cepDigits)}&countrycodes=br&limit=10`;
        const rPostal = await (await fetch(uPostal, { signal })).json();
        if (id !== lastRequestId) return;
        const bestPostal = pickBest(rPostal, { expectedPostal: cepDigits });
        if (bestPostal && bestPostal.lat && bestPostal.lon) {
          await applyPosition(parseFloat(bestPostal.lat), parseFloat(bestPostal.lon), { reverse: true });
          cepEl.value = formatCepDigits(cepDigits);
          return;
        }
        alert(`Erro ao consultar ViaCEP (HTTP ${via.status}).`);
        return;
      }
  
      const data = await via.json();
      if (id !== lastRequestId) return;
      if (data?.erro) { alert('CEP não encontrado'); return; }
  
      const rua = data.logradouro || '';
      const bairro = data.bairro || '';
      const cidade = data.localidade || '';
      const uf = data.uf || '';
  
      addressEl.value = `${rua}${bairro ? ', ' + bairro : ''} - ${cidade}, ${uf}`.trim();
      cepEl.value = formatCepDigits(cepDigits);
  
      // Limita pelo perímetro da cidade/UF
      const bounds = await getCityBounds(cidade, uf);
      const vbParam = bounds?.viewbox ? `&viewbox=${encodeURIComponent(bounds.viewbox)}&bounded=1` : '';
  
      // 1) street+postalcode+city+state com perímetro + BR
      const u1 = `api_geocode.php?street=${encodeURIComponent(rua)}&postalcode=${encodeURIComponent(cepDigits)}&city=${encodeURIComponent(cidade)}&state=${encodeURIComponent(uf)}&countrycodes=br${vbParam}&limit=10`;
      const r1 = await (await fetch(u1, { signal })).json();
      if (id !== lastRequestId) return;
      let best = pickBest(r1, {
        expectedPostal: cepDigits,
        expectedCity: cidade,
        expectedState: uf,
        expectedStreet: rua,
        expectedNeighbourhood: bairro,
        expectedNeighbourhood: bairro,
        strictPostal: true,
        bounds
      });
  
      // 2) postalcode puro com perímetro + BR
      if (!best) {
        const u2 = `api_geocode.php?postalcode=${encodeURIComponent(cepDigits)}&countrycodes=br${vbParam}&limit=10`;
        const r2 = await (await fetch(u2, { signal })).json();
        if (id !== lastRequestId) return;
        best = pickBest(r2, {
          expectedPostal: cepDigits,
          expectedCity: cidade,
          expectedState: uf,
          strictPostal: true,
          bounds
        }) || null;
      }
  
      if (best && best.lat && best.lon) {
        await applyPosition(parseFloat(best.lat), parseFloat(best.lon), { reverse: true });
      } else if (bounds && Number.isFinite(bounds.centerLat) && Number.isFinite(bounds.centerLon)) {
        await applyPosition(bounds.centerLat, bounds.centerLon, { reverse: true });
      } else {
        alert('Não foi possível localizar coordenadas para este CEP.');
      }
    } catch (err) {
      console.error('fromCepInput', err);
    }
  }

  // Endereço → busca estruturada limitada por cidade/UF; fallback centro da cidade
  async function fromAddressInput(q) {
    stopRefine();
    if (!q || q.trim().length < 4) return;
    try {
      // "Rua, Número - Bairro, Cidade, UF" (aceita também sem número)
      const partsDash = q.split(' - ');
      const left = (partsDash[0] || '').trim();
      const right = (partsDash[1] || '').trim();

      // Rua e número
      const leftParts = left.split(',').map(s => s.trim()).filter(Boolean);
      const rua = leftParts[0] || '';
      const numero = leftParts.length >= 2 ? leftParts[1] : '';

      // Bairro, Cidade, UF
      const rightParts = right.split(',').map(s => s.trim()).filter(Boolean);
      let uf = '', cidade = '', bairro = '';
      if (rightParts.length >= 1 && /^[A-Za-z]{2}$/.test(rightParts[rightParts.length - 1])) {
        uf = rightParts[rightParts.length - 1];
      }
      if (rightParts.length >= 2) cidade = rightParts[rightParts.length - 2];
      if (rightParts.length >= 3) bairro = rightParts.slice(0, rightParts.length - 2).join(', ');

      // Limita pela cidade/UF
      const bounds = await getCityBounds(cidade, uf);
      const vbParam = bounds?.viewbox ? `&viewbox=${encodeURIComponent(bounds.viewbox)}&bounded=1` : '';

      const streetQuery = numero ? `${rua} ${numero}` : rua;
      const { id, signal } = newRequest();

      // 1) estruturado com perímetro + country br
      const url1 = `api_geocode.php?street=${encodeURIComponent(streetQuery)}&city=${encodeURIComponent(cidade)}&state=${encodeURIComponent(uf)}&countrycodes=br${vbParam}&limit=10`;
      const r1 = await (await fetch(url1, { signal })).json();
      if (id !== lastRequestId) return;

      let found = pickBest(r1, {
        expectedCity: cidade,
        expectedState: uf,
        expectedStreet: rua,
        expectedNeighbourhood: bairro,
        expectedHouseNumber: numero,
        bounds
      });

      // 2) fallback livre no perímetro
      if (!found) {
        const free = `${streetQuery} - ${bairro ? bairro + ', ' : ''}${cidade}, ${uf} Brasil`;
        const url2 = `api_geocode.php?q=${encodeURIComponent(free)}&countrycodes=br${vbParam}&limit=10`;
        const r2 = await (await fetch(url2, { signal })).json();
        if (id !== lastRequestId) return;
        found = pickBest(r2, {
          expectedCity: cidade,
          expectedState: uf,
          expectedStreet: rua,
          expectedNeighbourhood: bairro,
          expectedHouseNumber: numero,
          bounds
        });
      }

      if (found && found.lat && found.lon) {
        await applyPosition(parseFloat(found.lat), parseFloat(found.lon), { reverse: true });
      } else if (bounds && Number.isFinite(bounds.centerLat) && Number.isFinite(bounds.centerLon)) {
        await applyPosition(bounds.centerLat, bounds.centerLon, { reverse: true });
      } else {
        alert('Endereço não encontrado no mapa.');
      }
    } catch (err) {
      console.error('fromAddressInput', err);
    }
  }

  // Eventos CEP/Endereço (param o refino e reposicionam)
  cepEl.addEventListener('input', (e) => {
    const raw = e.target.value.replace(/\D/g,'');
    e.target.value = formatCepDigits(raw);
    cepChangedByUser = true; // marca que foi o usuário
  });

  cepEl.addEventListener('blur', async (e) => {
    const digits = (e.target.value || '').replace(/\D/g,'');
    if (!cepChangedByUser) return;             // ignora blur não causado por edição
    if (digits.length !== 8) { cepChangedByUser = false; return; }
    if (digits === lastCepFetchedDigits) { cepChangedByUser = false; return; }
    await fromCepInput(e.target.value);
    lastCepFetchedDigits = digits;
    cepChangedByUser = false;
  });

  cepEl.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const digits = (cepEl.value || '').replace(/\D/g,'');
      if (digits.length !== 8) return;
      await fromCepInput(cepEl.value);
      lastCepFetchedDigits = digits;
      cepChangedByUser = false;
    }
  });

  let addrTimer = null;
  addressEl.addEventListener('input', (e) => {
    const q = e.target.value;
    if (addrTimer) clearTimeout(addrTimer);
    addrTimer = setTimeout(async () => { await fromAddressInput(q); }, 600);
  });
  addressEl.addEventListener('keydown', async (e) => { if (e.key === 'Enter') { e.preventDefault(); await fromAddressInput(addressEl.value); } });

  // Inicialização: SEMPRE tenta GPS primeiro; refino por até 10s com indicador
  (async function init() {
    try {
      const pos = await getCurrentPositionWithTimeout(8000);
      const lat = pos.coords.latitude, lng = pos.coords.longitude;
      createMap([lat,lng]);
      await doReverse(lat,lng);
      return;
    } catch (err) {
      const saved = (coordsEl.value || '').split(',').map(Number);
      const hasSaved = saved.length === 2 && !isNaN(saved[0]) && !isNaN(saved[1]);
      const start = hasSaved ? saved : DEFAULT;
      createMap(start);
      await doReverse(start[0], start[1]);
      return;
    }
    // REMOVIDO: geocodificar CEP/endereço da sessão aqui
    return;
  })();

});
<?php endif; ?>
</script>




</head>


<body class="min-h-screen bg-gray-100 pb-20">
  <header class="bg-white border-b border-gray-200">
    <div class="container mx-auto px-4 py-4">
      <a href="dashboard.php" class="inline-flex items-center text-green-600 font-medium hover:text-green-800">Voltar</a>
    </div>
  </header>

  <main class="container mx-auto max-w-screen-lg px-4 py-4">
    <?php if($step === 1): ?>
      <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Passo 1 — Localização</h1>
        <form method="POST" class="space-y-4">
          <input type="hidden" name="step" value="1" />
          <input type="hidden" id="coordinates" name="coordinates" value="<?= htmlspecialchars(is_array($data['coordinates'] ?? null) ? implode(',', $data['coordinates']) : '') ?>" />

        <div>
          <label for="address" class="block text-sm font-medium text-gray-700">Endereço</label>
          <input id="address" name="address" type="text" value="<?= htmlspecialchars($data['address'] ?? '') ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="Rua, Bairro - Cidade, UF" />
        </div>

        <div>
          <label for="cep" class="block text-sm font-medium text-gray-700">CEP</label>
          <input id="cep" name="cep" type="text" value="<?= htmlspecialchars($data['cep'] ?? '') ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="00000-000" />
        </div>


        <div id="map" class="relative w-full h-64 md:h-72 lg:h-80 rounded-lg border border-gray-200"></div>

        <div class="flex justify-end">
          <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">Continuar</button>
        </div>
    <?php elseif($step === 2): ?>
      <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Passo 2 — Detalhes</h1>
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
          <input type="hidden" name="step" value="2" />
    
          <!-- Tipo de manifestação (radio visível com bolinha preenchida) -->
          <?php $selectedType = $data['type'] ?? ''; ?>
          <div>
            <p class="block text-sm font-medium text-gray-700 mb-2">Tipo de manifestação</p>
            <div class="space-y-3">
              <label class="flex items-center gap-3 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-green-400">
                <input type="radio" name="type" value="Sugestão de Melhoria"
                       class="accent-green-600 w-4 h-4" <?= $selectedType==='Sugestão de Melhoria'?'checked':'' ?> required>
                <span class="text-gray-900">Sugestão de Melhoria</span>
              </label>
    
              <label class="flex items-center gap-3 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-green-400">
                <input type="radio" name="type" value="Reclamação"
                       class="accent-green-600 w-4 h-4" <?= $selectedType==='Reclamação'?'checked':'' ?> required>
                <span class="text-gray-900">Reclamação</span>
              </label>
    
              <label class="flex items-center gap-3 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-green-400">
                <input type="radio" name="type" value="Elogio"
                       class="accent-green-600 w-4 h-4" <?= $selectedType==='Elogio'?'checked':'' ?> required>
                <span class="text-gray-900">Elogio</span>
              </label>
    
              <label class="flex items-center gap-3 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-green-400">
                <input type="radio" name="type" value="Outros"
                       class="accent-green-600 w-4 h-4" <?= $selectedType==='Outros'?'checked':'' ?> required>
                <span class="text-gray-900">Outros</span>
              </label>
            </div>
          </div>
    
          <!-- Descrição -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
            <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-md border-gray-300" placeholder="Descreva o problema ou sua sugestão em detalhes..."><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
          </div>
    
          <!-- Imagens ou Vídeos (dropzone bonito) -->
          <div>
            <p class="block text-sm font-medium text-gray-700 mb-2">Imagens ou Vídeos</p>
            <div id="dropzone" class="w-full rounded-lg border-2 border-dashed border-gray-300 bg-white p-8 text-center transition">
              <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17a4 4 0 004 4h8a4 4 0 004-4"/>
                </svg>
              </div>
              <p id="fileInfo" class="text-gray-600">Arraste arquivos ou clique para selecionar</p>
              <p id="fileError" class="mt-2 text-sm text-red-600"></p>
              <button type="button" id="btnSelectFiles" class="mt-4 bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">Selecionar Arquivos</button>
              <input id="fileInput" type="file" name="files[]" multiple accept="image/*,video/*" class="hidden" />
            </div>
          </div>
    
          <div class="flex justify-between">
            <button type="submit" name="navigate" value="back" formnovalidate class="px-4 py-2 rounded-md border border-gray-300 text-gray-700">Voltar</button>
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">Pré-visualizar</button>
          </div>
        </form>
      </div>
    <?php elseif($step === 3): ?>
      <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Passo 3 — Revisão</h1>

      <div class="bg-white rounded-xl shadow p-4 space-y-2 mb-6">
        <div><strong>Endereço:</strong> <?= htmlspecialchars($data['address'] ?? '') ?></div>
        <div><strong>CEP:</strong> <?= htmlspecialchars($data['cep'] ?? '') ?></div>
        <div><strong>Tipo:</strong> <?= htmlspecialchars($data['type'] ?? '') ?></div>
        <div><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($data['description'] ?? '')) ?></div>
      </div>

      <div class="bg-white rounded-xl shadow p-4 mb-6">
        <h2 class="text-lg font-semibold mb-2">Local no mapa</h2>
        <!-- AUMENTO DE ALTURA NO PREVIEW -->
        <div id="mapPreview" class="relative w-full h-64 md:h-72 lg:h-80 rounded-lg border border-gray-200"></div>
      </div>

      <?php $files = $data['preview_files'] ?? []; if(!empty($files)): ?>
      <div class="bg-white rounded-xl shadow p-4 mb-6">
        <h2 class="text-lg font-semibold mb-2">Mídia</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <?php foreach($files as $f): 
            $url = htmlspecialchars($f['url'] ?? '');
            $type = (strpos($f['type'] ?? '', 'video') !== false) ? 'video' : 'image';
          ?>
            <div class="cursor-pointer" data-media-url="<?= $url ?>" data-media-type="<?= $type ?>" data-media-mime="<?= htmlspecialchars($f['type'] ?? '') ?>">
              <?php if($type === 'image'): ?>
                <img src="<?= $url ?>" class="w-full h-32 object-cover rounded" alt="<?= htmlspecialchars($f['name'] ?? '') ?>">
              <?php else: ?>
                <video class="w-full h-32 object-cover rounded" muted>
                  <source src="<?= $url ?>" type="<?= htmlspecialchars($f['type'] ?? '') ?>">
                </video>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <form method="POST" class="flex justify-between">
        <input type="hidden" name="step" value="3" />
        <button type="submit" name="navigate" value="back" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700">Voltar</button>
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">Finalizar</button>
      </form>

      <div id="mediaModal" class="fixed inset-0 bg-black/60 hidden z-40 items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-xl overflow-hidden shadow-xl relative">
          <button id="mediaClose" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl px-3 py-1" aria-label="Fechar">&times;</button>
          <img id="modalImage" class="w-full h-96 object-cover" style="display:none" alt="Pré-visualização de imagem" />
          <video id="modalVideo" class="w-full h-96 object-cover" style="display:none" controls>
            <source id="modalVideoSource" src="" type="">
          </video>
        </div>
      </div>
    <?php endif; ?>
  </main>
  <?php if (isset($_SESSION['usuario'])) { include __DIR__ . '/mobile_nav.php'; } ?>
</body>
</html>
