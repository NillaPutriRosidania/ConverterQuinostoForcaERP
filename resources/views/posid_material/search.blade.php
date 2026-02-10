<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cari POS ID - Bahan Baku</title>
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
            max-width: 760px;
            padding: 16px
        }

        .card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .45)
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px
        }

        .sub {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 14px
        }

        .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin: 10px 0
        }

        .pill {
            padding: 8px 10px;
            border-radius: 10px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            font-size: 12px;
            color: #1e3a8a;
            font-weight: 700
        }

        label {
            font-size: 12px;
            color: #374151;
            font-weight: 700
        }

        input[type=text] {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #e5e7eb
        }

        select {
            width: 100%;
            min-height: 220px;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #e5e7eb
        }

        .actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-top: 14px
        }

        button {
            border: none;
            border-radius: 999px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 800;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            color: #fff;
            cursor: pointer
        }

        a {
            font-size: 12px;
            color: #2563eb;
            text-decoration: none
        }

        .mode {
            display: flex;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap
        }

        .mode label {
            font-weight: 700
        }

        .hint {
            font-size: 11px;
            color: #6b7280
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <h1>Cari POS ID Berdasarkan Bahan Baku</h1>
            <div class="sub">
                Pilih mode <b>menggunakan</b> / <b>tidak menggunakan</b>. Kamu bisa pilih bahan baku lebih dari 1 (CTRL
                / CMD + klik).
            </div>

            <div class="row">
                <span class="pill">Total bahan baku terdata: {{ count($ingredients) }}</span>
            </div>

            <form action="{{ route('posidMaterial.result') }}" method="GET">
                <div class="row mode">
                    <label>Mode:</label>
                    <label><input type="radio" name="mode" value="using" checked> Menggunakan</label>
                    <label><input type="radio" name="mode" value="not_using"> Tidak menggunakan</label>
                    <span class="hint">Menggunakan = wajib ada semua yang dipilih (AND). Tidak menggunakan = tidak
                        boleh ada satupun.</span>
                </div>

                <div class="row">
                    <div style="width:100%">
                        <label>Cari cepat bahan baku (filter list)</label>
                        <input type="text" id="filterBox" placeholder="contoh: Egg / Susu / Cup / Syrup ...">
                    </div>
                </div>

                <div class="row">
                    <div style="width:100%">
                        <label>Pilih bahan baku (multi)</label>
                        <select name="ingredients[]" id="ingredientSelect" multiple>
                            @foreach ($ingredients as $ing)
                                <option value="{{ $ing }}">{{ $ing }}</option>
                            @endforeach
                        </select>
                        <div class="hint">Tip: ketik di search box untuk menyaring, lalu pilih beberapa item.</div>
                    </div>
                </div>

                <div class="actions">
                    <a href="{{ route('posidMaterial.upload') }}">← Upload ulang</a>
                    <button type="submit">🔎 Cari POS ID</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const filterBox = document.getElementById('filterBox');
        const select = document.getElementById('ingredientSelect');

        filterBox.addEventListener('input', () => {
            const q = filterBox.value.toLowerCase();
            [...select.options].forEach(opt => {
                opt.hidden = q && !opt.text.toLowerCase().includes(q);
            });
        });
    </script>
</body>

</html>
