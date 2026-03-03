<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Revenue Journal (Per Invoice)</title>
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
        }

        .wrapper {
            width: 100%;
            max-width: 560px;
            padding: 16px;
        }

        .card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.45);
        }

        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }

        .subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 16px;
            line-height: 1.4;
        }

        .field-group {
            margin-bottom: 14px;
        }

        .file-input {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1px dashed #cbd5f5;
            background: #f3f4f6;
            font-size: 12px;
            cursor: pointer;
        }

        .file-preview {
            margin-top: 10px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 12px;
        }

        .file-item:not(:last-child) {
            border-bottom: 1px dashed #e5e7eb;
        }

        .file-name {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #111827;
        }

        .file-size {
            font-size: 11px;
            color: #6b7280;
        }

        .btn-row {
            margin-top: 14px;
            text-align: right;
        }

        button {
            border: none;
            border-radius: 999px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 700;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            color: #fff;
            cursor: pointer;
        }

        .footnote {
            margin-top: 10px;
            font-size: 10px;
            color: #9ca3af;
            text-align: right;
            line-height: 1.4;
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

        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .hint {
            margin-top: 10px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            font-size: 12px;
            color: #334155;
            line-height: 1.45;
        }

        .hint b {
            color: #0f172a;
        }

        .chips {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .chip {
            font-size: 11px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="card">
            <a href="{{ route('welcome') }}" class="back-btn"><span>←</span> Kembali</a>

            <h1>Revenue Journal (Per Invoice)</h1>
            <div class="subtitle">
                Upload hasil mapping (CSV / XLSX). Sistem akan menghitung jurnal per invoice (Penjualan, Diskon, SC,
                PB10, PB0, dll),
                tampilkan hasil, lalu bisa kamu download.
            </div>

            @if ($errors->any())
                <div class="error">
                    <b>Terjadi error:</b>
                    <ul style="margin:8px 0 0 18px;">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('revenue_journal.run') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="field-group">
                    <input type="file" name="file_mapping[]" id="fileInput" class="file-input"
                        accept=".csv,.txt,.xlsx" multiple required>
                </div>

                <div id="filePreview" class="file-preview" style="display:none;"></div>

                <div class="btn-row">
                    <button type="submit">🧾 Hitung Jurnal</button>
                </div>

                <div class="hint">
                    <b>Kolom yang wajib ada</b> (di mapping):
                    <div class="chips">
                        <span class="chip">FORCA_POSID</span>
                        <span class="chip">total_discount</span>
                        <span class="chip">FORCA_ServiceCharge</span>
                        <span class="chip">FORCA_RoundingAmt</span>
                        <span class="chip">FORCA_ImportSalesPOSLine&gt;QtyOrdered</span>
                        <span class="chip">FORCA_ImportSalesPOSLine&gt;PriceActual</span>
                        <span class="chip">FORCA_ImportSalesPOSLine&gt;C_Tax_ID[Name]</span>
                    </div>
                    <div style="margin-top:8px;">
                        <b>Optional:</b> <span class="chip">FORCA_TotalDiscNoTax</span> (kalau ada diskon PB1 0%).
                    </div>
                </div>

                <div class="footnote">
                    Tips: kalau mapping kamu per transaksi ada di 3 sheet, upload 3 file (Sheet 1/2/3) sekaligus.
                </div>
            </form>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('filePreview');

        function humanSize(bytes) {
            const kb = bytes / 1024;
            if (kb <= 1024) return kb.toFixed(1) + ' KB';
            return (kb / 1024).toFixed(2) + ' MB';
        }

        fileInput.addEventListener('change', () => {
            preview.innerHTML = '';
            const files = Array.from(fileInput.files || []);

            if (!files.length) {
                preview.style.display = 'none';
                return;
            }

            preview.style.display = 'block';

            files.forEach((file) => {
                const item = document.createElement('div');
                item.className = 'file-item';
                item.innerHTML = `
                <div class="file-name">📄 ${file.name}</div>
                <div class="file-size">${humanSize(file.size)}</div>
            `;
                preview.appendChild(item);
            });
        });
    </script>
</body>

</html>
