<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sales Upload</title>
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
            max-width: 480px;
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
            padding: 6px 8px;
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


    </style>
</head>

<body>
    <div class="wrapper">
        <div class="card">

            <h1>Sales Upload</h1>
            <div class="subtitle">Upload beberapa file transaksi sekaligus</div>

            <!-- FORM -->
            <form action="{{ route('sales.process') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="field-group">
                    <input type="file" name="files[]" id="fileInput" class="file-input" multiple accept=".csv,.txt"
                        required>
                </div>

                <!-- PREVIEW FILE -->
                <div id="filePreview" class="file-preview" style="display:none;"></div>

                <div class="btn-row">
                    <button type="submit">📤 Upload & Hitung Sales</button>
                </div>

                <div class="footnote">
                    File yang dipilih akan ditampilkan sebelum diproses.
                </div>

            </form>

        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('filePreview');

        fileInput.addEventListener('change', () => {
            preview.innerHTML = '';
            const files = fileInput.files;

            if (files.length === 0) {
                preview.style.display = 'none';
                return;
            }

            preview.style.display = 'block';

            Array.from(files).forEach(file => {
                const item = document.createElement('div');
                item.className = 'file-item';

                const sizeKB = (file.size / 1024).toFixed(1);
                const sizeText = sizeKB > 1024 ?
                    (sizeKB / 1024).toFixed(2) + ' MB' :
                    sizeKB + ' KB';

                item.innerHTML = `
                    <div class="file-name">📄 ${file.name}</div>
                    <div class="file-size">${sizeText}</div>
                `;

                preview.appendChild(item);
            });
        });
    </script>

</body>

</html>
