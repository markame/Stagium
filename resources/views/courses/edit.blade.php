<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Editar curso - {{ config('app.name', 'Stagium') }}</title>

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
            .wrap { width: min(100%, 760px); margin: 0 auto; }

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

            .panel {
                margin-top: 32px;
                border: 1px solid var(--border);
                border-radius: 8px;
                background: var(--surface);
                padding: 24px;
                box-shadow: 0 8px 24px rgba(18, 32, 51, 0.06);
            }

            h2 {
                margin-bottom: 4px;
                font-size: 18px;
                font-weight: 700;
                letter-spacing: 0;
            }

            .hint { margin-bottom: 0; color: var(--muted); font-size: 14px; }
            form { margin-top: 24px; }
            .field { margin-bottom: 20px; }

            .field-row {
                display: grid;
                grid-template-columns: 112px 1fr;
                gap: 16px;
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

            .form-actions {
                display: flex;
                gap: 12px;
                align-items: center;
            }

            button, .secondary-link {
                border-radius: 6px;
                padding: 11px 16px;
                font: inherit;
                font-size: 14px;
                font-weight: 700;
            }

            button {
                border: 0;
                background: var(--accent);
                color: #fff;
                cursor: pointer;
                transition: background 150ms ease, box-shadow 150ms ease;
            }

            button:hover { background: var(--accent-hover); }
            button:focus { outline: none; box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 24%, transparent); }

            .secondary-link {
                color: var(--muted);
                text-decoration: none;
            }

            .secondary-link:hover { background: var(--surface-muted); }

            @media (max-width: 720px) {
                .page { padding: 28px 16px; }
                .header { align-items: flex-start; flex-direction: column; }
                .field-row, .form-actions { grid-template-columns: 1fr; flex-direction: column; align-items: stretch; }
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
                        <h1>Editar curso</h1>
                    </div>

                    <nav class="tabs" aria-label="Navegação principal">
                        <a href="{{ route('dashboard') }}" class="tab">Dashboard</a>
                        <a href="{{ route('courses.index') }}" class="tab active">Início</a>
                        <a href="{{ route('profile.edit') }}" class="tab">Meus dados</a>
                    </nav>
                </header>

                <section class="panel">
                    <h2>{{ $course->name }}</h2>
                    <p class="hint">Atualize o nome, a área de atuação e a cidade onde o curso acontece.</p>

                    <form action="{{ route('courses.update', $course) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="field">
                            <label for="name">Nome</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $course->name) }}" required>
                            @error('name')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="area">Área de atuação</label>
                            <select id="area" name="area" required>
                                <option value="">Selecione uma área</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area }}" @selected(old('area', $course->area) === $area)>{{ $area }}</option>
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
                                        <option value="{{ $state }}" @selected(old('state', $course->state) === $state)>{{ $state }}</option>
                                    @endforeach
                                </select>
                                @error('state')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="city">Cidade</label>
                                <select id="city" name="city" data-selected="{{ old('city', $course->city) }}" required>
                                    <option value="">Carregando cidades...</option>
                                </select>
                                @error('city')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit">Salvar alterações</button>
                            <a href="{{ route('courses.index') }}" class="secondary-link">Cancelar</a>
                        </div>
                    </form>
                </section>
            </main>
        </div>

        <script>
            const stateSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');

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

            stateSelect.addEventListener('change', () => fillCities());
            fillCities(citySelect.dataset.selected);
        </script>
    </body>
</html>
