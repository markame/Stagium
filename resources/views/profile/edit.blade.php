<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Meus dados - {{ config('app.name', 'Stagium') }}</title>
        @fonts
        <style>
            :root { --background:#f6f8fb; --surface:#fff; --surface-muted:#eef4fb; --text:#122033; --muted:#637083; --border:#d8e2ee; --field-border:#c8d5e4; --accent:#155ea8; --accent-hover:#0f4f8f; --success-bg:#e7f5ee; --success-border:#b8dec9; --success-text:#145235; --danger:#b42318; font-family:"Instrument Sans",ui-sans-serif,system-ui,sans-serif; }
            * { box-sizing: border-box; }
            body { margin:0; background:var(--background); color:var(--text); font-family:inherit; }
            .page { min-height:100vh; padding:40px 24px; }
            .wrap { width:min(100%,960px); margin:0 auto; }
            .header { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; border-bottom:1px solid var(--border); padding-bottom:24px; }
            .brand { margin:0 0 4px; color:var(--accent); font-size:14px; font-weight:700; }
            h1,h2,p { margin-top:0; }
            h1 { margin-bottom:0; font-size:clamp(28px,4vw,36px); line-height:1.15; font-weight:700; letter-spacing:0; }
            .tabs { display:flex; gap:4px; border:1px solid var(--border); border-radius:8px; background:var(--surface); padding:4px; box-shadow:0 1px 2px rgba(18,32,51,.06); }
            .tab { border-radius:6px; color:var(--muted); padding:8px 16px; font-size:14px; font-weight:700; text-decoration:none; }
            .tab.active { background:var(--accent); color:#fff; }
            .logout-form { margin:0; }
            .logout-button { width:auto; border:0; border-radius:6px; background:transparent; color:var(--muted); padding:8px 16px; font:inherit; font-size:14px; font-weight:700; cursor:pointer; }
            .logout-button:hover { background:var(--surface-muted); }
            .status { margin-top:24px; border:1px solid var(--success-border); border-radius:8px; background:var(--success-bg); color:var(--success-text); padding:12px 16px; font-size:14px; }
            .grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:32px; }
            .panel { border:1px solid var(--border); border-radius:8px; background:var(--surface); padding:24px; box-shadow:0 8px 24px rgba(18,32,51,.06); }
            h2 { margin-bottom:4px; font-size:18px; font-weight:700; letter-spacing:0; }
            .hint { margin-bottom:0; color:var(--muted); font-size:14px; }
            form { margin-top:24px; }
            .field { margin-bottom:18px; }
            label { display:block; font-size:14px; font-weight:650; }
            input { display:block; width:100%; margin-top:8px; border:1px solid var(--field-border); border-radius:6px; padding:10px 12px; font:inherit; outline:none; }
            input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(21,94,168,.16); }
            .error { margin:8px 0 0; color:var(--danger); font-size:14px; }
            button { width:100%; border:0; border-radius:6px; background:var(--accent); color:#fff; padding:11px 16px; font:inherit; font-size:14px; font-weight:700; cursor:pointer; }
            button:hover { background:var(--accent-hover); }
            @media (max-width:820px) { .page{padding:28px 16px;} .header{align-items:flex-start; flex-direction:column;} .grid{grid-template-columns:1fr;} .tabs{width:100%;} .tab{flex:1; text-align:center;} }
        </style>
    </head>
    <body>
        <div class="page">
            <main class="wrap">
                <header class="header">
                    <div>
                        <p class="brand">Stagium</p>
                        <h1>Meus dados</h1>
                    </div>

                    <nav class="tabs" aria-label="Navegação principal">
                        <a href="{{ route('courses.index') }}" class="tab">Cursos</a>
                        <a href="{{ route('profile.edit') }}" class="tab active">Meus dados</a>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="logout-button">Sair</button>
                        </form>
                    </nav>
                </header>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <section class="grid">
                    <div class="panel">
                        <h2>Dados da conta</h2>
                        <p class="hint">Atualize seu nome e e-mail de acesso.</p>

                        <form action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="field">
                                <label for="name">Nome</label>
                                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="email">E-mail</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit">Salvar dados</button>
                        </form>
                    </div>

                    <div class="panel">
                        <h2>Alterar senha</h2>
                        <p class="hint">Informe sua senha atual antes de definir uma nova.</p>

                        <form action="{{ route('profile.password') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="field">
                                <label for="current_password">Senha atual</label>
                                <input id="current_password" name="current_password" type="password" required>
                                @error('current_password')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="password">Nova senha</label>
                                <input id="password" name="password" type="password" required>
                                @error('password')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="password_confirmation">Confirmar nova senha</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required>
                            </div>

                            <button type="submit">Alterar senha</button>
                        </form>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
