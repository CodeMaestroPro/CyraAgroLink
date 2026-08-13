<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verified receipt {{ $reference }} — {{ $brand }}</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        .card {
            width: min(560px, calc(100% - 2rem));
            margin: 2rem auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 0.9rem;
            padding: 1.35rem 1.4rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .ok {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 999px;
            background: #e8f5e9;
            color: #10853f;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.3rem 0.7rem;
        }
        h1 {
            margin: 0.75rem 0 0.25rem;
            font-size: 1.35rem;
        }
        .muted { color: #6b7280; font-size: 0.9rem; }
        ul {
            margin: 1rem 0 0;
            padding: 0;
            list-style: none;
        }
        li {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }
        .total {
            margin-top: 1rem;
            font-size: 1.1rem;
            font-weight: 800;
            color: #10853f;
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="ok">✓ Verified receipt</span>
        <h1>{{ $reference }}</h1>
        <p class="muted">
            {{ $brand }} · {{ $company }}<br>
            Order #{{ $order->id }} · {{ ucfirst($order->status) }} ·
            {{ $order->created_at?->format('d M Y, H:i') }}
        </p>

        <ul>
            @foreach ($order->items as $line)
                <li>
                    <span>{{ $line->product_name }} · {{ number_format($line->quantity) }} {{ $line->unit }}</span>
                    <strong>₦{{ number_format($line->line_total) }}</strong>
                </li>
            @endforeach
        </ul>

        <p class="total">Total {{ $order->formattedTotal() }}</p>
        <p class="muted">This QR code matches an authentic {{ $brand }} consumer marketplace receipt.</p>
    </main>
</body>
</html>
