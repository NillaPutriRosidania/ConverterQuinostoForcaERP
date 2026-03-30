<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Produk</title>
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
            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.45),
                0 0 0 1px rgba(148, 163, 184, 0.25);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 55%);
            pointer-events: none;
        }

        .header {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .subtitle {
            margin-top: 6px;
            color: #64748b;
            font-size: 13px;
            max-width: 860px;
            line-height: 1.5;
        }

        .table-wrap {
            position: relative;
            z-index: 1;
            overflow: auto;
            border-radius: 16px;
            border: 1px solid #dbeafe;
            background: white;
        }

        table {
            width: 100%;
            min-width: 1900px;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            position: sticky;
            top: 0;
            background: #e0f2fe;
            color: #0369a1;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #bae6fd;
            white-space: nowrap;
            z-index: 2;
        }

        tbody td,
        tfoot td {
            padding: 8px 10px;
            border-bottom: 1px dashed #e5e7eb;
            vertical-align: middle;
            background: #fff;
        }

        tbody tr:hover td {
            background: #f8fafc;
        }

        td.num,
        th.num {
            text-align: right;
        }

        td.center,
        th.center {
            text-align: center;
        }

        .product-select {
            min-width: 320px;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            outline: none;
            font-size: 13px;
        }

        .input-cell input {
            width: 100%;
            min-width: 110px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            outline: none;
            font-size: 13px;
        }

        .input-cell input:focus,
        .product-select:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .readonly-box {
            min-width: 115px;
            padding: 10px 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            text-align: right;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
        }

        .locked-input,
        .locked-select {
            background: #e2e8f0 !important;
            color: #475569 !important;
            cursor: not-allowed !important;
        }

        .row-action {
            display: flex;
            justify-content: center;
        }

        .btn-icon {
            border: none;
            cursor: pointer;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-danger:hover {
            background: #fecaca;
        }

        tfoot td {
            position: sticky;
            bottom: 0;
            background: #dbeafe;
            border-top: 2px solid #93c5fd;
            border-bottom: none;
            font-weight: 800;
            color: #0f172a;
        }

        .actions {
            margin-top: 16px;
            position: relative;
            z-index: 1;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            box-shadow: 0 10px 20px rgba(2, 132, 199, 0.25);
        }

        .btn-secondary {
            background: rgba(15, 23, 42, 0.07);
            color: #334155;
            border: 1px solid rgba(15, 23, 42, 0.12);
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #15803d);
            color: white;
            box-shadow: 0 10px 20px rgba(21, 128, 61, 0.25);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 10px 20px rgba(217, 119, 6, 0.25);
        }

        .helper {
            margin-top: 10px;
            color: #64748b;
            font-size: 12px;
            position: relative;
            z-index: 1;
            line-height: 1.5;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div>
                    <h2>Rekap Produk Kopi Kilen</h2>
                    <div class="subtitle">
                        Tambah baris sesuai kebutuhan. Kalau 1 produk punya harga berbeda, cukup buat baris baru
                        dengan produk yang sama. Isi Qty dan Harga Akhir, kolom lain akan terhitung otomatis.
                    </div>
                </div>

                <div class="badge" id="modeBadge">Mode: Edit</div>
            </div>

            @if ($errors->any())
                <div style="margin-bottom:12px; padding:12px; border-radius:12px; background:#fee2e2; color:#991b1b;">
                    <strong>Validasi gagal:</strong>
                    <ul style="margin:8px 0 0 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('rekap.products.result') }}" method="POST" id="recapForm">
                @csrf

                <div class="table-wrap">
                    <table id="rekapTable">
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
                                <th class="center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="tableBody"></tbody>

                        <tfoot>
                            <tr>
                                <td colspan="2">Grand Total</td>
                                <td class="num" id="grandQty">0</td>
                                <td class="num" id="grandPriceInput">0</td>
                                <td class="num" id="grandUnitPrice">0</td>
                                <td class="num" id="grandNetSales">0</td>
                                <td class="num" id="grandDiscountAll">0</td>
                                <td class="num" id="grandDiscountAll2">0</td>
                                <td class="num" id="grandTaxAll">0</td>
                                <td class="num" id="grandTaxAll2">0</td>
                                <td class="num" id="grandScAll">0</td>
                                <td class="num" id="grandScAll2">0</td>
                                <td class="num" id="grandSubtotalAll">0</td>
                                <td class="num" id="grandSubtotalAll2">0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn-primary" id="addRowBtn">+ Tambah Baris</button>
                    <button type="button" class="btn btn-success" id="saveTableBtn">💾 Save</button>
                    <button type="button" class="btn btn-warning" id="editTableBtn" style="display:none;">✏️
                        Edit</button>
                    <button type="button" class="btn btn-secondary" id="fillExample">Isi contoh angka</button>
                    <button type="button" class="btn btn-secondary" id="resetTable">Reset</button>

                    <label class="btn btn-secondary" for="importFile">📥 Import Excel</label>
                    <input type="file" id="importFile" accept=".xlsx,.xls,.csv" style="display:none;">

                    <button type="submit" class="btn btn-primary">📊 Lihat Rekap</button>
                </div>
            </form>

            <div class="helper">
                Rumus:
                unit price = harga input / 0.8,
                net sales = harga input / 1.155,
                diskon = unit price - net sales,
                pajak = 10.5% × net sales,
                sc = 5% × net sales,
                subtotal = net sales + pajak + sc.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <script>
        const productOptions = @json($products);
        let isLocked = false;

        const formatNumber = (value) => {
            const number = Number(value || 0);
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        };

        const parseValue = (value) => {
            const normalized = String(value ?? '').replace(/,/g, '');
            const number = parseFloat(normalized);
            return isNaN(number) ? 0 : number;
        };

        function normalizeProductName(name) {
            return String(name ?? '')
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();
        }

        function findMatchingProduct(importedName) {
            const normalizedImported = normalizeProductName(importedName);

            const found = productOptions.find(product => {
                return normalizeProductName(product) === normalizedImported;
            });

            return found ?? '';
        }

        function createProductOptions() {
            let options = '<option value="">Pilih produk</option>';
            productOptions.forEach(product => {
                options += `<option value="${escapeHtml(product)}">${escapeHtml(product)}</option>`;
            });
            return options;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }

        function createRow(data = {}) {
            const tbody = document.getElementById('tableBody');
            const rowIndex = document.querySelectorAll('#tableBody .product-row').length;

            const tr = document.createElement('tr');
            tr.className = 'product-row';

            tr.innerHTML = `
            <td class="center row-number"></td>
            <td>
                <select class="product-select" name="items[${rowIndex}][product]">
                    ${createProductOptions()}
                </select>
            </td>
            <td class="input-cell">
                <input type="number" min="0" step="1" class="qty-input" name="items[${rowIndex}][qty]" value="${data.qty ?? 0}">
            </td>
            <td class="input-cell">
                <input type="number" min="0" step="0.01" class="price-input" name="items[${rowIndex}][price_input]" value="${data.priceInput ?? 0}">
            </td>
            <td><div class="readonly-box unit-price">0</div></td>
            <td><div class="readonly-box net-sales">0</div></td>
            <td><div class="readonly-box discount">0</div></td>
            <td><div class="readonly-box discount-all">0</div></td>
            <td><div class="readonly-box tax">0</div></td>
            <td><div class="readonly-box tax-all">0</div></td>
            <td><div class="readonly-box sc">0</div></td>
            <td><div class="readonly-box sc-all">0</div></td>
            <td><div class="readonly-box subtotal">0</div></td>
            <td><div class="readonly-box subtotal-all">0</div></td>
            <td class="row-action">
                <button type="button" class="btn-icon btn-danger delete-row-btn" title="Hapus baris">×</button>
            </td>
        `;

            tbody.appendChild(tr);

            const select = tr.querySelector('.product-select');
            select.value = data.product ?? '';

            tr.querySelectorAll('.qty-input, .price-input').forEach(input => {
                input.addEventListener('input', calculateAll);
            });

            tr.querySelector('.product-select').addEventListener('change', calculateAll);

            tr.querySelector('.delete-row-btn').addEventListener('click', () => {
                if (isLocked) return;
                tr.remove();
                reindexRows();
                calculateAll();
            });

            updateRowNumbers();
            applyLockStateToRow(tr);
            calculateAll();
        }

        function reindexRows() {
            document.querySelectorAll('#tableBody .product-row').forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;
                row.querySelector('.product-select').name = `items[${index}][product]`;
                row.querySelector('.qty-input').name = `items[${index}][qty]`;
                row.querySelector('.price-input').name = `items[${index}][price_input]`;
            });
        }

        function updateRowNumbers() {
            document.querySelectorAll('#tableBody .product-row').forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;
            });
        }

        function calculateRow(row) {
            const qty = parseValue(row.querySelector('.qty-input').value);
            const priceInput = parseValue(row.querySelector('.price-input').value);

            const unitPrice = priceInput / 0.8;
            const netSales = priceInput / 1.155;
            const discount = unitPrice - netSales;
            const discountAll = discount * qty;
            const tax = 0.105 * netSales;
            const taxAll = tax * qty;
            const sc = 0.05 * netSales;
            const scAll = sc * qty;
            const subtotal = netSales + tax + sc;
            const subtotalAll = subtotal * qty;

            row.querySelector('.unit-price').textContent = formatNumber(unitPrice);
            row.querySelector('.net-sales').textContent = formatNumber(netSales);
            row.querySelector('.discount').textContent = formatNumber(discount);
            row.querySelector('.discount-all').textContent = formatNumber(discountAll);
            row.querySelector('.tax').textContent = formatNumber(tax);
            row.querySelector('.tax-all').textContent = formatNumber(taxAll);
            row.querySelector('.sc').textContent = formatNumber(sc);
            row.querySelector('.sc-all').textContent = formatNumber(scAll);
            row.querySelector('.subtotal').textContent = formatNumber(subtotal);
            row.querySelector('.subtotal-all').textContent = formatNumber(subtotalAll);

            return {
                qty,
                priceInputAll: priceInput * qty,
                unitPrice,
                unitPriceAll: unitPrice * qty,
                netSales,
                netSalesAll: netSales * qty,
                discount,
                discountAll,
                tax,
                taxAll,
                sc,
                scAll,
                subtotal,
                subtotalAll
            };
        }

        function calculateAll() {
            let totals = {
                qty: 0,
                priceInputAll: 0,
                unitPriceAll: 0,
                netSalesAll: 0,
                discountAll: 0,
                taxAll: 0,
                scAll: 0,
                subtotalAll: 0
            };

            document.querySelectorAll('.product-row').forEach(row => {
                const result = calculateRow(row);

                totals.qty += result.qty;
                totals.priceInputAll += result.priceInputAll;
                totals.unitPriceAll += result.unitPriceAll;
                totals.netSalesAll += result.netSalesAll;
                totals.discountAll += result.discountAll;
                totals.taxAll += result.taxAll;
                totals.scAll += result.scAll;
                totals.subtotalAll += result.subtotalAll;
            });

            document.getElementById('grandQty').textContent = formatNumber(totals.qty);
            document.getElementById('grandPriceInput').textContent = formatNumber(totals.priceInputAll);
            document.getElementById('grandUnitPrice').textContent = formatNumber(totals.unitPriceAll);
            document.getElementById('grandNetSales').textContent = formatNumber(totals.netSalesAll);
            document.getElementById('grandDiscountAll').textContent = formatNumber(totals.discountAll);
            document.getElementById('grandDiscountAll2').textContent = formatNumber(totals.discountAll);
            document.getElementById('grandTaxAll').textContent = formatNumber(totals.taxAll);
            document.getElementById('grandTaxAll2').textContent = formatNumber(totals.taxAll);
            document.getElementById('grandScAll').textContent = formatNumber(totals.scAll);
            document.getElementById('grandScAll2').textContent = formatNumber(totals.scAll);
            document.getElementById('grandSubtotalAll').textContent = formatNumber(totals.subtotalAll);
            document.getElementById('grandSubtotalAll2').textContent = formatNumber(totals.subtotalAll);
        }

        function applyLockStateToRow(row) {
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const productSelect = row.querySelector('.product-select');
            const deleteBtn = row.querySelector('.delete-row-btn');

            qtyInput.readOnly = isLocked;
            priceInput.readOnly = isLocked;
            productSelect.disabled = isLocked;
            deleteBtn.disabled = isLocked;

            qtyInput.classList.toggle('locked-input', isLocked);
            priceInput.classList.toggle('locked-input', isLocked);
            productSelect.classList.toggle('locked-select', isLocked);
            deleteBtn.style.opacity = isLocked ? '0.45' : '1';
            deleteBtn.style.cursor = isLocked ? 'not-allowed' : 'pointer';
        }

        function setLockState(locked) {
            isLocked = locked;

            document.querySelectorAll('.product-row').forEach(row => applyLockStateToRow(row));

            document.getElementById('addRowBtn').disabled = locked;
            document.getElementById('addRowBtn').style.opacity = locked ? '0.5' : '1';
            document.getElementById('addRowBtn').style.cursor = locked ? 'not-allowed' : 'pointer';

            document.getElementById('saveTableBtn').style.display = locked ? 'none' : 'inline-flex';
            document.getElementById('editTableBtn').style.display = locked ? 'inline-flex' : 'none';

            document.getElementById('modeBadge').textContent = locked ? 'Mode: Saved' : 'Mode: Edit';
        }

        function mapSheetRows(jsonRows) {
            return jsonRows.map(row => {
                const normalized = {};

                Object.keys(row).forEach(key => {
                    const cleanKey = String(key).trim().toLowerCase();

                    if (['produk', 'product', 'nama produk', 'nama_product', 'nama item', 'item', 'menu']
                        .includes(cleanKey)) {
                        normalized.product = row[key];
                    }

                    if (['qty', 'quantity', 'jumlah'].includes(cleanKey)) {
                        normalized.qty = row[key];
                    }

                    if (['harga', 'price', 'harga input', 'harga akhir', 'final price'].includes(
                            cleanKey)) {
                        normalized.price = row[key];
                    }
                });

                return normalized;
            }).filter(row => {
                return row.product !== undefined || row.qty !== undefined || row.price !== undefined;
            });
        }

        function importRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                alert('Data import kosong atau tidak terbaca.');
                return;
            }

            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            rows.forEach(row => {
                const rawProduct = row.product ?? row.nama_produk ?? row.nama ?? row.item ?? '';
                const rawQty = row.qty ?? row.quantity ?? 0;
                const rawPrice = row.harga ?? row.price ?? row.harga_input ?? row.unit_price ?? 0;

                const matchedProduct = findMatchingProduct(rawProduct);

                createRow({
                    product: matchedProduct, // kosong kalau tidak match
                    qty: parseValue(rawQty),
                    priceInput: parseValue(rawPrice),
                });
            });

            reindexRows();
            calculateAll();
        }

        document.getElementById('addRowBtn').addEventListener('click', () => {
            if (isLocked) return;
            createRow();
        });

        document.getElementById('saveTableBtn').addEventListener('click', () => {
            calculateAll();
            setLockState(true);
        });

        document.getElementById('editTableBtn').addEventListener('click', () => {
            setLockState(false);
        });

        document.getElementById('resetTable').addEventListener('click', () => {
            if (isLocked) return;

            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            createRow();
            calculateAll();
        });

        document.getElementById('fillExample').addEventListener('click', () => {
            if (isLocked) return;

            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            createRow({
                product: productOptions[0] ?? '',
                qty: 1,
                priceInput: 37000
            });

            createRow({
                product: productOptions[0] ?? '',
                qty: 4,
                priceInput: 40000
            });

            createRow({
                product: productOptions[1] ?? '',
                qty: 2,
                priceInput: 28400
            });

            calculateAll();
        });

        document.getElementById('recapForm').addEventListener('submit', () => {
            calculateAll();
        });

        document.getElementById('importFile').addEventListener('change', async function(event) {
            if (isLocked) return;

            const file = event.target.files[0];
            if (!file) return;

            try {
                const arrayBuffer = await file.arrayBuffer();
                const workbook = XLSX.read(arrayBuffer, {
                    type: 'array'
                });

                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];

                const jsonRows = XLSX.utils.sheet_to_json(worksheet, {
                    defval: ''
                });
                const mappedRows = mapSheetRows(jsonRows);

                importRows(mappedRows);

                // reset input file supaya file yang sama bisa dipilih lagi
                event.target.value = '';
            } catch (error) {
                console.error(error);
                alert('Gagal membaca file import: ' + error.message);
            }
        });

        createRow();
        calculateAll();
        setLockState(false);
    </script>
</body>

</html>
