<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sales Result</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #0f172a;
        }

        .wrapper {
            width: 100%;
            max-width: 640px;
            padding: 16px;
        }

        .card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 24px 24px 20px;
            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.45),
                0 0 0 1px rgba(148, 163, 184, 0.25);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.18),
                    transparent 55%);
            pointer-events: none;
        }

        h2 {
            margin: 0 0 14px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            position: relative;
            z-index: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            position: relative;
            z-index: 1;
        }

        thead th {
            text-align: left;
            padding: 10px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            font-size: 12px;
            border-bottom: 1px solid #bae6fd;
        }

        tbody td {
            padding: 10px;
            border-bottom: 1px dashed #e5e7eb;
            color: #111827;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f1f5f9;
        }

        .qty {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }

        .footer-row {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(15, 23, 42, 0.07);
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            border: 1px solid rgba(15, 23, 42, 0.12);
            transition: 0.15s ease;
        }

        .back-link:hover {
            background: rgba(15, 23, 42, 0.12);
            transform: translateX(-2px);
        }

        .note {
            font-size: 10px;
            color: #9ca3af;
        }

        .btn-row {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #64748b, #475569);
            box-shadow: 0 10px 22px rgba(71, 85, 105, 0.35);
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="card">

            <h2>Jumlah Penjualan per Produk</h2>

            <table>
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th style="text-align:right;">Total Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $product => $total)
                        <tr>
                            <td>{{ $product }}</td>
                            <td class="qty">{{ number_format((int) $total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <!-- FOOTER (DITAMBAH 1 BUTTON) -->
            <div class="footer-row">
                <a href="{{ route('sales.upload') }}" class="back-link">
                    ← Upload ulang
                </a>

                <form action="{{ route('sales.convertBom') }}" method="POST">
                    @csrf
                    <button type="submit" class="back-link">
                        🔄 Convert ke BOM
                    </button>
                </form>


            </div>

        </div>
    </div>
</body>


</html>
