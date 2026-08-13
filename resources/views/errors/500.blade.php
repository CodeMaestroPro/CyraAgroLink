<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Server Error | {{ config('cyra.brand', 'CyraAgroLink') }}</title>
    <style>
        :root { --forest:#0B3D2E; --soft:#E8F5E9; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; font-family:"Plus Jakarta Sans",system-ui,sans-serif;
            background: radial-gradient(circle at 50% 0%, #1a3d30, var(--forest) 50%, #041810); color:#fff; padding:1.5rem; }
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
        <h1>500</h1>
        <p>Something went wrong on our side. Please try again shortly.</p>
        <a href="{{ url('/') }}">Return home</a>
    </div>
</body>
</html>
