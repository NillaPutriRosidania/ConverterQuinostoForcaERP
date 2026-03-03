<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Hasil Revenue Journal</title>
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
            max-width: 1100px;
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
            line-height: 1.4;
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
            font-weight: 800;
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
            font-weight: 900;
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

        .kpi {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .kpi .box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
        }

        .kpi .label {
            font-size: 11px;
            color: #6b7280;
        }

        .kpi .val {
            margin-top: 6px;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 900px) {
            .kpi {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="top">
                <div>
                    <h1>Hasil Revenue Journal</h1>
                    <div class="sub">
                        Klik invoice untuk melihat detail jurnal (Penjualan, Diskon, SC, PB10, PB0, Diskon tanpa pajak,
                        Total akhir).
                    </div>
                </div>
                <div class="btns">
                    <a class="btn" href="{{ route('revenue_journal.upload') }}">← Upload Lagi</a>
                    {{-- tombol download ini opsional. aktifkan kalau kamu simpan hasil di session / cache --}}
                    @if (!empty($downloadUrl ?? ''))
                        <a class="btn" href="{{ $downloadUrl }}">⬇️ Download</a>
                    @else
                        <a class="btn" href="{{ route('revenue_journal.download') }}">⬇️ Download</a>
                    @endif
                    <a class="btn" href="{{ route('welcome') }}">🏠 Home</a>
                </div>
            </div>

            @php
                $rows = $rows ?? []; // array of invoice journals (kesamping)
            @endphp

            @if (empty($rows))
                <p class="muted" style="margin-top:12px;">Data kosong. Upload mapping terlebih dulu.</p>
            @else
                @php
                    // KPI ringkas (opsional)
                    $countInv = count($rows);
                    $sumSales10 = 0;
                    $sumPB0 = 0;
                    $sumTotalAkhir = 0;
                    $sumDiscNoTax = 0;
                    foreach ($rows as $r) {
                        $sumSales10 += (float) ($r['Penjualan'] ?? 0);
                        $sumPB0 += (float) ($r['Total produk PB1 0%'] ?? 0);
                        $sumTotalAkhir += (float) ($r['Total akhir'] ?? 0);
                        $sumDiscNoTax += (float) ($r['Diskon tanpa pajak (PB1 0%)'] ?? 0); // already negative
                    }
                @endphp

                <div class="kpi">
                    <div class="box">
                        <div class="label">Jumlah Invoice</div>
                        <div class="val">{{ number_format($countInv, 0, ',', '.') }}</div>
                    </div>
                    <div class="box">
                        <div class="label">Total Penjualan (PB1 10%)</div>
                        <div class="val">{{ number_format($sumSales10, 0, ',', '.') }}</div>
                    </div>
                    <div class="box">
                        <div class="label">Total Produk (PB1 0%)</div>
                        <div class="val">{{ number_format($sumPB0, 0, ',', '.') }}</div>
                    </div>
                    <div class="box">
                        <div class="label">Total Akhir (All)</div>
                        <div class="val">{{ number_format($sumTotalAkhir, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="acc">
                    @foreach ($rows as $r)
                        @php
                            $inv = $r['Invoice'] ?? '';
                            $totalAkhir = (float) ($r['Total akhir'] ?? 0);

                            // bikin "baris" jurnal seperti contoh (kiri=label, tengah=amount, kanan=note)
                            $lines = [
                                ['Penjualan', (float) ($r['Penjualan'] ?? 0), 'total produk PB1 10%'],
                                ['Diskon', (float) ($r['Diskon'] ?? 0), 'Charge Diskon pb 10%'],
                                ['Total Produk', (float) ($r['Total Produk'] ?? 0), 'penjualan - diskon'],
                                ['PB 10% Produk', (float) ($r['PB 10% Produk'] ?? 0), '10% * total produk'],
                                ['(SC) Hutang Karyawan 4%', (float) ($r['(SC) Hutang Karyawan 4%'] ?? 0), 'Charge SC'],
                                [
                                    '(SC) Hutang Accrue Lain 1%',
                                    (float) ($r['(SC) Hutang Accrue Lain 1%'] ?? 0),
                                    'Charge Accrue',
                                ],
                                ['Total SC', (float) ($r['Total SC'] ?? 0), 'charge sc + charge accrue'],
                                ['PB 10% Service Charge', (float) ($r['PB 10% Service Charge'] ?? 0), '10% * total sc'],
                                ['Total PB 10%', (float) ($r['Total PB 10%'] ?? 0), 'pb 10% produk + pb 10% sc'],
                                ['Rounding', (float) ($r['Rounding'] ?? 0), ''],
                                [
                                    '',
                                    (float) ($r['Subtotal PB10 Side'] ?? 0),
                                    'Total Produk + SC + Total PB 10% + Rounding',
                                ],
                                ['', (float) ($r['Total produk PB1 0%'] ?? 0), 'Total produk PB1 0%'],
                                ['', (float) ($r['Grand Before DiscNoTax'] ?? 0), ''],
                                [
                                    '',
                                    (float) ($r['Diskon tanpa pajak (PB1 0%)'] ?? 0),
                                    'diskon tanpa pajak (diskon pada produk yang pb1 0%)',
                                ],
                                ['', (float) ($r['Total akhir'] ?? 0), 'Total akhir'],
                            ];
                        @endphp

                        <details>
                            <summary>
                                <div>
                                    <b>Invoice #{{ $inv }}</b>
                                    <div class="muted">Klik untuk lihat jurnal</div>
                                </div>
                                <div class="badge">Total Akhir: {{ number_format($totalAkhir, 0, ',', '.') }}</div>
                            </summary>

                            <div class="content">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:260px;">Akun / Keterangan</th>
                                            <th style="width:180px; text-align:right;">Nominal</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lines as $line)
                                            <tr>
                                                <td>{{ $line[0] }}</td>
                                                <td class="num">{{ number_format($line[1], 2, ',', '.') }}</td>
                                                <td class="muted">{{ $line[2] }}</td>
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
