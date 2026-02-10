<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Hasil Cari POS ID</title>
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
            max-width: 980px;
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
            margin-bottom: 12px;
            line-height: 1.5
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px
        }

        thead th {
            background: #e0f2fe;
            color: #0369a1;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #bae6fd
        }

        tbody td {
            padding: 10px;
            border-bottom: 1px dashed #e5e7eb;
            vertical-align: top
        }

        .pos {
            font-weight: 800;
            color: #0f172a
        }

        .meta {
            font-size: 11px;
            color: #6b7280
        }

        .tag {
            display: inline-block;
            margin: 2px 6px 0 0;
            padding: 3px 8px;
            border-radius: 999px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            font-size: 11px
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px
        }

        a {
            font-size: 12px;
            color: #2563eb;
            text-decoration: none
        }

        .empty {
            padding: 12px;
            border-radius: 12px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 12px
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <h1>Hasil Pencarian POS ID</h1>
            <div class="sub">
                Mode: <b>{{ $mode === 'using' ? 'Menggunakan' : 'Tidak menggunakan' }}</b><br>
                Filter bahan baku:
                @if (count($selected))
                    @foreach ($selected as $s)
                        <span class="tag">{{ $s }}</span>
                    @endforeach
                @else
                    <i>(tidak ada filter — tampil semua)</i>
                @endif
                <br>
                Total POSID di data: <b>{{ number_format($total_posid) }}</b> • Hasil match:
                <b>{{ number_format(count($results)) }}</b>
            </div>

            @if (empty($results))
                <div class="empty">
                    Tidak ada POS ID yang cocok dengan filter kamu.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:180px;">POS ID</th>
                            <th>Ringkasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results as $r)
                            <tr>
                                <td class="pos">{{ $r['posid'] }}</td>
                                <td>
                                    <div class="meta">
                                        Produk unik: <b>{{ $r['product_count'] }}</b> • Total bahan terdeteksi:
                                        <b>{{ $r['ingredient_count'] }}</b>
                                    </div>
                                    <div class="meta" style="margin-top:6px;">
                                        Top produk:
                                        @foreach ($r['top_products'] as $pid => $qty)
                                            <span class="tag">{{ $pid }} ({{ number_format($qty) }})</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="actions">
                <a href="{{ route('posidMaterial.search') }}">← Kembali ke Search</a>

                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="{{ route('posidMaterial.export', ['mode' => $mode, 'ingredients' => $selected]) }}">
                        ⬇️ Download Excel
                    </a>
                    <a href="{{ route('posidMaterial.upload') }}">Upload ulang</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
