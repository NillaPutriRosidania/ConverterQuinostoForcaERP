<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Hasil Rekap Produk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            padding: 24px;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #0f172a;
        }

        .wrapper {
            width: 100%;
            max-width: 1900px;
            margin: 0 auto;
        }

        .card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.45),
                0 0 0 1px rgba(148, 163, 184, 0.25);
        }

        h2 {
            margin: 0 0 8px;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .subtitle {
            margin-bottom: 16px;
            color: #64748b;
            font-size: 13px;
        }

        .table-wrap {
            overflow: auto;
            border-radius: 16px;
            border: 1px solid #dbeafe;
            background: white;
            margin-top: 12px;
        }

        table {
            width: 100%;
            min-width: 1500px;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            background: #e0f2fe;
            color: #0369a1;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #bae6fd;
            white-space: nowrap;
        }

        tbody td,
        tfoot td {
            padding: 10px;
            border-bottom: 1px dashed #e5e7eb;
            background: #fff;
        }

        td.num,
        th.num {
            text-align: right;
        }

        td.center,
        th.center {
            text-align: center;
        }

        tfoot td {
            background: #dbeafe;
            font-weight: 800;
            border-top: 2px solid #93c5fd;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .btn {
            border: none;
            cursor: pointer;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
        }

        .btn-secondary {
            background: rgba(15, 23, 42, 0.07);
            color: #334155;
            border: 1px solid rgba(15, 23, 42, 0.12);
        }
    </style>
</head>

<body>
    <div class="wrapper">

        <div class="card">
            <h2>Rekap Produk</h2>
            <div class="subtitle">
                Produk direkap berdasarkan nama produk dan harga input. Jika nama sama tapi harga berbeda, akan dipisah.
            </div>
            <div class="actions">
                <form method="POST" action="{{ route('rekap.products.download') }}">
                    @csrf
                    <input type="hidden" name="groupedRows" value='@json($groupedRows)'>
                    <input type="hidden" name="detailRows" value='@json($detailRows)'>

                    <button type="submit" class="btn btn-primary">
                        ⬇️ Download Excel
                    </button>
                </form>

                <a href="{{ route('rekap.products.index') }}" class="btn btn-secondary">
                    ← Kembali ke Input
                </a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="center">No</th>
                            <th>Nama Produk</th>
                            <th class="num">Harga Input</th>
                            <th class="num">Unit Price</th>
                            <th class="num">Net Sales</th>
                            <th class="num">Diskon</th>
                            <th class="num">Pajak</th>
                            <th class="num">SC</th>
                            <th class="num">Subtotal</th>
                            <th class="num">Total Qty</th>
                            <th class="num">Total Harga Input</th>
                            <th class="num">Total Unit Price</th>
                            <th class="num">Total Net Sales</th>
                            <th class="num">Total Diskon</th>
                            <th class="num">Total Pajak</th>
                            <th class="num">Total SC</th>
                            <th class="num">Total Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedRows as $index => $row)
                            <tr>
                                <td class="center">{{ $index + 1 }}</td>
                                <td>{{ $row['product'] }}</td>
                                <td class="num">{{ number_format($row['price_input'], 2) }}</td>
                                <td class="num">{{ number_format($row['unit_price'], 2) }}</td>
                                <td class="num">{{ number_format($row['net_sales'], 2) }}</td>
                                <td class="num">{{ number_format($row['discount'], 2) }}</td>
                                <td class="num">{{ number_format($row['tax'], 2) }}</td>
                                <td class="num">{{ number_format($row['sc'], 2) }}</td>
                                <td class="num">{{ number_format($row['subtotal'], 2) }}</td>
                                <td class="num">{{ number_format($row['qty'], 2) }}</td>
                                <td class="num">{{ number_format($row['price_input_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['unit_price_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['net_sales_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['discount_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['tax_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['sc_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['subtotal_all'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9">Grand Total</td>
                            <td class="num">{{ number_format($grandTotals['qty'], 2) }}</td>
                            <td class="num">{{ number_format($grandTotals['price_input_all'], 2) }}</td>
                            <td class="num">{{ number_format($grandTotals['unit_price_all'], 2) }}</td>
                            <td class="num">{{ number_format($grandTotals['net_sales_all'], 2) }}</td>
                            <td class="num">{{ number_format($grandTotals['discount_all'], 2) }}</td>
                            <td class="num">{{ number_format($grandTotals['tax_all'], 2) }}</td>
                            <td class="num">{{ number_format($grandTotals['sc_all'], 2) }}</td>
                            <td class="num">{{ number_format($grandTotals['subtotal_all'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Detail Input</h2>
            <div class="subtitle">
                Ini adalah semua baris sesuai input user, termasuk jika 1 produk punya harga berbeda.
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="center">No</th>
                            <th>Nama Produk</th>
                            <th class="num">Qty</th>
                            <th class="num">Harga Input</th>
                            <th class="num">Unit Price</th>
                            <th class="num">Net Sales</th>
                            <th class="num">Diskon</th>
                            <th class="num">Diskon All</th>
                            <th class="num">Pajak</th>
                            <th class="num">Pajak All</th>
                            <th class="num">SC</th>
                            <th class="num">SC All</th>
                            <th class="num">Subtotal</th>
                            <th class="num">Subtotal All</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detailRows as $index => $row)
                            <tr>
                                <td class="center">{{ $index + 1 }}</td>
                                <td>{{ $row['product'] }}</td>
                                <td class="num">{{ number_format($row['qty'], 2) }}</td>
                                <td class="num">{{ number_format($row['price_input'], 2) }}</td>
                                <td class="num">{{ number_format($row['unit_price'], 2) }}</td>
                                <td class="num">{{ number_format($row['net_sales'], 2) }}</td>
                                <td class="num">{{ number_format($row['discount'], 2) }}</td>
                                <td class="num">{{ number_format($row['discount_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['tax'], 2) }}</td>
                                <td class="num">{{ number_format($row['tax_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['sc'], 2) }}</td>
                                <td class="num">{{ number_format($row['sc_all'], 2) }}</td>
                                <td class="num">{{ number_format($row['subtotal'], 2) }}</td>
                                <td class="num">{{ number_format($row['subtotal_all'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>

</html>
