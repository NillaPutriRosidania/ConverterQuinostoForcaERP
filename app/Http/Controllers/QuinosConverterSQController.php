<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuinosConverterSQController extends Controller
{
    public function index()
    {
        return view('converter.convertSQ');
    }

    private function isNotes(array $row, array $modifierNotesUpper): bool
    {
        $nameUpper = strtoupper(trim($row['name'] ?? ''));

        // notes berdasarkan daftar modifier
        if ($nameUpper !== '' && in_array($nameUpper, $modifierNotesUpper, true)) {
            return true;
        }

        // fallback: notes biasanya qty & price = 0
        $qty   = (float) ($row['qty'] ?? 0);
        $price = (float) ($row['price'] ?? 0);

        if ($qty == 0.0 && $price == 0.0) {
            return true;
        }

        return false;
    }

    private function isModifierRow(array $row, array $modifierNotesUpper): bool
    {
        $nameUpper = strtoupper(trim($row['name'] ?? ''));
        $category  = strtoupper(trim($row['category'] ?? ''));
        $department = strtoupper(trim($row['department'] ?? ''));

        // 1. berdasarkan notes array
        if (in_array($nameUpper, $modifierNotesUpper, true)) {
            return true;
        }

        // 2. berdasarkan category / department
        if ($category === 'MODIFIER' || $department === 'MODIFIER') {
            return true;
        }

        return false;
    }

    private function isTakeAwayZeroRow(array $row): bool
    {
        $name = trim($row['name'] ?? '');
        $nameUpper = strtoupper($name);

        $price = (float) ($row['price'] ?? 0);

        $code = trim($row['code'] ?? '');
        $codeUpper = strtoupper($code);

        // 1. Description HARUS berawalan "Take Away"
        if (stripos($name, 'Take Away') !== 0) {
            return false;
        }

        // 2. Code HARUS diawali "TKW" tapi BUKAN persis "TKW"
        if (
            strpos($codeUpper, 'TKW') !== 0 // harus prefix TKW
            || $codeUpper === 'TKW'         // tapi tidak boleh persis TKW
        ) {
            return false;
        }

        // 3. Price harus 0
        if ($price != 0.0) {
            return false;
        }

        return true;
    }

    private function isTakeAwayItem(array $row): bool
    {
        $name = trim($row['name'] ?? '');
        $code = strtoupper(trim($row['code'] ?? ''));

        // Description harus diawali "Take Away"
        if (stripos($name, 'Take Away') !== 0) {
            return false;
        }

        // Code harus diawali TKW (TKWH001, TKWI002, dst)
        if (strpos($code, 'TKW') !== 0) {
            return false;
        }

        return true;
    }




    private function normalizeProductName(string $name): string
    {
        return trim(str_replace('#', '', $name));
    }


    public function convert(Request $request)
    {
        // 1. VALIDASI 2 FILE
        $request->validate([
            'file_detail'  => 'required|file|mimes:csv,txt',
            'file_summary' => 'required|file|mimes:csv,txt',
        ]);

        $pathDetail  = $request->file('file_detail')->getRealPath();
        $pathSummary = $request->file('file_summary')->getRealPath();

        /* =========================================================
         * 2. BACA FILE SUMMARY: Customer, Date, Discount, Service charge, Rounding
         *    FILE: DATA sumary OKTOBER SQ.csv
         *    Header:
         *    0 Transaction #
         *    1 (kosong)
         *    2 Date
         *    3 Store
         *    4 Type
         *    5 TBL
         *    6 Customer
         *    7 Pax
         *    8 Subtotal
         *    9 Discount
         *   10 Net sales
         *   11 Serv Chg
         *   12 Tax
         *   13 Rounding
         *   14 Total
         *   15 (kosong)
         * =======================================================*/

        $handleSummary = fopen($pathSummary, 'r');
        if (! $handleSummary) {
            abort(500, 'Gagal membuka file summary.');
        }

        $summaryHeader = fgetcsv($handleSummary);
        if ($summaryHeader === false) {
            abort(500, 'Header CSV summary tidak bisa dibaca.');
        }
        $summaryHeader = array_map('trim', $summaryHeader);

        // SESUAIKAN DENGAN HEADER CSV ASLI
        $sumTrxIndex   = array_search('Transaction #', $summaryHeader); // <-- dulu "Trx Code"
        $sumDateIndex  = array_search('Date', $summaryHeader);
        $sumDiscIndex  = array_search('Discount', $summaryHeader);
        $sumSvcIndex   = array_search('Serv Chg', $summaryHeader);      // <-- dulu "Service charge"
        $sumRoundIndex = array_search('Rounding', $summaryHeader);

        if (
            $sumTrxIndex   === false ||
            $sumDateIndex  === false ||
            $sumDiscIndex  === false ||
            $sumSvcIndex   === false ||
            $sumRoundIndex === false
        ) {
            fclose($handleSummary);
            abort(
                500,
                'Kolom summary tidak lengkap. Pastikan ada "Transaction #, Date, Discount, Serv Chg, Rounding". Header: '
                . implode(', ', $summaryHeader)
            );
        }

        // kolom "customer = di sebelah kiri Date" (index 1, header kosong → berisi "No Sales")
        $customerIndex = $sumDateIndex > 0 ? $sumDateIndex - 1 : null;

        // map summary: trxCode -> [dateOrdered, totalDisc, serviceCharge, rounding, customer]
        $summaryMap = [];

        while (($row = fgetcsv($handleSummary)) !== false) {
            if (! isset($row[$sumTrxIndex])) {
                continue;
            }

            $trxCode = trim($row[$sumTrxIndex]); // Transaction #
            if ($trxCode === '') {
                continue;
            }

            $dateRaw  = isset($row[$sumDateIndex])  ? trim($row[$sumDateIndex])  : '';
            $discRaw  = isset($row[$sumDiscIndex])  ? trim($row[$sumDiscIndex])  : '0';
            $svcRaw   = isset($row[$sumSvcIndex])   ? trim($row[$sumSvcIndex])   : '0';
            $roundRaw = isset($row[$sumRoundIndex]) ? trim($row[$sumRoundIndex]) : '0';

            $customerRaw = '';
            if ($customerIndex !== null && isset($row[$customerIndex])) {
                $customerRaw = trim($row[$customerIndex]); // "No Sales" / dll
            }

            // date ke format Y-m-d
            // contoh sumber summary: "1-Oct-25"
            $dateFormatted = '';

            if ($dateRaw !== '') {
                // pertama coba format m/d/Y (untuk jaga2), kalau gagal pakai strtotime
                $parts = explode('/', $dateRaw);

                if (count($parts) === 3) {
                    $month = (int) $parts[0];
                    $day   = (int) $parts[1];
                    $year  = (int) $parts[2];

                    $dateFormatted = sprintf('%04d-%02d-%02d', $year, $month, $day);
                } else {
                    $timestamp = strtotime($dateRaw);
                    if ($timestamp !== false) {
                        $dateFormatted = date('Y-m-d', $timestamp);
                    }
                }
            }

            // buang koma di angka
            $disc  = str_replace(',', '', $discRaw);
            $svc   = str_replace(',', '', $svcRaw);
            $round = str_replace(',', '', $roundRaw);

            $summaryMap[$trxCode] = [
                'date'     => $dateFormatted,
                'discount' => $disc,
                'service'  => $svc,
                'rounding' => $round,
                'customer' => $customerRaw,
            ];
        }

        fclose($handleSummary);

        /* =========================================================
         * 3. BACA FILE DETAIL (Export Transaction) – BERSIHKAN DULU
         *    FILE: DATA ASLI DETAIL PENJUALAN OKTOBER SQ.csv
         *
         *    Header detail:
         *    0 Invoice
         *    1 Date
         *    2 Location
         *    3 OpenTime
         *    4 CloseTime
         *    5 SalesType
         *    6 Cashier
         *    7 Customer
         *    8 Pax
         *    9 Table
         *   10 Server
         *   11 Code
         *   12 Description
         *   13 Category
         *   14 Department
         *   15 Quantity
         *   16 UnitPrice
         *   17 Discount
         *   18 ServiceCharge
         *   19 Tax
         *   20 Payment
         *   21 ''
         *   22 ''
         * =======================================================*/

        $handleDetail = fopen($pathDetail, 'r');
        if (! $handleDetail) {
            abort(500, 'Gagal membuka file detail.');
        }

        $inputHeader = fgetcsv($handleDetail); // baris pertama = header
        if ($inputHeader === false) {
            abort(500, 'Header CSV detail tidak bisa dibaca.');
        }
        $inputHeader = array_map('trim', $inputHeader);

        // SESUAIKAN DENGAN HEADER CSV ASLI
        $trxIndex   = array_search('Invoice', $inputHeader);     // <-- dulu "Trx Code"
        $qtyIndex   = array_search('Quantity', $inputHeader);    // <-- dulu "Qty"
        $priceIndex = array_search('UnitPrice', $inputHeader);   // <-- dulu "Price"
        $nameIndex  = array_search('Description', $inputHeader); // <-- dulu "Name"
        $catIndex  = array_search('Category', $inputHeader);
        $deptIndex = array_search('Department', $inputHeader);
        $custIndex = array_search('Customer', $inputHeader);

        if ($catIndex === false || $deptIndex === false) {
            fclose($handleDetail);
            abort(500, 'Kolom "Category" atau "Department" tidak ditemukan di file detail. Header: ' . implode(', ', $inputHeader));
        }

        if ($trxIndex === false || $qtyIndex === false || $priceIndex === false || $nameIndex === false) {
            fclose($handleDetail);
            abort(
                500,
                'Kolom "Invoice", "Quantity", "UnitPrice", atau "Description" tidak ditemukan di file detail. Header: '
                . implode(', ', $inputHeader)
            );
        }

        // list NOTES / MODIFIER yang harus dihapus (unit price 0)
        $modifierNotes = [
            '3.4',
            '12',
            '34',
            '45659',
            '45720',
            '1 MNS',
            '1 PDS',
            '1 SMBEL MATAH',
            '1 TDK PKE BWNG GRG',
            '1 TLR MTG',
            '1/2 MTG',
            '1 DINGIN',
            '1 ES',
            '1 GK PKE SAYUR',
            '1 GPKE KORNET',
            '1 LES ICE&UGAR',
            '1 LS',
            '1 MINYAK DIKIT',
            '1MNS',
            '2 CEPLOK MTG',
            '2 PEDES',
            '2 SEDENG 1 PEDES',
            '2MTG',
            '3 LS (Price actual 4)',
            '3/4 JGN ENCER',
            '8 MNT',
            '8MNT',
            'ACEH',
            'ADD ICE',
            'BUMBU JGN BANYAK',
            'CABE PISAH',
            'CBE PISAH',
            'CEPLOK 1/2 MTG',
            'CEPLOK MTG',
            'CHANGE GAYO',
            'COKLAT AJA',
            'DADAR',
            'DDR (price actual 1)',
            'DIBUAT NNTI',
            'DIKLUARIN JAM 1',
            'DINGIN',
            'DOUBLE',
            'EXTRA ICE',
            'GAK PEDES',
            'GARING',
            'GAPAKE KORNET. MIENYA SETENGAH MATENG',
            'GAUSAH DIBUAT',
            'GAYO',
            'GAYO 2 PACK',
            'GK PDES',
            'GK PDS',
            'GK PEDES',
            'GK PKE CABE',
            'GK PKE GULA',
            'GK PKE SAYUR (price 1)',
            'GK PKE TELUR..',
            'GPKE RAWIT',
            'GPKE TELOR',
            'GULA IKIT',
            'HOT',
            'ICE',
            'ICED',
            'JD 1 CUP',
            'JGN DIBIKIN',
            'JGN PKE CABE',
            'JGN PKE COKLAT',
            'JGN PKE KECAP',
            'JGN PKE SAYUR',
            'Kalibrasi - Espresso',
            'Kalibrasi - Latte',
            'KCAP DIKIT',
            'KERING',
            'KERING TKWWWWW',
            'KERIS',
            'KERUPUK',
            'KOENET TELUR D DADAR',
            'KORNET DADAR',
            'KUAH DIBANYAKIN',
            'L SUSU',
            'LC',
            'LECI',
            'LEMON',
            'LEMONADE',
            'LES ICE',
            'LESS COFE',
            'LESSWEET',
            'LI',
            'LILS',
            'LMON',
            'LS',
            'LS LC',
            'LS LI',
            'M',
            'MANDAILING',
            'MANIS',
            'MATANG',
            'MATENG',
            'MEDIU',
            'MEDIUM',
            'MINYAK DIKIT',
            'MNS',
            'MS',
            'MTG',
            'MTG JNG BYK MINYAK',
            'MTG PEDES',
            'MTG REBUS',
            'NASI 1/2',
            'NASI STGH',
            'NO CABE',
            'NO CARAMEL',
            'NO ICING',
            'NO KORNET',
            'NO SAYUR',
            'NO SAYUR NO DAUNG BWG',
            'NO SAYUR NO KORNET',
            'NORMAL SUGAR',
            'ON THE ROCK',
            'PAKE RAWIT',
            'PDS',
            'PDS MNS',
            'PEACH',
            'PEACH 2',
            'PEDAS',
            'PEDES',
            'PEES',
            'PUTIH AJA',
            'PUTIHNYA AJA',
            'RAWIT',
            'REBUS',
            'REBUS MATENG',
            'REBUS MTG',
            'SAMBEL PISAH',
            'SAYURAN',
            'SDG',
            'SDIKIT',
            'SDNG',
            'SEDANG',
            'SMBL PISAH',
            'iSTGH MTG',
            'SWEET',
            'T',
            'TA',
            'TAKE AWAY SEMUA',
            'TAKEAWZY',
            'TANPA CABE',
            'TANPA CABE KORNET',
            'TDK PDS',
            'TDK PKE BWNG GORENG',
            'TDK PKE DAUN BWNG',
            'TDK PKE GULA',
            'TDK PKE KECAP',
            'TDK PKE KRNT',
            'TDK PKE NASI..',
            'TDK PKE PISNG',
            'TELOR DDR',
            'TELOR MTG',
            'TIMUN TOMAT',
            'TIPIS',
            'TIPIS KERING',
            'TKW',
            'TKWWW',
            'TKWWWWW',
            'TLR 1/2 MTG',
            'TLR 3/4',
            'TLR DADAR',
            'TLR GANTI KORNET',
            'TLR MTG',
            'TLR MTG/TNP SAYUR',
            'TLRNYA 1/2 MTG',
            'TNPA GULA',
            'TNPA KORNET',
            'TNPA KORNET & SYR',
            'TNPA NASI',
            'TNPA PISANG',
            'TNPACABE',
            'TWR',
            'TANPA KORNET',
            'TIMUN',
            'TDK PKE NASI',
            '3 LS',
            'DDR',
            'PDS  MNS',
            'TANPA KORNET TANPA SAYUR',
            'TANPA SAYUR',
            'SEDENG',
            'TDK PEDES',
            'STGH MTG',
            'PISAH GULA',
            'TNPA KRNT',
            'GK PKE TELUR',
            'TANPA KORNET',
            'JGN BERMINYAK',
            'LI LS',
            'GK PKE SAYUR',
        ];

        // buat versi uppercase supaya cocok dengan strtoupper($name)
        $modifierNotesUpper = array_map(function ($v) {
            return strtoupper(trim($v));
        }, $modifierNotes);

        // list nama paket/combo yang harus dihapus, tapi anak "#"-nya dipakai
        $comboNames = [
            'Breakfast Kilen 1',
            'Breakfast Kilen 2',
            'Breakfast Kilen 3',
            'Combo Kilen Ber 2',
            'Combo Kilen Ber 3',
            'Combo Kopi Kilen x Cocacola',
            'Kilen Nyemil',
            'Kilen Toast',
            'Kilen Toast A',
            'Kilen Toast B',
        ];

        // 3a. BACA SEMUA BARIS DETAIL KE MEMORI
        $rawRows = [];
        

        while (($row = fgetcsv($handleDetail)) !== false) {
            if (! isset($row[$trxIndex])) {
                continue;
            }

            $trxCode = trim($row[$trxIndex]); // Invoice
            if ($trxCode === '') {
                continue;
            }

            $codeIndex = array_search('Code', $inputHeader);
            if ($codeIndex === false) {
                fclose($handleDetail);
                abort(500, 'Kolom "Code" tidak ditemukan di file detail.');
            }

            $nameRaw  = trim($row[$nameIndex]  ?? '');
            $qtyRaw   = trim($row[$qtyIndex]   ?? '0');
            $priceRaw = trim($row[$priceIndex] ?? '0');
            $categoryRaw   = trim($row[$catIndex] ?? '');
            $departmentRaw = trim($row[$deptIndex] ?? '');
            $customRaw = trim($row[$custIndex] ?? '');
            $codeRaw = trim($row[$codeIndex] ?? '');

            // buang koma ribuan
            $qtyStr   = str_replace(',', '', $qtyRaw);
            $priceStr = str_replace(',', '', $priceRaw);

            $qty   = (float) $qtyStr;
            $price = (float) $priceStr;

            $rawRows[] = [
                'trx'   => $trxCode,
                'name'  => $nameRaw,
                'qty'   => $qty,
                'price' => $price,
                'category'   => $categoryRaw,
                'department' => $departmentRaw,
                'customer' => $customRaw,
                'code'       => $codeRaw,
            ];
        }
        fclose($handleDetail);

        // 3b. PROSES: HAPUS NOTES & PECAH PAKET
        $logicalRows = [];
        $totalRaw    = count($rawRows);

        for ($i = 0; $i < $totalRaw; $i++) {
            $current = $rawRows[$i];

            $trxCode = $current['trx'];
            $name    = $current['name'];
            $qty     = $current['qty'];
            $price   = $current['price'];
            $category     = $current['category'];
            $departement   = $current['department'];

            // hitung unit price
            $unitPrice = $qty != 0 ? $price / $qty : 0;

            // HAPUS TAKE AWAY (TKW, price = 0)
            if ($this->isTakeAwayZeroRow($current)) {
                continue;
            }

            if ($this->isModifierRow($current, $modifierNotesUpper)) {
                    continue;
            }

            // 2) JIKA BARIS INI ADALAH PAKET/COMBO

            if (in_array($name, $comboNames, true)) {

                $childrenIndex = [];
                $j = $i + 1;

                while ($j < $totalRaw) {
                    $next = $rawRows[$j];

                    // 1. beda invoice / trx → stop combo
                    if ($next['trx'] !== $trxCode) {
                        break;
                    }

                    // 2. modifier / notes → skip tapi combo lanjut
                    if ($this->isModifierRow($next, $modifierNotesUpper)) {
                        $j++;
                        continue;
                    }

                    // 3. TAKE AWAY (Code awalan TKW & Description awalan Take Away)
                    //    -> TIDAK masuk anak combo, TAPI combo lanjut scan item berikutnya
                    if ($this->isTakeAwayItem($next)) {
                        $j++;
                        continue;
                    }

                    // 4. ketemu combo baru → stop (hindari nested combo)
                    if (in_array($next['name'], $comboNames, true)) {
                        break;
                    }

                    // 5. item valid → bagian dari combo
                    $childrenIndex[] = $j;
                    $j++;
                }

                $childCount = count($childrenIndex);

                // hanya proses jika combo valid
                if ($childCount > 0 && $qty > 0 && $price > 0) {

                    $unitPriceChild = ($price / $qty) / $childCount;

                    foreach ($childrenIndex as $idxChild) {
                        $child = $rawRows[$idxChild];

                        $logicalRows[] = [
                            'trx'       => $trxCode,
                            'name'      => trim($child['name']),
                            'qty'       => $child['qty'] > 0 ? $child['qty'] : $qty,
                            'unitPrice' => $unitPriceChild,
                            'customer'  => $child['customer'] ?? '',
                        ];
                    }
                }

                // lompat ke baris setelah combo
                $i = $j - 1;

                // combo header tidak dimasukkan
                continue;
            }



            // 3) BUKAN MODIFIER & BUKAN PAKET → MASUK LANGSUNG
            $logicalRows[] = [
                'trx'       => $trxCode,
                'name'      => $name,
                'qty'       => $qty,
                'unitPrice' => $unitPrice,
                'customer'  => $current['customer'],
            ];
        }

        /* =========================================================
         * 4. HEADER OUTPUT (TAMBAH ROUNDING)
         * =======================================================*/

        $headers = [
            'AD_Org_ID[Name]',                              // 0
            'FORCA_TransactionType',                        // 1
            'Business Partner',                             // 2
            'Description',                                  // 3
            'DateOrdered',                                  // 4
            'FORCA_POSID',                                  // 5
            'Total Discount',                               // 6
            'Description Discount',                         // 7
            'Service Charge',                               // 8
            'Rounding',                                     // 9
            'C_BankAccount_ID[Value]',                      // 10
            'C_Project_ID[Value]',                          // 11
            'C_Activity_ID[Value]',                         // 12
            'M_Warehouse_ID[Value]',                        // 13
            'SalesRep_ID[Name]',                            // 14
            'C_Currency_ID',                                // 15
            'IsActive',                                     // 16
            'C_DocType_ID[Name]',                           // 17
            'M_PriceList_ID[Name]',                         // 18
            'C_PaymentTerm_ID[Value]',                      // 19
            'FORCA_ImportSalesPOSLine>AD_Org_ID[Name]',     // 20
            'Line No',                                      // 21
            'FORCA_ImportSalesPOSLine>M_Product_ID[Value]', // 22
            'FORCA_ImportSalesPOSLine>PriceActual',         // 23
            'FORCA_ImportSalesPOSLine>QtyOrdered',          // 24
            'FORCA_ImportSalesPOSLine>C_Tax_ID[Name]',      // 25
            'FORCA_ImportSalesPOSLine>IsActive',            // 26
        ];

        /* =========================================================
         * 5. KAMUS PRODUK: Nama di Quinos -> Nama di FORCA (berkode)
         * =======================================================*/

        $productMap = [
            // DRINKS
            'Americano (Hot)'                          => '1001802_Americano (Hot) - KOPI KILEN (DRINKS)',
            'Americano (Iced)'                         => '1001803_Americano (Iced) - KOPI KILEN (DRINKS)',
            'Americano'                                => '1001803_Americano (Iced) - KOPI KILEN (DRINKS)',
            'Aren Latte (Hot)'                         => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
            'Aren Latte (Iced)'                        => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
            'Aren Latte'                               => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
            'Cafe Latte (Hot)'                         => '1001806_Cafe Latte (Hot) - KOPI KILEN (DRINKS)',
            'Caffe Latte (Hot)'                        => '1001806_Cafe Latte (Hot) - KOPI KILEN (DRINKS)',
            'Caffe Latte (Iced)'                       => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',
            'Caffe Latte'                              => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',
            'Cappuccino (Hot)'                         => '1001808_Cappuccino (Hot) - KOPI KILEN (DRINKS)',
            'Cappuccino (Iced)'                        => '1001809_Cappuccino (Iced) - KOPI KILEN (DRINKS)',
            'Cappuccino'                               => '1001809_Cappuccino (Iced) - KOPI KILEN (DRINKS)',
            'Caramel Latte (Hot)'                      => '1001810_Caramel Latte (Hot) - KOPI KILEN (DRINKS)',
            'Caramel Latte (Iced)'                     => '1001811_Caramel Latte (Iced) - KOPI KILEN (DRINKS)',
            'Caramel Latte'                            => '1001811_Caramel Latte (Iced) - KOPI KILEN (DRINKS)',
            'Chocolate (Hot)'                          => '1001812_Chocolate (Hot) - KOPI KILEN (DRINKS)',
            'Chocolate (Iced)'                         => '1001813_Chocolate (Ice) - KOPI KILEN (DRINKS)',
            'Chocolate'                                => '1001813_Chocolate (Ice) - KOPI KILEN (DRINKS)',
            'Coca Cola'                                => '1001698_Coca Cola @250Ml - KOPI KILEN (DRINKS)',
            'Coca Cola Zero'                           => '1001701_Coca Cola Zero - KOPI KILEN (DRINKS)',
            'Cold Brew Sweet Kilen'                    => '1001686_Cold Brew Sweet - KOPI KILEN (DRINKS)',
            'Cold White Caramel Kilen'                 => '1001684_Cold White Caramel - KOPI KILEN (DRINKS)',
            'Cold White Coconut Kilen'                 => '1001685_Cold White Coconut - KOPI KILEN (DRINKS)',
            'Cold White Original'                      => '1001683_Cold White Original - KOPI KILEN (DRINKS)',
            'Creamy Dopio Espresso'                    => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
            'Creamy Doppio Espresso'                   => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Hot (Bali Batu'         => '1001815_Daily Brew (V-60) Hot (Bali Batukaru) - KOPI KILEN (DRINKS)',
            'V60 Bali Batukaru (Hot)'                  => '1001815_Daily Brew (V-60) Hot (Bali Batukaru) - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Iced (Bali Bat'         => '1001818_Daily Brew (V-60) Iced (Bali Batukaru) - KOPI KILEN (DRINKS)',
            'V60 Bali Batukaru (Iced)'                 => '1001818_Daily Brew (V-60) Iced (Bali Batukaru) - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Hot (Mandailin'         => '1001816_Daily Brew (V-60) Hot (Mandailing) - KOPI KILEN (DRINKS)',
            'V60 Mandailing (Hot)'                     => '1001816_Daily Brew (V-60) Hot (Mandailing) - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Iced (Mandaili'         => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
            'V60 Mandailing (Iced)'                    => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
            'Daily Brew(V-60) Hot (Aceh Gayo)'         => '1001819_Daily Brew(V-60) Hot (Aceh Gayo) - KOPI KILEN (DRINKS)',
            'V60 Aceh Gayo (Hot)'                      => '1001819_Daily Brew(V-60) Hot (Aceh Gayo) - KOPI KILEN (DRINKS)',
            'Daily Brew(V-60) Iced (Aceh Gayo'         => '1001822_Daily Brew(V-60) Iced (Aceh Gayo) - KOPI KILEN (DRINKS)',
            'V60 Aceh Gayo (Iced)'                     => '1001822_Daily Brew(V-60) Iced (Aceh Gayo) - KOPI KILEN (DRINKS)',
            'Daily Brew(V-60) Hot (Toraja Sap'         => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
            'V60 Toraja Sapan (Hot)'                   => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Daily Brew(V-60) Iced (Toraja Sa'         => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
            'V60 Toraja Sapan (Iced)'                  => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
            'DailyBrewV60'                             => '1002484_Daily Brew V60 - Kopi Kilen',
            'Aceh Gayo 250 Gr'                         => '1002485_Aceh Gayo 250 Gr - Kopi Kilen',
            'Kopi Kilen Blend 250g'                    => '1002486_Kopi Kilen Blend 250g - Kopi Kilen',

            'Espresso'                                 => '1001823_Esspreso - KOPI KILEN (DRINKS)',
            'Espresso Shot'                            => '1002337_Add on Essppresso - KOPI KILEN (DRINKS)',
            'Flat White (Hot)'                         => '1001824_Flat White - KOPI KILEN (DRINKS)',
            'Flat White'                               => '1001824_Flat White - KOPI KILEN (DRINKS)',
            'Green Tea (Hot)'                          => '1001825_Green Tea (Hot) - KOPI KILEN (DRINKS)',
            'Green Tea (Iced)'                         => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',
            'Green Tea Latte'                          => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',
            'Hazelnut Latte (Hot)'                     => '1001827_Hazelnut Latte (Hot) - KOPI KILEN (DRINKS)',
            'Hazelnut Latte (Iced)'                    => '1001828_Hazelnut Latte (Iced) - KOPI KILEN (DRINKS)',
            'Hazelnut Latte'                           => '1001828_Hazelnut Latte (Iced) - KOPI KILEN (DRINKS)',
            'Honey Citron Tea Hot'                     => '1001829_Honey Citron Tea Hot - KOPI KILEN (DRINKS)',
            'Honey Citron Tea (Hot)'                   => '1001829_Honey Citron Tea Hot - KOPI KILEN (DRINKS)',
            'Honey Citron Tea Iced'                    => '1001931_Honey Citron Tea Iced - KOPI KILEN (DRINKS)',
            'Honey Citron Tea (Iced)'                  => '1001931_Honey Citron Tea Iced - KOPI KILEN (DRINKS)',
            'Honey Citron Tea'                         => '1001931_Honey Citron Tea Iced - KOPI KILEN (DRINKS)',
            'Java Tea (Hot)'                           => '1001830_Java Tea (Hot) - KOPI KILEN (DRINKS)',
            'Java Tea (Iced)'                          => '1001831_Java Tea (Ice) - KOPI KILEN (DRINKS)',
            'Java Tea'                                 => '1001831_Java Tea (Ice) - KOPI KILEN (DRINKS)',
            'Lemon Tea Hot'                            => '1001832_Lemon Tea (Hot) - KOPI KILEN (DRINKS)',
            'Lemon Tea Iced'                           => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',  
            'Lemon Tea'                                => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',
            'Lychee Tea Iced'                          => '1001834_Lychee Tea - KOPI KILEN (DRINKS)',
            'Lychee Tea'                               => '1001834_Lychee Tea - KOPI KILEN (DRINKS)',
            'Lychee Tea (Iced)'                        => '1001834_Lychee Tea - KOPI KILEN (DRINKS)',   
            'Macchiato'                                => '1001835_Macchiato (Hot) - KOPI KILEN (DRINKS)',
            'Mocha Latte (Hot)'                        => '1001836_Mocha Latte (Hot) - KOPI KILEN (DRINKS)',
            'Mocha Latte (Iced)'                       => '1001837_Mocha Latte (Ice) - KOPI KILEN (DRINKS)',
            'Mocca Latte'                              => '1001837_Mocha Latte (Ice) - KOPI KILEN (DRINKS)',    
            'Peach Tea'                                => '1001838_Peach Tea - KOPI KILEN (DRINKS)',
            'Peach Tea Iced'                           => '1001838_Peach Tea - KOPI KILEN (DRINKS)',
            'Piccolo'                                  => '1001839_Piccolo (Hot) - KOPI KILEN (DRINKS)',
            'Piccolo (Hot)'                            => '1001839_Piccolo (Hot) - KOPI KILEN (DRINKS)',
            'Pokka Green Tea'                          => '1001687_Pokka Green Tea - KOPI KILEN (DRINKS)',
            'Red Velvet (Iced)'                        => '1001841_Red Velvet (Ice) - KOPI KILEN (DRINKS)',
            'Water'                                    => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
            'Mineral Water'                            => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
            'Toraja Sapan 250g'                        => '1001520_Toraja Sapan Biji @250 Gr - KOPI KILEN (DRINKS)',
            'Vanilla Latte (Hot)'                      => '1001842_Vanilla Latte (Hot) - KOPI KILEN (DRINKS)',
            'Vanilla Latte (Iced)'                     => '1001843_Vanilla Latte (Ice) - KOPI KILEN (DRINKS)',
            'Vanila Latte'                             => '1001843_Vanilla Latte (Ice) - KOPI KILEN (DRINKS)',
            'Milk'                                     => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            'Oat Milk'                                 => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            'Oat Milk Ice'                             => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            'Oat Milk Hot'                             => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            'Soy Milk'                                 => '1001511_Soy Milk - KOPI KILEN (DRINKS)',
            'Soy Milk Ice'                             => '1001511_Soy Milk - KOPI KILEN (DRINKS)',
            'Soy Milk Hot'                             => '1001511_Soy Milk - KOPI KILEN (DRINKS)',
            

            // MEALS
            'Ayam Bakar'                               => '1001769_Ayam Bakar - KOPI KILEN (MEALS)',
            'Ayam Geprek'                              => '1001770_Ayam Geprek - KOPI KILEN (MEALS)',
            'Banana Caramel'                           => '1001702_Banana Caramel - KOPI KILEN (MEALS)',
            'Bubur Ayam'                               => '1001771_Bubur Ayam - KOPI KILEN (MEALS)',
            'Chicken Blackpapper'                      => '1001772_Chicken Blackpepper - KOPI KILEN (MEALS)',
            'Chicken Blackpepper'                      => '1001772_Chicken Blackpepper - KOPI KILEN (MEALS)',
            'Chicken Katsu Rice Bowl'                  => '1001773_Chicken Katsu Rice Bowl - KOPI KILEN (MEALS)',
            'Chicken Satay'                            => '1001774_Chicken Satay - KOPI KILEN (MEALS)',
            'Sate Ayam'                                => '1001774_Chicken Satay - KOPI KILEN (MEALS)',
            'Chocolate Cake with Espresso'             => '1001703_Chocolate Cake with Espresso - KOPI KILEN (MEALS)',
            'Cireng'                                   => '1001775_Cireng - KOPI KILEN (MEALS)',
            'Ikan Dori Asam Manis'                     => '1001776_Dori Asam Manis - KOPI KILEN (MEALS)',
            'Dori Asam Manis'                          => '1001776_Dori Asam Manis - KOPI KILEN (MEALS)',
            'Dori Sambal Matah'                        => '1001778_Dori Sambal Matah - KOPI KILEN (MEALS)',
            'French Fries'                             => '1001779_French Fries - KOPI KILEN (MEALS)',
            'Internet Goreng Kilen'                    => '1001780_Internet Goreng Kilen - KOPI KILEN (MEALS)',
            'Internet Rebus'                           => '1001781_Internet Rebus - KOPI KILEN (MEALS)',
            'Internet Rebus Kilen'                     => '1001781_Internet Rebus - KOPI KILEN (MEALS)',
            'Lontong Sayur'                            => '1001782_Lontong Sayur - KOPI KILEN (MEALS)',
            'Mie Ayam Pangsit'                         => '1001783_Mie Ayam - KOPI KILEN (MEALS)',
            'Mie Goreng Ayam Betutu'                   => '1001784_Mie Goreng Ayam Betutu - KOPI KILEN (MEALS)',
            'Nasi Goreng'                              => '1001785_Nasi Goreng Kilen - KOPI KILEN (MEALS)',
            'Nasi Goreng Sapi'                         => '1001786_Nasi Goreng Sapi - KOPI KILEN (MEALS)',
            'Nasi Goreng Kilen'                        => '1001785_Nasi Goreng Kilen - KOPI KILEN (MEALS)',
            'Pisang Goreng'                            => '1001787_Pisang Goreng - KOPI KILEN (MEALS)',
            'Bundling Roti Bakar'                      => '1001789_Roti Bakar Bundling - KOPI KILEN (MEALS)',
            'Soto Ayam'                                => '1001790_Soto Ayam - KOPI KILEN (MEALS)',
            'Soto betawi'                              => '1001791_Soto Betawi - KOPI KILEN (MEALS)',
            'Soto Betawi'                              => '1001791_Soto Betawi - KOPI KILEN (MEALS)',
            'Spicy Chicken Bites'                      => '1001792_Spicy Chicken Bites - KOPI KILEN (MEALS)',
            'Tahu isi'                                 => '1001793_Tahu Isi Sayur - KOPI KILEN (MEALS)',
            'Tahu Lada Garam'                          => '1001794_Tahu Lada Garam - KOPI KILEN (MEALS)',
            'Tempe Goreng'                             => '1001795_Tempe Goreng - KOPI KILEN (MEALS)',
            'Toast Kaya Butter'                        => '1001796_Toast Kaya Butter - KOPI KILEN (MEALS)',
            'Ubi Goreng'                               => '1001797_Ubi Goreng - KOPI KILEN (MEALS)',
            'Kwetiau Goreng'                           => '1002296_Kwetiau Goreng - KOPI KILEN (MEALS)',
            'Kwetiau Sapi'                             => '1002311_Kwetiau Goreng Sapi - KOPI KILEN (MEALS)',
            'Kwetiua Sapi'                             => '1002311_Kwetiau Goreng Sapi - KOPI KILEN (MEALS)',
            'Roti Bakar'                               => '1001788_Roti Bakar - KOPI KILEN (MEALS)',
            'Sop Sapi'                                 => '1002487_Sop Sapi - Kopi Kilen',
            'Toast'                                    => '1001796_Toast Kaya Butter - KOPI KILEN (MEALS)',

            // Add On
            'Egg'                                      => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'egg'                                      => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'Add Egg Chicken/Telur Ayam'               => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'Add Rice'                                 => '1002327_Add Rice - KOPI KILEN (MEALS)',
            'Add On Rice'                              => '1002327_Add Rice - KOPI KILEN (MEALS)',
            'Add on Rice'                              => '1002327_Add Rice - KOPI KILEN (MEALS)',
            'Add On Chicken'                           => '1002326_Add On Chicken - KOPI KILEN (MEALS)',
            'Add On Beef'                              => '1002325_Add On Beef - KOPI KILEN (MEALS)',
            'Add On Food'                              => '1002482_Add On Food - Kopi Kilen',
            'Additional Food'                          => '1002482_Add On Food - Kopi Kilen',
            'Add Syrup'                                => '1002483_Add on Syrup - Kopi Kilen',
            'Addtional Syrup'                          => '1002483_Add on Syrup - Kopi Kilen',

            // Perlengkapan
            'Paper Bag Kilen'                          => '1002214_Paper Bag Kilen - KOPI KILEN PERLENGKAPAN',
            'Spunbond Kilen'                           => '1002213_Paper Bag Hitam Spunbond - KOPI KILEN PERLENGKAPAN',
            'Spunbond Bag Kilen'                       => '1002213_Paper Bag Hitam Spunbond - KOPI KILEN PERLENGKAPAN',
            // 'Add On Beef'                              => '1002325_Add On Beef - KOPI KILEN (MEALS)',
            'TAKE AWAY HOT'                            => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Hot'            => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'TAKE AWAY HOT'                            => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Iced'                           => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
            'Take Away Bowl'                           => '1000819_Take Away Snack - KOPI KILEN (MEALS)',
            'Take Away Lunch Box'                      => '1000820_Take Away Food - KOPI KILEN (MEALS)',
            'Take Away Hot 08 Oz'                      => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Cup Hot 8 Oz'                   => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Iced 12 Oz'                     => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
            'Take Away Cup Iced 12 Oz'                 => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
        ];


        /* =========================================================
         * 6. BANGUN CSV OUTPUT
         * =======================================================*/

        $temp = fopen('php://temp', 'r+');
        fputcsv($temp, $headers);
        $masterWritten = [];
        $lineNoByTrx   = [];
        $bulanIndo = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        foreach ($logicalRows as $row) {
            $trxCode   = $row['trx'];
            $nameRaw   = $row['name'];
            $qty       = (float) $row['qty'];
            $unitPrice = (float) $row['unitPrice'];
            $customer = $current['customer'];
            if (! isset($lineNoByTrx[$trxCode])) {
                $lineNoByTrx[$trxCode] = 10;
            } else {
                $lineNoByTrx[$trxCode] += 10;
            }
            $lineNo = $lineNoByTrx[$trxCode];
            $sum = $summaryMap[$trxCode] ?? [
                'date'     => '',
                'discount' => '0',
                'service'  => '0',
                'rounding' => '0',
                // 'customer' => '',
            ];

            $discountAmount = (float) $sum['discount'];

            $descriptionDiscount = $discountAmount == 0.0 ? '' : 'Diskon';

            $description = '';
            $tanggalIndo = '';
            $bulanText   = '';

            if ($sum['date'] !== '') {
                [$year, $month, $day] = explode('-', $sum['date']);
                $monthNum  = (int) $month;
                $bulanText = $bulanIndo[$monthNum] ?? $month;
                $tanggalIndo = sprintf('%02d %s %04d', (int) $day, $bulanText, (int) $year);
            }

            $customer = trim($row['customer'] ?? '');

            if ($tanggalIndo !== '') {
                if ($customer === '') {
                    $description = 'Migrasi Quinos Transaksi Bulan ' . $bulanText . '_' . $tanggalIndo;
                } else {
                    $description = 'an ' . $customer . ' (Migrasi Quinos Transaksi ' . $tanggalIndo . ')';
                }
            }
            $cleanName = $this->normalizeProductName($nameRaw);
            $productId = $productMap[$cleanName] ?? $cleanName;
            $productTrim = explode('_', $productId)[0];

            $outputRow = [
                0  => 'Head Office',
                1  => 'N',
                2  => '1000750',
                3  => $description,             // Description
                4  => $sum['date'],             // DateOrdered
                5  => $trxCode,                 // FORCA_POSID (pakai Invoice / Transaction #)
                6  => $sum['discount'],         // Total Discount
                7  => $descriptionDiscount,     // Description Discount
                8  => $sum['service'],          // Service Charge
                9  => $sum['rounding'],         // Rounding
                10 => '1113037 Temporary Transit Account',
                11 => '1000009',
                12 => '1000006',
                13 => 'KOPI KILEN SQ - JAKARTA',
                14 => 'Ely Ruknia Sari',
                15 => '303',
                16 => 'Y',
                17 => 'Food and Beverage',
                18 => 'Sales Exclude Tax',
                19 => '1. Immediate',
                20 => 'Head Office',
                21 => $lineNo,       // Line No: 10, 20, 30 per Trx Code
                22 => $productTrim,    // M_Product_ID[Value]
                23 => $unitPrice,    // PriceActual
                24 => $qty,          // QtyOrdered
                25 => 'PB1 10%',
                26 => 'Y',
            ];

            // Kalau Trx Code ini sudah pernah ditulis,
            // kosongkan kolom-kolom master (0 s/d 16)
            if (isset($masterWritten[$trxCode])) {
                foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19,] as $idx) {
                    $outputRow[$idx] = '';
                }
            } else {
                // pertama kali ketemu Trx Code ini
                $masterWritten[$trxCode] = true;
            }

            fputcsv($temp, $outputRow);
        }

        rewind($temp);
        $csvContent = stream_get_contents($temp);
        fclose($temp);

        $filename = 'Mapping_Quinos_2Files_' . date('Ymd_His') . '.csv';

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}