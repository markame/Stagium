<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Entrar - {{ config('app.name', 'Stagium') }}</title>
        @fonts
        <style>
            :root { --background:#f6f8fb; --surface:#fff; --text:#122033; --muted:#637083; --border:#d8e2ee; --field-border:#c8d5e4; --accent:#155ea8; --accent-hover:#0f4f8f; --danger:#b42318; font-family:"Instrument Sans",ui-sans-serif,system-ui,sans-serif; }
            * { box-sizing: border-box; }
            body { margin:0; min-height:100vh; display:grid; place-items:center; background:var(--background); color:var(--text); font-family:inherit; padding:24px; }
            .card { width:min(100%,420px); border:1px solid var(--border); border-radius:8px; background:var(--surface); padding:28px; box-shadow:0 8px 24px rgba(18,32,51,.06); }
            .brand { margin:0 0 4px; color:var(--accent); font-size:14px; font-weight:700; }
            h1 { margin:0; font-size:28px; letter-spacing:0; }
            .hint { margin:8px 0 24px; color:var(--muted); font-size:14px; }
            .field { margin-bottom:18px; }
            label { display:block; font-size:14px; font-weight:650; }
            input { display:block; width:100%; margin-top:8px; border:1px solid var(--field-border); border-radius:6px; padding:10px 12px; font:inherit; outline:none; }
            input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(21,94,168,.16); }
            .check { display:flex; align-items:center; gap:8px; color:var(--muted); font-size:14px; }
            .check input { width:auto; margin:0; }
            .error { margin:8px 0 0; color:var(--danger); font-size:14px; }
            button { width:100%; border:0; border-radius:6px; background:var(--accent); color:#fff; padding:11px 16px; font:inherit; font-size:14px; font-weight:700; cursor:pointer; }
            button:hover { background:var(--accent-hover); }
            .link { display:block; margin-top:16px; color:var(--accent); text-align:center; font-size:14px; font-weight:700; text-decoration:none; }
        </style>
    </head>
    <body>
        <main class="card">
            <p class="brand">Stagium</p>
            <h1>Entrar</h1>
            <p class="hint">Acesse sua conta para cadastrar e acompanhar seus cursos.</p>

            <form action="{{ route('login.store') }}" method="POST">
                @csrf

                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" required>
                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label class="check">
                        <input name="remember" type="checkbox" value="1">
                        Manter conectado
                    </label>
                </div>

                <button type="submit">Entrar</button>
            </form>

            <a href="{{ route('register') }}" class="link">Criar uma conta</a>
        </main>
    </body>
</html>
