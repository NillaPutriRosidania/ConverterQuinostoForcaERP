<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Perhitungan Pendapatan</title>
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
            max-width: 480px;
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
                    rgba(59, 130, 246, 0.2),
                    transparent 55%);
            pointer-events: none;
        }

        .back-btn {
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
            margin-bottom: 14px;
            transition: 0.15s ease;
            position: relative;
            z-index: 1;
        }

        .back-btn:hover {
            background: rgba(15, 23, 42, 0.12);
            transform: translateX(-2px);
        }

        .card-header {
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .logo-dot {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f9fafb;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 0 0 3px rgba(191, 219, 254, 0.7);
        }

        h1 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }

        .subtitle {
            font-size: 12px;
            color: #6b7280;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #ecfeff;
            color: #155e75;
            margin-top: 6px;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #22c55e;
        }

        form {
            position: relative;
            z-index: 1;
        }

        .field-group {
            margin-bottom: 16px;
        }

        .field-label {
            font-size: 12px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .field-label span.helper {
            font-weight: 400;
            font-size: 11px;
            color: #9ca3af;
        }

        .file-input {
            width: 100%;
            padding: 8px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            background: #f3f4f6;
            font-size: 12px;
        }

        .file-input:hover {
            background: #e5e7eb;
        }

        .file-hint {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 2px;
            line-height: 1.35;
        }

        .btn-row {
            margin-top: 8px;
            display: flex;
            justify-content: flex-end;
        }

        button[type="submit"] {
            border: none;
            border-radius: 999px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            color: #f9fafb;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.3);
            transition:
                transform 0.08s ease-out,
                box-shadow 0.08s ease-out,
                filter 0.1s ease-out;
        }

        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(37, 99, 235, 0.38);
            filter: brightness(1.03);
        }

        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.28);
            filter: brightness(0.98);
        }

        .btn-icon {
            font-size: 14px;
        }

        .errors {
            background: #fef2f2;
            border-radius: 10px;
            padding: 8px 10px;
            margin-bottom: 14px;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 11px;
            position: relative;
            z-index: 1;
        }

        .errors ul {
            margin: 0;
            padding-left: 18px;
        }

        .rules {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.35);
            font-size: 11px;
            color: #334155;
            position: relative;
            z-index: 1;
            line-height: 1.45;
        }

        .rules strong {
            color: #0f172a;
        }

        .footnote {
            margin-top: 10px;
            font-size: 10px;
            color: #9ca3af;
            text-align: right;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 480px) {
            .card {
                padding: 20px 18px 16px;
            }

            h1 {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="card">
            <a href="{{ route('welcome') }}" class="back-btn">
                ← Kembali
            </a>

            <div class="card-header">
                <div class="title-row">
                    <div class="logo-dot">Rp</div>
                    <div>
                        <h1>Perhitungan Pendapatan</h1>
                        <div class="subtitle">Upload hasil Mapping CSV → download Summary Revenue per Invoice</div>
                        <div class="badge">
                            <span class="badge-dot"></span>
                            Output: Summary (Subtotal, Discount, Net Sales, Svc, Tax, Rounding, Total)
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="errors">
                    <strong>Terjadi kesalahan:</strong>
                    <ul>
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('revenue.run') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="field-group">
                    <div class="field-label">
                        <span>File Mapping (CSV)</span>
                        <span class="helper">Hasil convert FORCA (.csv)</span>
                    </div>
                    <input type="file" name="file_mapping" accept=".csv,.txt" required class="file-input">
                    <div class="file-hint">
                        File yang berisi kolom: FORCA_POSID, Total Discount, Service Charge, Rounding,
                        QtyOrdered, PriceActual, C_Tax_ID[Name].
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit">
                        <span class="btn-icon">⬇️</span>
                        <span>Proses &amp; Download Summary</span>
                    </button>
                </div>

                <div class="rules">
                    <strong>Rule perhitungan:</strong><br>
                    Subtotal = Σ(Qty × Price) per invoice<br>
                    Net Sales = Subtotal − Discount<br>
                    Tax = Σ(PB1% × Qty × Price) + (Service Charge × 10%)<br>
                    Total = Net Sales + Service Charge + Tax + Rounding
                </div>

                <div class="footnote">
                    Setelah diproses, file Summary akan otomatis ter-download.
                </div>
            </form>
        </div>
    </div>
</body>

</html>
