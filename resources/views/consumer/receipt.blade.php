<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $reference }} — {{ $brand }}</title>
    <style>
        :root {
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #d1d5db;
            --forest: #10853f;
            --soft: #e8f5e9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f4f6;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1rem;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .toolbar h1 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border-radius: 0.55rem;
            padding: 0.55rem 0.9rem;
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: var(--forest);
            color: #fff;
        }

        .btn-secondary {
            background: #fff;
            color: var(--ink);
            border-color: var(--line);
        }

        .sheet-wrap {
            padding: 1.25rem 1rem 2rem;
        }

        .sheet {
            width: min(720px, 100%);
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 0.85rem;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--forest);
        }

        .brand {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--forest);
            letter-spacing: -0.02em;
        }

        .meta {
            color: var(--muted);
            font-size: 0.8rem;
            margin-top: 0.35rem;
            line-height: 1.45;
        }

        .qr-block {
            text-align: center;
            min-width: 150px;
        }

        .qr-block svg {
            width: 140px;
            height: 140px;
            display: block;
            margin: 0 auto;
            border: 1px solid var(--line);
            border-radius: 0.5rem;
            padding: 0.35rem;
            background: #fff;
        }

        .qr-caption {
            margin-top: 0.4rem;
            font-size: 0.7rem;
            color: var(--muted);
            max-width: 150px;
            line-height: 1.3;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem 1.25rem;
            margin: 1.15rem 0;
        }

        .label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.2rem;
        }

        .value {
            font-size: 0.95rem;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.35rem;
        }

        th, td {
            padding: 0.65rem 0.4rem;
            border-bottom: 1px solid var(--line);
            font-size: 0.875rem;
            text-align: left;
        }

        th {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
        }

        td.num, th.num { text-align: right; }

        .totals {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
        }

        .total-box {
            min-width: 220px;
            background: var(--soft);
            border: 1px solid #b8e0c4;
            border-radius: 0.65rem;
            padding: 0.85rem 1rem;
        }

        .total-box .row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .total-box .grand {
            margin-top: 0.45rem;
            padding-top: 0.45rem;
            border-top: 1px dashed #86b894;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--forest);
        }

        .note {
            margin-top: 1rem;
            padding: 0.75rem 0.9rem;
            background: #f9fafb;
            border-radius: 0.55rem;
            border: 1px solid var(--line);
            font-size: 0.85rem;
            color: var(--muted);
        }

        .footer {
            margin-top: 1.35rem;
            padding-top: 0.9rem;
            border-top: 1px solid var(--line);
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.45;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: var(--soft);
            color: var(--forest);
        }

        .badge.is-pending { background: #fff7ed; color: #b45309; }
        .badge.is-cancelled { background: #fff1f2; color: #be123c; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet-wrap { padding: 0; }
            .sheet {
                width: 100%;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
            }
            .qr-block svg {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 640px) {
            .grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1>Order receipt · {{ $reference }}</h1>
        <div class="actions">
            <a href="{{ route('consumer.marketplace', ['view' => 'orders']) }}" class="btn btn-secondary">Back to orders</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">Print receipt</button>
        </div>
    </div>

    <div class="sheet-wrap">
        <article class="sheet" aria-label="Printable receipt">
            <header class="header">
                <div>
                    <div class="brand">{{ $brand }}</div>
                    <div class="meta">
                        {{ $company }} · Consumer Marketplace<br>
                        Official purchase receipt
                    </div>
                </div>
                <div class="qr-block">
                    {!! $qr_svg !!}
                    <p class="qr-caption">Scan to verify this receipt</p>
                </div>
            </header>

            <div class="grid">
                <div>
                    <span class="label">Receipt</span>
                    <div class="value">{{ $reference }}</div>
                </div>
                <div>
                    <span class="label">Status</span>
                    <div class="value">
                        <span @class([
                            'badge',
                            'is-pending' => $order->status === 'pending',
                            'is-cancelled' => $order->status === 'cancelled',
                        ])>
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                </div>
                <div>
                    <span class="label">Customer</span>
                    <div class="value">{{ $buyer_name }}</div>
                    <div class="meta">{{ $buyer_email }}</div>
                </div>
                <div>
                    <span class="label">Order date</span>
                    <div class="value">{{ $order->created_at?->format('d M Y, H:i') }}</div>
                    <div class="meta">Printed {{ $issued_at }}</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="num">Qty</th>
                        <th class="num">Unit</th>
                        <th class="num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $line)
                        <tr>
                            <td>{{ $line->product_name }}</td>
                            <td class="num">{{ number_format($line->quantity) }} {{ $line->unit }}</td>
                            <td class="num">₦{{ number_format($line->unit_price) }}</td>
                            <td class="num">₦{{ number_format($line->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="total-box">
                    <div class="row">
                        <span>Items</span>
                        <span>{{ $order->items->count() }}</span>
                    </div>
                    <div class="row grand">
                        <span>Total paid</span>
                        <span>{{ $order->formattedTotal() }}</span>
                    </div>
                </div>
            </div>

            @if ($order->delivery_note)
                <div class="note">
                    <strong>Delivery note:</strong> {{ $order->delivery_note }}
                </div>
            @endif

            <footer class="footer">
                Verify online: {{ $verification_url }}<br>
                Thank you for shopping with {{ $brand }}. Keep this receipt for your records.
            </footer>
        </article>
    </div>
</body>
</html>
