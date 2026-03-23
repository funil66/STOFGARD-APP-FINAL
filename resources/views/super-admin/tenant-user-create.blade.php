<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar usuário do tenant</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Inter, Arial, sans-serif; background:#f5f5f7; margin:0; }
        .wrap { max-width:720px; margin:40px auto; background:#fff; border-radius:12px; padding:24px; box-shadow:0 6px 20px rgba(0,0,0,.08); }
        h1 { margin:0 0 20px; font-size:24px; }
        .row { margin-bottom:14px; }
        label { display:block; margin-bottom:6px; font-weight:600; }
        input, select { width:100%; padding:10px 12px; border:1px solid #d4d4d8; border-radius:8px; font-size:14px; }
        .actions { display:flex; gap:10px; margin-top:18px; }
        .btn { border:0; border-radius:8px; padding:10px 14px; font-weight:600; cursor:pointer; }
        .btn-primary { background:#7c3aed; color:#fff; }
        .btn-secondary { background:#e4e4e7; color:#111; text-decoration:none; display:inline-block; }
        .ok { background:#ecfdf5; border:1px solid #10b981; color:#065f46; padding:10px; border-radius:8px; margin-bottom:12px; }
        .err { background:#fef2f2; border:1px solid #ef4444; color:#7f1d1d; padding:10px; border-radius:8px; margin-bottom:12px; }
        .field-err { color:#b91c1c; font-size:12px; margin-top:4px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Criar usuário do tenant</h1>

    @if (session('success'))
        <div class="ok">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="err">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.tenant-users.store') }}">
        @csrf

        <div class="row">
            <label for="tenant_id">Tenant</label>
            <select name="tenant_id" id="tenant_id" required>
                <option value="">Selecione...</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id') === (string) $tenant->id)>
                        {{ $tenant->name }} ({{ $tenant->id }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <label for="name">Nome</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255">
        </div>

        <div class="row">
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required maxlength="255">
        </div>

        <div class="row">
            <label for="password">Senha</label>
            <input type="password" name="password" id="password" required minlength="8">
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Criar usuário</button>
            <a class="btn btn-secondary" href="{{ url('/portal/user-impersonations') }}">Voltar</a>
        </div>
    </form>
</div>
</body>
</html>
