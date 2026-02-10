<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Hasil Pemakaian Bahan Baku</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            padding: 16px;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }

        .wrap {
            max-width: 1000px;
            margin: 0 auto;
        }

        .card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .45);
        }

        .top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        h1 {
            font-size: 18px;
            margin: 0;
        }

        .sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .btns {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        a.btn {
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .12);
            color: #0f172a;
        }

        .acc {
            margin-top: 14px;
        }

        details {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        summary {
            cursor: pointer;
            list-style: none;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        summary::-webkit-details-marker {
            display: none;
        }

        .badge {
            font-variant-numeric: tabular-nums;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 12px;
        }

        .content {
            padding: 0 14px 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
        }

        th {
            text-align: left;
            background: #f8fafc;
        }

        td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="top">
                <div>
                    <h1>Hasil Pemakaian Bahan Baku</h1>
                    <div class="sub">Klik bahan baku untuk melihat finish good yang memakai (gramasi, penjualan,
                        hasil).</div>
                </div>
                <div class="btns">
                    <a class="btn" href="{{ route('raw.upload') }}">← Upload Lagi</a>
                    <a class="btn" href="{{ route('raw.download') }}">⬇️ Download Excel</a>
                    <a class="btn" href="{{ route('welcome') }}">🏠 Home</a>
                </div>
            </div>

            @php
                $totals = $totals ?? [];
                $details = $details ?? [];
            @endphp

            @if (empty($totals))
                <p class="muted" style="margin-top:12px;">Data kosong. Upload file BOM terlebih dulu.</p>
            @else
                <div class="acc">
                    @foreach ($totals as $bahan => $total)
                        <details>
                            <summary>
                                <div>
                                    <b>{{ $bahan }}</b>
                                    <div class="muted">{{ count($details[$bahan] ?? []) }} baris pemakaian</div>
                                </div>
                                <div class="badge">Total: {{ number_format($total, 2, ',', '.') }}</div>
                            </summary>

                            <div class="content">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">No</th>
                                            <th>Finish Good</th>
                                            <th style="width:140px; text-align:right;">Gramasi</th>
                                            <th style="width:140px; text-align:right;">Penjualan</th>
                                            <th style="width:180px; text-align:right;">Hasil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($details[$bahan] ?? [] as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['finish_good'] }}</td>
                                                <td class="num">{{ number_format($row['gramasi'], 2, ',', '.') }}
                                                </td>
                                                <td class="num">{{ number_format($row['penjualan'], 0, ',', '.') }}
                                                </td>
                                                <td class="num">{{ number_format($row['hasil'], 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>

</html>
