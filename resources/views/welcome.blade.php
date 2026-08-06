<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Stagium') }}</title>

        @fonts

        <style>
            :root {
                --background: #f6f8fb;
                --surface: #ffffff;
                --surface-muted: #eef4fb;
                --text: #122033;
                --muted: #637083;
                --border: #d8e2ee;
                --field-border: #c8d5e4;
                --accent: #155ea8;
                --accent-hover: #0f4f8f;
                --accent-soft: #e8f2ff;
                --success-bg: #e7f5ee;
                --success-border: #b8dec9;
                --success-text: #145235;
                --danger: #b42318;
                font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                background: var(--background);
                color: var(--text);
                font-family: inherit;
            }

            .page { min-height: 100vh; padding: 40px 24px; }
            .wrap { width: min(100%, 1180px); margin: 0 auto; }

            .header {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 16px;
                border-bottom: 1px solid var(--border);
                padding-bottom: 24px;
            }

            .brand {
                margin: 0 0 4px;
                color: var(--accent);
                font-size: 14px;
                font-weight: 700;
            }

            h1, h2, p { margin-top: 0; }

            h1 {
                margin-bottom: 0;
                font-size: clamp(28px, 4vw, 36px);
                line-height: 1.15;
                font-weight: 700;
                letter-spacing: 0;
            }

            .tabs {
                display: flex;
                gap: 4px;
                border: 1px solid var(--border);
                border-radius: 8px;
                background: var(--surface);
                padding: 4px;
                box-shadow: 0 1px 2px rgba(18, 32, 51, 0.06);
            }

            .tab {
                border-radius: 6px;
                color: var(--muted);
                padding: 8px 16px;
                font-size: 14px;
                font-weight: 700;
                text-decoration: none;
            }

            .tab.active {
                background: var(--accent);
                color: #fff;
            }

            .status {
                margin-top: 24px;
                border: 1px solid var(--success-border);
                border-radius: 8px;
                background: var(--success-bg);
                color: var(--success-text);
                padding: 12px 16px;
                font-size: 14px;
            }

            .panel {
                border: 1px solid var(--border);
                border-radius: 8px;
                background: var(--surface);
                padding: 24px;
                box-shadow: 0 8px 24px rgba(18, 32, 51, 0.06);
            }

            .map-panel { margin-top: 32px; }

            .grid {
                display: grid;
                grid-template-columns: 0.9fr 1.25fr;
                gap: 24px;
                margin-top: 24px;
            }

            .map-grid {
                display: grid;
                grid-template-columns: 1fr 340px;
                gap: 20px;
                margin-top: 20px;
            }

            #company-map {
                min-height: 440px;
                border: 1px solid var(--border);
                border-radius: 8px;
                overflow: hidden;
                background: var(--surface-muted);
            }

            .company-list {
                min-height: 440px;
                max-height: 440px;
                overflow-y: auto;
                border: 1px solid var(--border);
                border-radius: 8px;
                background: var(--surface);
            }

            .company-item {
                padding: 14px 16px;
                border-bottom: 1px solid var(--border);
            }

            .company-item:last-child { border-bottom: 0; }

            .company-name {
                margin: 0 0 4px;
                font-size: 14px;
                font-weight: 700;
            }

            .company-meta {
                margin: 0;
                color: var(--muted);
                font-size: 13px;
            }

            .company-link {
                display: inline-block;
                margin-top: 6px;
                color: var(--accent);
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
            }

            h2 {
                margin-bottom: 4px;
                font-size: 18px;
                font-weight: 700;
                letter-spacing: 0;
            }

            .hint, .count, .secondary { color: var(--muted); }
            .hint, .count { margin-bottom: 0; font-size: 14px; }

            form { margin-top: 24px; }
            .field { margin-bottom: 20px; }

            .field-row {
                display: grid;
                grid-template-columns: 112px 1fr;
                gap: 16px;
            }

            .search-row {
                display: grid;
                grid-template-columns: 1fr 160px 220px;
                align-items: end;
                gap: 16px;
            }

            .scanner-field {
                margin-top: 16px;
            }

            textarea {
                display: block;
                width: 100%;
                min-height: 92px;
                margin-top: 8px;
                border: 1px solid var(--field-border);
                border-radius: 6px;
                background: var(--surface);
                color: var(--text);
                padding: 10px 12px;
                font: inherit;
                font-size: 14px;
                resize: vertical;
                outline: none;
            }

            textarea:focus {
                border-color: var(--accent);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
            }

            label {
                display: block;
                font-size: 14px;
                font-weight: 650;
            }

            input, select {
                display: block;
                width: 100%;
                margin-top: 8px;
                border: 1px solid var(--field-border);
                border-radius: 6px;
                background: var(--surface);
                color: var(--text);
                padding: 10px 12px;
                font: inherit;
                font-size: 14px;
                outline: none;
                transition: border-color 150ms ease, box-shadow 150ms ease;
            }

            input:focus, select:focus {
                border-color: var(--accent);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
            }

            .error {
                margin: 8px 0 0;
                color: var(--danger);
                font-size: 14px;
            }

            button {
                width: 100%;
                border: 0;
                border-radius: 6px;
                background: var(--accent);
                color: #fff;
                padding: 11px 16px;
                font: inherit;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                transition: background 150ms ease, box-shadow 150ms ease;
            }

            button:hover { background: var(--accent-hover); }
            button:focus { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 24%, transparent); }
            button:disabled { cursor: not-allowed; opacity: 0.55; }

            .logout-form { margin: 0; }

            .logout-button {
                width: auto;
                background: transparent;
                color: var(--muted);
                padding: 8px 16px;
            }

            .logout-button:hover { background: var(--surface-muted); }

            .actions {
                display: flex;
                align-items: center;
                gap: 8px;
                white-space: nowrap;
            }

            .action-link,
            .danger-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 34px;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
            }

            .action-link {
                background: var(--accent-soft);
                color: var(--accent);
            }

            .danger-button {
                width: auto;
                background: #fff1f0;
                color: var(--danger);
            }

            .danger-button:hover { background: #ffe4e0; }
            .inline-form { margin: 0; }

            .panel-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
            }

            .table-wrap {
                margin-top: 24px;
                overflow-x: auto;
                border: 1px solid var(--border);
                border-radius: 8px;
            }

            table {
                width: 100%;
                min-width: 720px;
                border-collapse: collapse;
                text-align: left;
                font-size: 14px;
            }

            thead {
                background: var(--surface-muted);
                color: var(--muted);
            }

            th, td { padding: 13px 16px; vertical-align: top; }
            th { font-weight: 700; }
            tbody tr + tr { border-top: 1px solid var(--border); }
            td:first-child { font-weight: 700; }

            .badge {
                display: inline-flex;
                align-items: center;
                min-width: 36px;
                justify-content: center;
                border-radius: 999px;
                background: var(--accent-soft);
                color: var(--accent);
                padding: 4px 9px;
                font-size: 12px;
                font-weight: 800;
            }

            .empty {
                color: var(--muted);
                text-align: center;
            }

            @media (max-width: 920px) {
                .page { padding: 28px 16px; }
                .header, .panel-head { align-items: flex-start; flex-direction: column; }
                .grid, .field-row, .map-grid, .search-row { grid-template-columns: 1fr; }
                .tabs { width: 100%; }
                .tab { flex: 1; text-align: center; }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <main class="wrap">
                <header class="header">
                    <div>
                        <p class="brand">Stagium</p>
                        <h1>Painel inicial</h1>
                    </div>

                    <nav class="tabs" aria-label="Navegação principal">
                        <a href="{{ route('courses.index') }}" class="tab active">Início</a>
                        <a href="{{ route('students.index') }}" class="tab">Alunos</a>
                        <a href="{{ route('companies.index') }}" class="tab">Empresas</a>
                        <a href="{{ route('profile.edit') }}" class="tab">Meus dados</a>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="logout-button">Sair</button>
                        </form>
                    </nav>
                </header>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <section class="panel map-panel">
                    <div class="panel-head">
                        <div>
                            <h2>Empresas por curso e cidade</h2>

                        </div>
                    </div>

                    <div class="search-row">
                        <div class="field">
                            <label for="map-course">Curso</label>
                            <select id="map-course" @disabled($courses->isEmpty())>
                                <option value="">Todos os cursos cadastrados</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">
                                        {{ $course->name }} - {{ $course->area }} - {{ $course->city }}/{{ $course->state }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" id="search-companies" @disabled($courses->isEmpty() || blank($googleMapsApiKey))>Ver salvas</button>
                        <button type="button" id="scan-companies" @disabled($courses->isEmpty() || blank($googleMapsApiKey))>Scanner da base Receita</button>
                    </div>

                    <div class="map-grid">
                        <div id="company-map"></div>
                        <div class="company-list" id="company-list">
                            <div class="company-item">
                                <p class="company-name">Carregando mapa</p>
                                <p class="company-meta">Os pontos dos cursos cadastrados aparecem aqui quando a busca estiver pronta.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid">
                    <div class="panel">
                        <h2>Novo curso</h2>
                        <p class="hint">Informe o curso, a área de atuação e a cidade onde ele acontece.</p>

                        <form action="{{ route('courses.store') }}" method="POST">
                            @csrf

                            <div class="field">
                                <label for="name">Nome</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                                @error('name')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="area">Área de atuação</label>
                                <select id="area" name="area" required>
                                    <option value="">Selecione uma área</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area }}" @selected(old('area') === $area)>{{ $area }}</option>
                                    @endforeach
                                </select>
                                @error('area')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label for="state">Estado</label>
                                    <select id="state" name="state" required>
                                        <option value="">UF</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state }}" @selected(old('state') === $state)>{{ $state }}</option>
                                        @endforeach
                                    </select>
                                    @error('state')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="field">
                                    <label for="city">Cidade</label>
                                    <select id="city" name="city" data-selected="{{ old('city') }}" required @disabled(! old('state'))>
                                        <option value="">Selecione o estado primeiro</option>
                                    </select>
                                    @error('city')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit">Cadastrar curso</button>
                        </form>
                    </div>

                    <div class="panel">
                        <div class="panel-head">
                            <div>
                                <h2>Cursos cadastrados</h2>
                                <p class="count">{{ $courses->count() }} curso(s)</p>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Área</th>
                                        <th>Local</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($courses as $course)
                                        <tr>
                                            <td>{{ $course->name }}</td>
                                            <td class="secondary">{{ $course->area }}</td>
                                            <td>
                                                <span class="badge">{{ $course->state ?? '-' }}</span>
                                                <span class="secondary">{{ $course->city ?? '-' }}</span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <a href="{{ route('courses.edit', $course) }}" class="action-link">Editar</a>
                                                    <form action="{{ route('courses.destroy', $course) }}" method="POST" class="inline-form" onsubmit="return confirm('Excluir este curso?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="danger-button">Excluir</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="empty">Nenhum curso cadastrado ainda.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <script>
            const stateSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');
            const courseSelect = document.getElementById('map-course');
            const searchButton = document.getElementById('search-companies');
            const scanButton = document.getElementById('scan-companies');
            const companyList = document.getElementById('company-list');
            const hasGoogleMapsKey = @json(filled($googleMapsApiKey));
            let map = null;
            let infoWindow = null;
            let markers = [];

            window.initGoogleMap = function () {
                map = new google.maps.Map(document.getElementById('company-map'), {
                    center: { lat: -14.235, lng: -51.9253 },
                    zoom: 4,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                });
                infoWindow = new google.maps.InfoWindow();

                if (courseSelect.disabled) {
                    setCompanyMessage('Nenhum curso cadastrado', 'Cadastre um curso para carregar empresas no mapa.');
                    return;
                }

                if (hasGoogleMapsKey) {
                    searchCompanies();
                }
            };

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char]));
            }

            function setCityPlaceholder(text, disabled = true) {
                citySelect.innerHTML = '';
                citySelect.disabled = disabled;

                const option = document.createElement('option');
                option.value = '';
                option.textContent = text;
                citySelect.appendChild(option);
            }

            async function fillCities(selectedCity = '') {
                if (!stateSelect.value) {
                    setCityPlaceholder('Selecione o estado primeiro');
                    return;
                }

                setCityPlaceholder('Carregando cidades...');

                try {
                    const response = await fetch(
                        `https://servicodados.ibge.gov.br/api/v1/localidades/estados/${stateSelect.value}/municipios?orderBy=nome`
                    );

                    if (!response.ok) {
                        throw new Error('Falha ao carregar cidades');
                    }

                    const cities = await response.json();

                    setCityPlaceholder('Selecione uma cidade', cities.length === 0);

                    cities.forEach((city) => {
                        const option = document.createElement('option');
                        option.value = city.nome;
                        option.textContent = city.nome;
                        option.selected = city.nome === selectedCity;
                        citySelect.appendChild(option);
                    });
                } catch (error) {
                    setCityPlaceholder('Não foi possível carregar as cidades');
                }
            }

            function setCompanyMessage(title, text) {
                companyList.innerHTML = `
                    <div class="company-item">
                        <p class="company-name">${escapeHtml(title)}</p>
                        <p class="company-meta">${escapeHtml(text)}</p>
                    </div>
                `;
            }

            function renderCompanies(companies) {
                markers.forEach((marker) => marker.setMap(null));
                markers = [];

                if (companies.length === 0) {
                    setCompanyMessage('Nenhuma empresa encontrada', 'O Google Places não retornou locais para esta combinação.');
                    return;
                }

                companyList.innerHTML = '';
                const bounds = new google.maps.LatLngBounds();

                companies.forEach((company) => {
                    const position = { lat: company.lat, lng: company.lng };
                    const marker = new google.maps.Marker({
                        map,
                        position,
                        title: company.name,
                    });

                    bounds.extend(position);
                    markers.push(marker);

                    marker.addListener('click', () => {
                        const course = company.course || {};
                        const phone = company.phone || company.international_phone || '';
                        const website = company.website_url
                            ? `<br><a href="${escapeHtml(company.website_url)}" target="_blank" rel="noreferrer">Site da empresa</a>`
                            : '';
                        infoWindow.setContent(`
                            <strong>${escapeHtml(company.name)}</strong><br>
                            ${escapeHtml(course.name || 'Curso')}<br>
                            ${escapeHtml(course.area || '')}<br>
                            ${company.cnpj ? `CNPJ: ${escapeHtml(company.cnpj)}<br>` : ''}
                            ${company.registration_status ? `Situação: ${escapeHtml(company.registration_status)}<br>` : ''}
                            ${escapeHtml(company.type || 'empresa')}<br>
                            ${escapeHtml(company.address || '')}
                            ${company.email ? `<br>E-mail: ${escapeHtml(company.email)}` : ''}
                            ${phone ? `<br>Telefone: ${escapeHtml(phone)}` : ''}
                            ${website}
                        `);
                        infoWindow.open({ anchor: marker, map });
                    });

                    const item = document.createElement('div');
                    item.className = 'company-item';
                    const source = company.source ? `Fonte: ${escapeHtml(company.source)}` : '';
                    const course = company.course || {};
                    const courseText = [course.name, course.area].filter(Boolean).join(' - ');
                    const phone = company.phone || company.international_phone || '';
                    item.innerHTML = `
                        <p class="company-name">${escapeHtml(company.name)}</p>
                        ${company.cnpj ? `<p class="company-meta">CNPJ: ${escapeHtml(company.cnpj)}</p>` : ''}
                        ${courseText ? `<p class="company-meta">${escapeHtml(courseText)}</p>` : ''}
                        ${company.registration_status ? `<p class="company-meta">Situação: ${escapeHtml(company.registration_status)}</p>` : ''}
                        <p class="company-meta">${escapeHtml(company.type || 'empresa')}${company.address ? ' - ' + escapeHtml(company.address) : ''}</p>
                        ${company.email ? `<p class="company-meta">E-mail: ${escapeHtml(company.email)}</p>` : ''}
                        ${phone ? `<p class="company-meta">Telefone: ${escapeHtml(phone)}</p>` : ''}
                        ${source ? `<p class="company-meta">${source}</p>` : ''}
                        ${company.website_url ? `<a class="company-link" href="${escapeHtml(company.website_url)}" target="_blank" rel="noreferrer">Site da empresa</a>` : ''}
                        ${company.maps_url ? `<a class="company-link" href="${escapeHtml(company.maps_url)}" target="_blank" rel="noreferrer">Abrir no Google Maps</a>` : ''}
                    `;
                    item.addEventListener('click', () => {
                        map.setZoom(17);
                        map.panTo(position);
                        google.maps.event.trigger(marker, 'click');
                    });
                    companyList.appendChild(item);
                });

                map.fitBounds(bounds);
            }

            async function searchCompanies(scan = false) {
                if (!hasGoogleMapsKey || !map) {
                    setCompanyMessage('Google Places não configurado', 'Configure GOOGLE_MAPS_API_KEY no arquivo .env para habilitar o mapa e a busca.');
                    return;
                }

                if (courseSelect.disabled) {
                    setCompanyMessage('Nenhum curso cadastrado', 'Cadastre um curso para carregar empresas no mapa.');
                    return;
                }

                if (scan && !courseSelect.value) {
                    setCompanyMessage('Selecione um curso', 'A consulta por CNPJ precisa ser vinculada a um curso cadastrado.');
                    return;
                }

                searchButton.disabled = true;
                scanButton.disabled = true;
                setCompanyMessage('Buscando empresas...', courseSelect.value
                    ? 'Consultando Google Places para a cidade e área do curso.'
                    : 'Consultando Google Places para todos os cursos cadastrados.'
                );

                try {
                    const url = new URL(scan ? '{{ route('companies.scan', [], false) }}' : '{{ route('companies.search', [], false) }}', window.location.origin);
                    if (courseSelect.value) {
                        url.searchParams.set('course_id', courseSelect.value);
                    }
                    const body = scan ? new URLSearchParams({
                        course_id: courseSelect.value,
                    }) : undefined;

                    const response = await fetch(url, {
                        method: scan ? 'POST' : 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Não foi possível buscar empresas.');
                    }

                    if (data.center) {
                        map.setCenter({ lat: data.center.lat, lng: data.center.lng });
                        map.setZoom(12);
                    }

                    renderCompanies(data.companies || []);
                } catch (error) {
                    markers.forEach((marker) => marker.setMap(null));
                    markers = [];
                    setCompanyMessage('Busca indisponível', error.message);
                } finally {
                    searchButton.disabled = false;
                    scanButton.disabled = false;
                }
            }

            stateSelect.addEventListener('change', () => {
                fillCities();
            });
            searchButton?.addEventListener('click', () => searchCompanies(false));
            scanButton?.addEventListener('click', () => searchCompanies(true));

            if (stateSelect.value) {
                fillCities(citySelect.dataset.selected);
            } else {
                setCityPlaceholder('Selecione o estado primeiro');
            }

            if (!hasGoogleMapsKey) {
                setCompanyMessage('Google Places não configurado', 'Configure GOOGLE_MAPS_API_KEY no arquivo .env para habilitar o mapa e a busca.');
                searchButton.disabled = true;
                scanButton.disabled = true;
            }
        </script>
        @if (filled($googleMapsApiKey))
            <script
                src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}&callback=initGoogleMap&v=weekly"
                async
                defer
            ></script>
        @endif
    </body>
</html>
