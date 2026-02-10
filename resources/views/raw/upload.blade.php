<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Perhitungan Bahan Baku</title>
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
            max-width: 520px;
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
            font-weight: 600;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            color: #fff;
            cursor: pointer;
        }

        .footnote {
            margin-top: 10px;
            font-size: 10px;
            color: #9ca3af;
            text-align: right;
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
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="card">
            <a href="{{ route('welcome') }}" class="back-btn">
                <span>←</span> Kembali
            </a>

            <h1>Perhitungan Bahan Baku</h1>
            <div class="subtitle">Upload file BOM (Excel) untuk menghitung total pemakaian bahan baku</div>

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

            <form action="{{ route('raw.process') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="field-group">
                    <input type="file" name="file" id="fileInput" class="file-input" accept=".xlsx,.xls" required>
                </div>

                <div id="filePreview" class="file-preview" style="display:none;"></div>

                <div class="btn-row">
                    <button type="submit">📊 Hitung Pemakaian</button>
                </div>

                <div class="footnote">
                    Pastikan file memiliki kolom: <b>PENYUSUN</b> & <b>HASIL (GRAMASI X PENJULAN)</b>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('filePreview');

        fileInput.addEventListener('change', () => {
            preview.innerHTML = '';
            const file = fileInput.files[0];

            if (!file) {
                preview.style.display = 'none';
                return;
            }

            preview.style.display = 'block';

            const item = document.createElement('div');
            item.className = 'file-item';

            const sizeKB = (file.size / 1024).toFixed(1);
            const sizeText = sizeKB > 1024 ? (sizeKB / 1024).toFixed(2) + ' MB' : sizeKB + ' KB';

            item.innerHTML = `
                <div class="file-name">📄 ${file.name}</div>
                <div class="file-size">${sizeText}</div>
            `;

            preview.appendChild(item);
        });
    </script>
</body>

</html>
