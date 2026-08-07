<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#0b5cad">
    <title>Portal do aluno - Stagium</title>
    @fonts
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        :root{--navy:#0b2747;--accent:#0b5cad;--accent-dark:#084b8d;--bg:#f2f6fb;--surface:#fff;--muted:#64748b;--border:#dbe5f0;--green:#147a50;--green-soft:#e8f7ef;--red:#b42318;--red-soft:#fff0ef;--shadow:0 10px 30px rgba(11,39,71,.08);font-family:"Instrument Sans",system-ui,sans-serif}
        *{box-sizing:border-box}html{background:var(--bg)}body{margin:0;background:var(--bg);color:var(--navy);-webkit-font-smoothing:antialiased}.shell{width:100%;min-height:100vh;padding:env(safe-area-inset-top) 0 env(safe-area-inset-bottom)}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;background:var(--surface);padding:16px 18px;border-bottom:1px solid var(--border)}.eyebrow{margin:0;color:var(--accent);font-size:11px;font-weight:850;letter-spacing:.08em}.welcome{margin:3px 0 0;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:20px}.logout{border:0;border-radius:10px;background:var(--red-soft);color:var(--red);padding:10px 12px;font:inherit;font-size:13px;font-weight:800;cursor:pointer}
        .content{display:flex;flex-direction:column;gap:14px;padding:14px}.card{overflow:hidden;border:1px solid var(--border);border-radius:16px;background:var(--surface);box-shadow:var(--shadow)}.card-body{padding:18px}.section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}h2{margin:0;font-size:18px}.muted{color:var(--muted);font-size:13px;line-height:1.45}.company{margin-top:13px}.company strong{display:block;font-size:15px}.company-address{display:block;margin-top:3px}.radius-chip{flex:none;border-radius:999px;background:#eaf3ff;color:var(--accent);padding:6px 9px;font-size:11px;font-weight:850}
        #map{height:300px;background:#dbe8f4}.map-empty{display:grid;place-items:center;height:260px;padding:24px;text-align:center;color:var(--muted)}.leaflet-control-attribution{font-size:9px}.leaflet-popup-content{margin:10px 12px;font-family:"Instrument Sans",sans-serif}
        .location-panel{display:grid;gap:10px;margin-top:14px}.status{display:flex;align-items:flex-start;gap:10px;min-height:52px;border:1px solid var(--border);border-radius:12px;background:#f8fafc;padding:12px;color:var(--muted);font-size:13px;line-height:1.4}.status-dot{flex:none;width:9px;height:9px;margin-top:4px;border-radius:50%;background:#94a3b8}.status.ok{border-color:#b8dfca;background:var(--green-soft);color:#115d3e}.status.ok .status-dot{background:var(--green)}.status.error{border-color:#f1c1bd;background:var(--red-soft);color:var(--red)}.status.error .status-dot{background:var(--red)}
        .actions{display:grid;grid-template-columns:1fr;gap:9px}.button{width:100%;min-height:52px;border:0;border-radius:12px;padding:13px 16px;font:inherit;font-size:15px;font-weight:850;cursor:pointer}.locate{border:1px solid #b9d2ed;background:#eef6ff;color:var(--accent)}.mark{background:var(--accent);color:#fff;box-shadow:0 8px 18px rgba(11,92,173,.22)}.mark:active{background:var(--accent-dark);transform:translateY(1px)}.button:disabled{box-shadow:none;background:#a8b8c9;color:#edf2f7;cursor:not-allowed}.distance{display:none;text-align:center;color:var(--muted);font-size:12px}.distance.visible{display:block}
        .history .card-body{padding-bottom:8px}.course{margin:4px 0 8px}.log{display:grid;grid-template-columns:1fr auto;align-items:center;gap:10px;padding:13px 0;border-top:1px solid var(--border)}.log-time{font-size:14px}.badge{border-radius:999px;padding:5px 9px;font-size:11px;font-weight:850}.badge.in{background:var(--green-soft);color:var(--green)}.badge.out{background:var(--red-soft);color:var(--red)}.empty{padding:18px 0 24px;text-align:center}
        @media(min-width:720px){.shell{width:min(100%,1040px);margin:auto;padding:24px}.topbar{border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow)}.welcome{max-width:560px;font-size:25px}.content{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(290px,.6fr);align-items:start;padding:18px 0}.attendance{grid-column:1}.history{grid-column:2;grid-row:1}#map{height:360px}.actions{grid-template-columns:.75fr 1.25fr}}
    </style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <div><p class="eyebrow">STAGIUM · ÁREA DO ALUNO</p><h1 class="welcome">Olá, {{ $student->name }}</h1></div>
        <form action="{{ route('logout') }}" method="POST">@csrf<button class="logout" type="submit">Sair</button></form>
    </header>

    <section class="content">
        <article class="card attendance">
            <div class="card-body">
                <div class="section-head"><div><h2>Registrar estágio</h2><p class="muted" style="margin:4px 0 0">Confirme sua posição dentro da área permitida.</p></div>@if($company)<span class="radius-chip">Raio {{ $company->attendance_radius_meters }} m</span>@endif</div>
                @if($company)
                    <div class="company"><strong>{{ $company->corporate_name }}</strong><span class="company-address muted">{{ method_exists($company, 'formattedAddress') ? $company->formattedAddress() : $company->address }}</span></div>
                @else
                    <p class="muted">Nenhuma empresa de estágio foi vinculada ao seu termo.</p>
                @endif
            </div>

            @if($company && $company->latitude !== null && $company->longitude !== null)
                <div id="map" role="application" aria-label="Mapa da área permitida para registro"></div>
            @else
                <div class="map-empty">A localização da empresa ainda não foi configurada.</div>
            @endif

            <div class="card-body location-panel">
                <div id="status" class="status" aria-live="polite"><span class="status-dot"></span><span id="status-text">Toque em “Usar minha localização” para conferir sua posição.</span></div>
                <div id="distance" class="distance"></div>
                <div class="actions">
                    <button id="locate" class="button locate" type="button" @disabled(!$company || $company->latitude===null)>Usar minha localização</button>
                    <button id="mark" class="button mark" type="button" disabled>{{ $lastLog?->type === 'in' ? 'Registrar saída' : 'Registrar entrada' }}</button>
                </div>
            </div>
        </article>

        <aside class="card history">
            <div class="card-body"><h2>Últimos registros</h2><p class="course muted">{{ $student->course?->name ?? 'Curso não informado' }}</p>
                @forelse($logs as $log)
                    <div class="log"><div><strong class="log-time">{{ $log->logged_at->format('d/m/Y H:i') }}</strong><br><span class="muted">{{ $log->company->corporate_name }} · {{ $log->distance_meters }} m</span></div><span class="badge {{ $log->type }}">{{ $log->type==='in'?'Entrada':'Saída' }}</span></div>
                @empty
                    <p class="empty muted">Você ainda não registrou nenhum ponto.</p>
                @endforelse
            </div>
        </aside>
    </section>
</main>

@php
    $mapCompany = $company && $company->latitude !== null ? [
        'latitude' => (float) $company->latitude,
        'longitude' => (float) $company->longitude,
        'radius' => (int) $company->attendance_radius_meters,
        'name' => $company->corporate_name,
    ] : null;
@endphp
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9coqIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(() => {
    const company = @json($mapCompany);
    const locateButton = document.getElementById('locate');
    const markButton = document.getElementById('mark');
    const status = document.getElementById('status');
    const statusText = document.getElementById('status-text');
    const distanceText = document.getElementById('distance');
    let currentPosition = null;
    let userMarker = null;
    let accuracyCircle = null;
    let map = null;

    const setStatus = (message, type = '') => {
        status.className = `status ${type}`.trim();
        statusText.textContent = message;
    };

    if (company && window.L) {
        const companyPoint = L.latLng(company.latitude, company.longitude);
        map = L.map('map', {zoomControl:true, attributionControl:true}).setView(companyPoint, 18);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(map);
        L.circle(companyPoint, {radius:company.radius,color:'#0b5cad',weight:2,fillColor:'#3b82f6',fillOpacity:.14}).addTo(map);
        L.marker(companyPoint).addTo(map).bindPopup(`<strong>${company.name}</strong><br>Área permitida: ${company.radius} m`).openPopup();
    }

    const updatePosition = position => {
        currentPosition = {latitude:position.coords.latitude, longitude:position.coords.longitude};
        const point = L.latLng(currentPosition.latitude, currentPosition.longitude);
        const companyPoint = L.latLng(company.latitude, company.longitude);
        const distance = Math.round(point.distanceTo(companyPoint));
        const inside = distance <= company.radius;

        if (userMarker) map.removeLayer(userMarker);
        if (accuracyCircle) map.removeLayer(accuracyCircle);
        userMarker = L.circleMarker(point, {radius:8,color:'#fff',weight:3,fillColor:inside?'#147a50':'#b42318',fillOpacity:1}).addTo(map).bindPopup('Sua localização');
        accuracyCircle = L.circle(point, {radius:position.coords.accuracy,color:'#64748b',weight:1,fillOpacity:.06}).addTo(map);
        map.fitBounds(L.latLngBounds([companyPoint, point]).pad(.35), {maxZoom:18});
        distanceText.textContent = `Você está a aproximadamente ${distance} m da empresa.`;
        distanceText.classList.add('visible');
        setStatus(inside ? 'Você está dentro da área permitida.' : `Você está fora do raio de ${company.radius} m.`, inside ? 'ok' : 'error');
        markButton.disabled = !inside;
        locateButton.disabled = false;
    };

    const locate = () => {
        if (!company || !navigator.geolocation) {
            setStatus('Este aparelho não oferece geolocalização.', 'error');
            return;
        }
        locateButton.disabled = true;
        markButton.disabled = true;
        setStatus('Obtendo sua localização…');
        navigator.geolocation.getCurrentPosition(updatePosition, error => {
            setStatus(`Não foi possível obter sua localização: ${error.message}`, 'error');
            locateButton.disabled = false;
        }, {enableHighAccuracy:true,timeout:15000,maximumAge:0});
    };

    locateButton?.addEventListener('click', locate);
    markButton?.addEventListener('click', async () => {
        if (!currentPosition) return locate();
        markButton.disabled = true;
        setStatus('Registrando seu ponto…');
        try {
            const response = await fetch('{{ route('student.time-log.mark') }}', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify(currentPosition)});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Não foi possível registrar o ponto.');
            setStatus(data.message, 'ok');
            setTimeout(() => location.reload(), 900);
        } catch (error) {
            setStatus(error.message, 'error');
            markButton.disabled = false;
        }
    });
})();
</script>
</body>
</html>
