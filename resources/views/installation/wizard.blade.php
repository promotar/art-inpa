<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform installation</title>
    <style>
        :root{--brand:#b30000;--ink:#171717;--muted:#666;--line:#ddd;--page:#f4f6f8;--surface:#fff;--danger:#9f1d1d}
        *{box-sizing:border-box}body{margin:0;background:var(--page);color:var(--ink);font:15px/1.5 Arial,sans-serif}
        main{width:min(720px,calc(100% - 32px));margin:48px auto}.brand{font-size:24px;font-weight:700;margin-bottom:24px}
        .steps{display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:20px;border:1px solid var(--line);background:var(--surface)}
        .step{padding:12px;text-align:center;color:var(--muted);border-right:1px solid var(--line)}.step:last-child{border:0}.step.active{color:var(--brand);font-weight:700}
        .panel{background:var(--surface);border:1px solid var(--line);padding:28px;border-radius:6px}h1{font-size:22px;margin:0 0 6px}p{color:var(--muted);margin:0 0 22px}
        label{display:block;font-weight:700;margin:14px 0 6px}input{width:100%;padding:11px;border:1px solid var(--line);border-radius:4px;font:inherit}
        .grid{display:grid;grid-template-columns:2fr 1fr;gap:14px}.warning{margin:20px 0;padding:14px;border:1px solid #e7b4b4;background:#fff2f2;color:var(--danger)}
        .check{display:flex;gap:10px;align-items:flex-start;font-weight:400}.check input{width:auto;margin-top:5px}
        button{margin-top:22px;border:0;border-radius:4px;background:var(--brand);color:#fff;font-weight:700;padding:12px 18px;cursor:pointer}
        .errors{padding:12px;background:#fff2f2;border:1px solid #e7b4b4;color:var(--danger);margin-bottom:16px}
        @media(max-width:600px){main{margin:20px auto}.panel{padding:20px}.grid{grid-template-columns:1fr}.steps{font-size:12px}}
    </style>
</head>
<body>
<main>
    <div class="brand">Art INPA Installation</div>
    <div class="steps">
        <div class="step {{ $step === 1 ? 'active' : '' }}">1. Platform</div>
        <div class="step {{ $step === 2 ? 'active' : '' }}">2. Database</div>
        <div class="step {{ $step === 3 ? 'active' : '' }}">3. Owner</div>
    </div>
    <section class="panel">
        @if($errors->any())<div class="errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @if($step === 1)
            <h1>Platform identity</h1><p>Set the public identity for this installation.</p>
            <form method="post" action="{{ route('install.platform.store') }}" enctype="multipart/form-data">@csrf
                <label for="name">Platform name</label><input id="name" name="name" value="{{ old('name') }}" required>
                <label for="domain">Domain</label><input id="domain" name="domain" type="url" placeholder="https://example.com" value="{{ old('domain') }}" required>
                <label for="logo">Logo</label><input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp">
                <button type="submit">Continue</button>
            </form>
        @elseif($step === 2)
            <h1>Database connection</h1><p>The connection is tested before any database operation.</p>
            <form method="post" action="{{ route('install.database.store') }}">@csrf
                <div class="grid"><div><label for="host">Database host / IP</label><input id="host" name="host" value="{{ old('host') }}" required></div>
                <div><label for="port">Port</label><input id="port" name="port" type="number" value="{{ old('port',3306) }}" required></div></div>
                <label for="database">Database name</label><input id="database" name="database" value="{{ old('database') }}" required>
                <label for="username">Database username</label><input id="username" name="username" value="{{ old('username') }}" required>
                <label for="password">Database password</label><input id="password" name="password" type="password">
                <div class="warning"><strong>Destructive operation:</strong> continuing will permanently delete every existing table in this database and install a fresh platform database.</div>
                <label class="check"><input name="erase_confirmation" type="checkbox" value="1" required><span>I understand and authorize deleting all existing database tables.</span></label>
                <button type="submit">Test connection and continue</button>
            </form>
        @else
            <h1>Super administrator</h1><p>Create the verified owner account with all platform permissions.</p>
            <form method="post" action="{{ route('install.finish') }}">@csrf
                <label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required>
                <label for="password">Password</label><input id="password" name="password" type="password" minlength="10" required>
                <label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" minlength="10" required>
                <button type="submit">Erase database and install</button>
            </form>
        @endif
    </section>
</main>
</body>
</html>
