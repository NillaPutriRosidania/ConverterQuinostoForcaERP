<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arsip File</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, Segoe UI, sans-serif
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            padding: 20px
        }

        .card {
            max-width: 560px;
            width: 100%;
            background: #f9fafb;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .45)
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px;
            color: #0f172a
        }

        .muted {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 14px
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin: 12px 0 6px;
            color: #111827
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-size: 13px
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px
        }

        .btn {
            margin-top: 16px;
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 800;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            color: #fff;
            cursor: pointer
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px
        }

        a {
            color: #2563eb;
            text-decoration: none;
            font-size: 12px
        }

        .hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px
        }

        .err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 12px;
            padding: 10px;
            font-size: 12px;
            margin-bottom: 12px
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

        .back-icon {
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="top">
            <a href="{{ route('welcome') }}">← Kembali</a>
            <a href="{{ route('archive.browse') }}">📁 Browse</a>
        </div>

        <h1>Arsip File</h1>
        <div class="muted">Upload file apa saja (detail Quinos, summary, mapping, dll).</div>

        @if ($errors->any())
            <div class="err">
                <b>Error:</b>
                <ul style="margin:6px 0 0 18px">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('archive.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <label>Cabang</label>
            <select name="branch" required id="branch">
                <option value="sq">SQ</option>
                <option value="gresik">Gresik</option>
                <option value="lantai12">Lantai 12</option>
            </select>

            <div class="row">
                <div>
                    <label>Tahun (opsional)</label>
                    <select name="year" id="year">
                        <option value="">-- Pilih Tahun --</option>
                        @php $y = (int)date('Y'); @endphp
                        @for ($i = $y - 2; $i <= $y + 1; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label>Bulan (opsional)</label>
                    <select name="month" class="form-select">
                        <option value="">-- Pilih Bulan --</option>
                        @foreach ($months ?? [] as $num => $label)
                            <option value="{{ $num }}" {{ old('month') == $num ? 'selected' : '' }}>
                                {{ $num }} - {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="hint">Kalau tahun/bulan kosong, file disimpan langsung di folder cabang.</div>

            <label>Upload File (bisa banyak)</label>
            <input type="file" name="files[]" multiple required>
            <div class="hint">Nama file otomatis ditambah timestamp supaya tidak ketimpa.</div>

            <button class="btn" type="submit">Simpan ke Arsip</button>
        </form>
    </div>
</body>

</html>
