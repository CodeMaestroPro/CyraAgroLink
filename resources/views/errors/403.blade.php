<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Access Denied | {{ config('cyra.brand', 'CyraAgroLink') }}</title>
    <style>
        :root { --forest:#0B3D2E; --mint:#1F7A5C; --soft:#E8F5E9; --ink:#0F1F1A; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; font-family:"Plus Jakarta Sans",system-ui,sans-serif;
            background: radial-gradient(circle at 20% 20%, #134a38, var(--forest) 55%, #06241a); color:#fff; padding:1.5rem; }
        .card { max-width:28rem; text-align:center; }
        h1 { font-size:clamp(2.5rem,6vw,3.5rem); margin:0 0 .5rem; letter-spacing:-.03em; }
        p { color:rgba(255,255,255,.78); line-height:1.6; margin:0 0 1.5rem; }
        a { display:inline-flex; align-items:center; justify-content:center; padding:.75rem 1.25rem; border-radius:.75rem;
            background:var(--soft); color:var(--forest); font-weight:700; text-decoration:none; }
        a:hover { background:#fff; }
    </style>
</head>
<body>
    <div class="card">
        <h1>403</h1>
        <p>You do not have permission to access this resource.</p>
        <a href="{{ url('/') }}">Return home</a>
    </div>
</body>
</html>
