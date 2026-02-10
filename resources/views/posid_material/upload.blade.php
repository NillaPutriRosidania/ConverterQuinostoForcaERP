<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Upload Mapping - Cari POS ID</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e293b)
        }

        .wrap {
            width: 100%;
            max-width: 560px;
            padding: 16px
        }

        .card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .45)
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px
        }

        .sub {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 16px;
            line-height: 1.4
        }

        .field {
            margin-bottom: 14px
        }

        label {
            display: block;
            font-size: 12px;
            color: #374151;
            margin-bottom: 6px;
            font-weight: 700
        }

        input[type=file] {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1px dashed #cbd5f5;
            background: #f3f4f6;
            font-size: 12px
        }

        button {
            border: none;
            border-radius: 999px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 700;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            color: #fff;
            cursor: pointer
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            gap: 10px
        }

        a {
            font-size: 12px;
            color: #2563eb;
            text-decoration: none
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <h1>Upload Mapping (CSV) untuk Cari POS ID</h1>
            <div class="sub">
                Upload file hasil mapping Quinos → FORCA. Setelah itu kamu bisa cari POS ID yang menggunakan / tidak
                menggunakan bahan baku tertentu.
            </div>

            <form action="{{ route('posidMaterial.process') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label>File Mapping (wajib, bisa lebih dari 1)</label>
                    <input type="file" name="files[]" multiple accept=".csv,.txt" required>
                </div>

                {{-- <div class="field">
                    <label>BOM Excel (opsional)</label>
                    <input type="file" name="bom" accept=".xlsx,.xls">
                </div> --}}

                <div class="row">
                    <a href="{{ route('welcome') }}">← Kembali</a>
                    <button type="submit">Lanjut → Search</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
