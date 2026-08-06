<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alunos - {{ config('app.name', 'Stagium') }}</title>
    @fonts
    <style>
        :root { --bg:#f6f8fb; --surface:#fff; --muted:#637083; --text:#122033; --border:#d8e2ee; --accent:#155ea8; --accent-hover:#0f4f8f; --soft:#eef4fb; --danger:#b42318; --success-bg:#e7f5ee; --success-border:#b8dec9; --success-text:#145235; font-family:"Instrument Sans",ui-sans-serif,system-ui,sans-serif; }
        * { box-sizing:border-box; } body { margin:0; background:var(--bg); color:var(--text); } .page { min-height:100vh; padding:40px 24px; } .wrap { width:min(100%,1180px); margin:auto; }
        .header,.panel-head,.actions { display:flex; align-items:center; justify-content:space-between; gap:16px; } .header { border-bottom:1px solid var(--border); padding-bottom:24px; } .brand { margin:0 0 4px; color:var(--accent); font-size:14px; font-weight:700; } h1 { margin:0; font-size:clamp(28px,4vw,36px); } h2 { margin:0 0 4px; font-size:18px; } p { margin-top:0; }
        .tabs { display:flex; gap:4px; border:1px solid var(--border); border-radius:8px; background:#fff; padding:4px; } .tab { border-radius:6px; color:var(--muted); padding:8px 16px; font-size:14px; font-weight:700; text-decoration:none; } .tab.active { background:var(--accent); color:#fff; }
        .status { margin-top:24px; border:1px solid var(--success-border); border-radius:8px; background:var(--success-bg); color:var(--success-text); padding:12px 16px; font-size:14px; }
        .layout { display:grid; grid-template-columns:minmax(320px,420px) 1fr; gap:24px; margin-top:32px; align-items:start; } .panel { border:1px solid var(--border); border-radius:8px; background:var(--surface); padding:24px; box-shadow:0 8px 24px rgba(18,32,51,.06); } .hint,.count,.secondary { color:var(--muted); font-size:14px; }
        form.main-form { margin-top:24px; } .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px 16px; } .field-wide { grid-column:1/-1; } label { display:block; font-size:14px; font-weight:650; } input,select { width:100%; margin-top:8px; border:1px solid #c8d5e4; border-radius:6px; background:#fff; color:var(--text); padding:10px 12px; font:inherit; font-size:14px; outline:none; } input:focus,select:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(21,94,168,.16); } .error { margin:6px 0 0; color:var(--danger); font-size:13px; }
        button,.action-link { border-radius:6px; padding:10px 14px; font:inherit; font-size:14px; font-weight:700; cursor:pointer; } .primary { width:100%; margin-top:22px; border:0; background:var(--accent); color:#fff; } .primary:hover { background:var(--accent-hover); } .primary:disabled { background:#91a4b8; cursor:not-allowed; }
        .table-wrap { margin-top:20px; overflow-x:auto; border:1px solid var(--border); border-radius:8px; } table { width:100%; min-width:720px; border-collapse:collapse; text-align:left; font-size:14px; } thead { background:var(--soft); color:var(--muted); } th,td { padding:13px 14px; border-bottom:1px solid var(--border); vertical-align:top; } tbody tr:last-child td { border-bottom:0; } .actions { justify-content:flex-start; gap:6px; } .inline { margin:0; } .action-link { color:var(--accent); text-decoration:none; padding:7px 9px; } .delete { border:0; background:transparent; color:var(--danger); padding:7px 9px; } .empty { padding:36px; text-align:center; color:var(--muted); }
        @media(max-width:960px){ .layout{grid-template-columns:1fr}.header,.panel-head{align-items:flex-start;flex-direction:column}.tabs{width:100%;overflow:auto}.tab{flex:1;text-align:center;white-space:nowrap} } @media(max-width:560px){.page{padding:24px 14px}.form-grid{grid-template-columns:1fr}.field-wide{grid-column:auto}.panel{padding:18px}}
    </style><style>.module-tabs{display:flex;gap:24px;margin-top:24px;border-bottom:1px solid var(--border)}.module-tab{border-bottom:3px solid transparent;color:var(--muted);padding:0 2px 12px;font-size:14px;font-weight:750;text-decoration:none}.module-tab.active{border-color:var(--accent);color:var(--accent)}.student-search{display:grid;grid-template-columns:1fr auto auto;align-items:end;gap:10px;margin-top:24px}.student-search input{margin-top:8px}.clear-search{display:inline-flex;align-items:center;justify-content:center;min-height:42px;color:var(--accent);font-size:14px;font-weight:700;text-decoration:none}@media(max-width:560px){.student-search{grid-template-columns:1fr}.student-search .button,.clear-search{width:100%}}</style>
</head>
<body><div class="page"><main class="wrap">
    <header class="header"><div><p class="brand">Stagium</p><h1>Cadastro de alunos</h1></div><nav class="tabs" aria-label="Navegação principal"><a href="{{ route('courses.index') }}" class="tab">Início</a><a href="{{ route('students.index') }}" class="tab active">Alunos</a><a href="{{ route('companies.index') }}" class="tab">Empresas</a><a href="{{ route('profile.edit') }}" class="tab">Meus dados</a></nav></header>
    <nav class="module-tabs"><a class="module-tab active" href="{{ route('students.index') }}">Cadastrar aluno</a><a class="module-tab" href="{{ route('students.import.form') }}">Importar CSV</a><a class="module-tab" href="{{ route('students.commitment-terms.index') }}">Termos de compromisso</a></nav>
    <form class="student-search" action="{{ route('students.index') }}" method="GET"><label for="student-search">Buscar aluno<input id="student-search" name="q" type="search" value="{{ $search }}" placeholder="Nome, CPF ou curso"></label><button class="button" type="submit">Buscar</button>@if($search !== '')<a class="clear-search" href="{{ route('students.index') }}">Limpar</a>@endif</form>
    @if(session('status')) <div class="status">{{ session('status') }}</div> @endif
    <section class="layout">
        <div class="panel"><h2>Novo aluno</h2><p class="hint">Preencha os dados pessoais e vincule o aluno a um curso.</p>
            @if($courses->isEmpty())
                <p class="error">Cadastre um curso antes de adicionar alunos.</p>
            @endif
            <form class="main-form" action="{{ route('students.store') }}" method="POST">@csrf @include('students._form')<button class="primary" type="submit" @disabled($courses->isEmpty())>Cadastrar aluno</button></form>
        </div>
        <div class="panel"><div class="panel-head"><div><h2>Alunos cadastrados</h2><p class="count">{{ $students->count() }} aluno(s)</p></div></div>
            <div class="table-wrap"><table><thead><tr><th>Aluno</th><th>Contato</th><th>Curso</th><th>Ações</th></tr></thead><tbody>
                @forelse($students as $student)
                    <tr><td><strong>{{ $student->name }}</strong><br><span class="secondary">CPF {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $student->cpf) }}@if($student->birth_date) · {{ $student->birth_date->format('d/m/Y') }}@endif</span></td><td>{{ $student->phone ?: 'Não informado' }}<br><span class="secondary">{{ $student->address ?: 'Endereço não informado' }}</span></td><td>{{ $student->course?->name ?? 'Sem curso' }}</td><td><div class="actions"><a class="action-link" href="{{ route('students.edit', $student) }}">Editar</a><form class="inline" action="{{ route('students.destroy', $student) }}" method="POST" onsubmit="return confirm('Excluir este aluno?')">@csrf @method('DELETE')<button class="delete" type="submit">Excluir</button></form></div></td></tr>
                @empty <tr><td colspan="4" class="empty">Nenhum aluno cadastrado ainda.</td></tr> @endforelse
            </tbody></table></div>
        </div>
    </section>
</main></div></body></html>
