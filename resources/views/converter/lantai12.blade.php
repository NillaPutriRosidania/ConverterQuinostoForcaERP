<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Quinos Converter – Lantai 12</title>
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
            background: #e0f2fe;
            color: #0369a1;
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
        }

        .errors ul {
            margin: 0;
            padding-left: 18px;
        }

        .footnote {
            margin-top: 10px;
            font-size: 10px;
            color: #9ca3af;
            text-align: right;
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
                    <div class="logo-dot">Q</div>
                    <div>
                        <h1>Quinos Converter – Lantai 12</h1>
                        <div class="subtitle">
                            Upload 1 file Quinos Lantai 12 → auto mapping ke FORCA
                        </div>
                        <div class="badge">
                            <span class="badge-dot"></span>
                            Aktif Lantai 12
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

            <form action="{{ route('converter.lantai12.run') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="field-group">
                    <div class="field-label">
                        <span>File Transaksi Quinos</span>
                        <span class="helper">CSV / TXT</span>
                    </div>
                    <input type="file" name="file_transaksi" accept=".csv,.txt" required class="file-input">
                    <div class="file-hint">
                        Berisi Trx Code, Date, Name, Qty, Price, Discount, Service, Rounding, dll.
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit">
                        <span class="btn-icon">⚙️</span>
                        Convert &amp; Download CSV
                    </button>
                </div>

                <div class="footnote">
                    File hasil akan otomatis ter-download dalam format template FORCA POS.
                </div>
            </form>

        </div>
    </div>
</body>

</html>
