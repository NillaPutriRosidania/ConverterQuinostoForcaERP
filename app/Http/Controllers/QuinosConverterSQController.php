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

    /**
     * =========================================================
     * Tambahan Rule: merge base drink + (Oat Milk / Soy Milk)
     * - jika dalam 1 trx ada base drink yang punya varian Oat/Soy,
     *   dan ada row add-on "Oat Milk" / "Soy Milk", maka:
     *   1) row milk add-on tidak keluar sendiri
     *   2) base drink diganti jadi varian Oat/Soy
     *   3) unitPrice dijumlah (base + addon)
     * =========================================================
     */
    private function applyMilkAddonMerge(array $logicalRows): array
    {
        // Deteksi nama add-on milk dari detail
        $milkAddonsUpper = [
            'OAT MILK' => 'OAT',
            'SOY MILK' => 'SOY',
        ];

        // Daftar varian (key = nama produk varian yang akan dicari di productMap)
        // Di-generate dari master FG "finish good bom aktif kopi kilen"
        $milkVariants = [
            // Aren Latte
            'Aren Latte Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Aren Latte (Hot)'],
            'Aren Latte Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Aren Latte (Iced)'],
            'Aren Latte Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Aren Latte (Hot)'],
            'Aren Latte Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Aren Latte (Iced)'],
            'Aren Latte Oat Milk (Hot)' => [
                'milk' => 'OAT',
                'base' => 'Aren Latte Fresh Milk (Hot)',
            ],
            
            'Aren Latte Oat Milk (Iced)' => [
                'milk' => 'OAT',
                'base' => 'Aren Latte Fresh Milk (Iced)',
            ],
            
            'Aren Latte Soy Milk (Hot)' => [
                'milk' => 'SOY',
                'base' => 'Aren Latte Fresh Milk (Hot)',
            ],
            
            'Aren Latte Soy Milk (Iced)' => [
                'milk' => 'SOY',
                'base' => 'Aren Latte Fresh Milk (Iced)',
            ],
            

            // Cafe Latte
            'Cafe Latte Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Cafe Latte (Hot)'],
            'Cafe Latte Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Cafe Latte (Iced)'],
            'Cafe Latte Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Cafe Latte (Hot)'],
            'Cafe Latte Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Cafe Latte (Iced)'],

            // Cappuccino
            'Cappuccino Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Cappuccino (Hot)'],
            'Cappuccino Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Cappuccino (Iced)'],
            'Cappuccino Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Cappuccino (Hot)'],
            'Cappuccino Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Cappuccino (Iced)'],

            // Caramel Latte
            'Caramel Latte Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Caramel Latte (Hot)'],
            'Caramel Latte Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Caramel Latte (Iced)'],
            'Caramel Latte Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Caramel Latte (Hot)'],
            'Caramel Latte Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Caramel Latte (Iced)'],

            // Chocolate
            'Chocolate Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Chocolate (Hot)'],
            'Chocolate Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Chocolate (Iced)'],
            'Chocolate Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Chocolate (Hot)'],
            'Chocolate Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Chocolate (Iced)'],
            
            // Flat White
            'Flat White Oat Milk' => ['milk' => 'OAT', 'base' => 'Flat White'],
            'Flat White Soy Milk' => ['milk' => 'SOY', 'base' => 'Flat White'],
            
            // ✅ alias untuk base yang sering muncul sebagai Flat White (Hot)
            'Flat White Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Flat White (Hot)'],
            'Flat White Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Flat White (Hot)'],



            // Green Tea
            'Green Tea Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Green Tea (Hot)'],
            'Green Tea Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Green Tea (Iced)'],
            'Green Tea Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Green Tea (Hot)'],
            'Green Tea Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Green Tea (Iced)'],
            'Green Tea Latte Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Green Tea Latte'],
'Green Tea Latte Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Green Tea Latte'],


            // Hazelnut Latte
            'Hazelnut Latte Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Hazelnut Latte (Hot)'],
            'Hazelnut Latte Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Hazelnut Latte (Iced)'],
            'Hazelnut Latte Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Hazelnut Latte (Hot)'],
            'Hazelnut Latte Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Hazelnut Latte (Iced)'],

            // Macchiato (master varian hot only)
            'Macchiato Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Macchiato'],
            'Macchiato Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Macchiato'],

            // Mocha Latte
            'Mocha Latte Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Mocha Latte (Hot)'],
            'Mocha Latte Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Mocha Latte (Iced)'],
            'Mocha Latte Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Mocha Latte (Hot)'],
            'Mocha Latte Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Mocha Latte (Iced)'],

            
            // Piccolo
            'Piccolo Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Piccolo (Hot)'],
            'Piccolo Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Piccolo (Hot)'],


            // Red Velvet
            'Red Velvet Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Red Velvet (Hot)'],
            'Red Velvet Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Red Velvet (Iced)'],
            'Red Velvet Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Red Velvet (Hot)'],
            'Red Velvet Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Red Velvet (Iced)'],

            // Vanilla Latte
            'Vanilla Latte Oat Milk (Hot)' => ['milk' => 'OAT', 'base' => 'Vanilla Latte (Hot)'],
            'Vanilla Latte Oat Milk (Iced)' => ['milk' => 'OAT', 'base' => 'Vanilla Latte (Iced)'],
            'Vanilla Latte Soy Milk (Hot)' => ['milk' => 'SOY', 'base' => 'Vanilla Latte (Hot)'],
            'Vanilla Latte Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Vanilla Latte (Iced)'],

            // Creamy Dopio Espresso (master tulis "iced" kecil)
            'Creamy Dopio Espresso Soy Milk (Iced)' => ['milk' => 'SOY', 'base' => 'Creamy Dopio Espresso'],
            'Creamy Dopio Espresso Oat Milk iced (Iced)' => ['milk' => 'OAT', 'base' => 'Creamy Dopio Espresso'],
            
            
        ];

        // Build base->variant map
        $baseToVariant = [];
        foreach ($milkVariants as $variantName => $info) {
            $base = $info['base'];
            $milk = $info['milk'];
            $baseToVariant[$base][$milk] = $variantName;
        }
        // ✅ alias: kalau Quinos tulis "Piccolo" tanpa (Hot), anggap sama dengan Piccolo (Hot)
if (isset($baseToVariant['Piccolo (Hot)'])) {
    $baseToVariant['Piccolo']['OAT'] = $baseToVariant['Piccolo (Hot)']['OAT'] ?? null;
    $baseToVariant['Piccolo']['SOY'] = $baseToVariant['Piccolo (Hot)']['SOY'] ?? null;
}

        // Group by trx
        $byTrx = [];
        foreach ($logicalRows as $r) {
            $byTrx[$r['trx']][] = $r;
        }

        $out = [];

foreach ($byTrx as $trx => $rows) {
    $used = array_fill(0, count($rows), false);

    // simpan index milk addon yang belum kepakai
    $milkIndexes = [];

    for ($i = 0; $i < count($rows); $i++) {
        if ($used[$i]) continue;

        $row = $rows[$i];
        $name = trim($row['name'] ?? '');
        $nameNorm = $this->normalizeProductName($name);

        // normalisasi "Caffe" -> "Cafe"
        if (stripos($nameNorm, 'Caffe Latte') === 0) {
            $nameNorm = 'Cafe Latte' . substr($nameNorm, strlen('Caffe Latte'));
        }

        $nameUpper = strtoupper($nameNorm);

        // ✅ kalau ini milk addon, JANGAN dibuang.
        // kita simpan dulu, nanti kalau tidak kepakai akan dikeluarkan.
        if (isset($milkAddonsUpper[$nameUpper])) {
            $milkIndexes[] = $i;
            continue;
        }

        // cari milk addon yang bisa merge (dan belum dipakai)
        $milkTypeFound = null;
        $milkPrice = 0.0;
        $milkIdx = null;

        foreach ($milkIndexes as $idx) {
            if ($used[$idx]) continue;

            $mNameNorm  = $this->normalizeProductName(trim($rows[$idx]['name'] ?? ''));
            $mNameUpper = strtoupper($mNameNorm);

            if (!isset($milkAddonsUpper[$mNameUpper])) continue;

            $milkTypeCandidate = $milkAddonsUpper[$mNameUpper];

            if (isset($baseToVariant[$nameNorm][$milkTypeCandidate])) {
                $milkTypeFound = $milkTypeCandidate;
                $milkPrice = (float) ($rows[$idx]['unitPrice'] ?? 0);
                $milkIdx = $idx;
                break; // 1 base cuma 1 milk
            }
        }

        // ✅ kalau ketemu pasangan milk → merge
        if ($milkTypeFound !== null && $milkIdx !== null) {
            $variantName = $baseToVariant[$nameNorm][$milkTypeFound];

            $baseQty = (float) ($row['qty'] ?? 1);
            if ($baseQty <= 0) $baseQty = 1;

            $addonQty = (float) ($rows[$milkIdx]['qty'] ?? 1);
            if ($addonQty <= 0) $addonQty = 1;

            $mergeQty = min($baseQty, $addonQty);

            $merged = $row;
            $merged['name'] = $variantName;
            $merged['qty'] = $mergeQty;
            $merged['unitPrice'] = (float) ($row['unitPrice'] ?? 0) + $milkPrice;

            $out[] = $merged;

            // sisa base kalau ada
            $remainingQty = $baseQty - $mergeQty;
            if ($remainingQty > 0) {
                $baseRemain = $row;
                $baseRemain['qty'] = $remainingQty;
                $baseRemain['name'] = $nameNorm;
                $out[] = $baseRemain;
            }

            $used[$i] = true;
            $used[$milkIdx] = true;
            continue;
        }

        // ✅ no merge → base normal keluar
        $row['name'] = $nameNorm;
        $out[] = $row;
        $used[$i] = true;
    }

    // ✅ terakhir: keluarkan milk addon yang tidak kepakai merge
    foreach ($milkIndexes as $idx) {
        if ($used[$idx]) continue;
        $r = $rows[$idx];
        $r['name'] = $this->normalizeProductName(trim($r['name'] ?? ''));
        $out[] = $r;
        $used[$idx] = true;
    }
}

        return $out;
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
        $sumTrxIndex   = array_search('Transaction #', $summaryHeader);
        $sumDateIndex  = array_search('Date', $summaryHeader);
        $sumDiscIndex  = array_search('Discount', $summaryHeader);
        $sumSvcIndex   = array_search('Serv Chg', $summaryHeader);
        $sumRoundIndex = array_search('Rounding', $summaryHeader);
        $sumPaxIndex   = array_search('Pax', $summaryHeader);


        if (
            $sumTrxIndex   === false ||
            $sumDateIndex  === false ||
            $sumDiscIndex  === false ||
            $sumSvcIndex   === false ||
            $sumRoundIndex === false ||
            $sumPaxIndex   === false
        ) {
            fclose($handleSummary);
            abort(
                500,
                'Kolom summary tidak lengkap. Pastikan ada "Transaction #, Date, Discount, Serv Chg, Rounding". Header: '
                . implode(', ', $summaryHeader)
            );
        }

        // kolom "customer = di sebelah kiri Date"
        $customerIndex = $sumDateIndex > 0 ? $sumDateIndex - 1 : null;

        $summaryMap = [];

        while (($row = fgetcsv($handleSummary)) !== false) {

            $paxRaw = isset($row[$sumPaxIndex]) ? trim($row[$sumPaxIndex]) : '1';
            $pax = (float) str_replace(',', '', $paxRaw);
            if ($pax <= 0) $pax = 1;

            if (! isset($row[$sumTrxIndex])) {
                continue;
            }

            $trxCode = trim($row[$sumTrxIndex]);
            if ($trxCode === '') {
                continue;
            }

            $dateRaw  = isset($row[$sumDateIndex])  ? trim($row[$sumDateIndex])  : '';
            $discRaw  = isset($row[$sumDiscIndex])  ? trim($row[$sumDiscIndex])  : '0';
            $svcRaw   = isset($row[$sumSvcIndex])   ? trim($row[$sumSvcIndex])   : '0';
            $roundRaw = isset($row[$sumRoundIndex]) ? trim($row[$sumRoundIndex]) : '0';

            $customerRaw = '';
            if ($customerIndex !== null && isset($row[$customerIndex])) {
                $customerRaw = trim($row[$customerIndex]);
            }

            $dateFormatted = '';

            if ($dateRaw !== '') {
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

            $disc  = str_replace(',', '', $discRaw);
            $svc   = str_replace(',', '', $svcRaw);
            $round = str_replace(',', '', $roundRaw);

            $summaryMap[$trxCode] = [
                'date'     => $dateFormatted,
                'discount' => $disc,
                'service'  => $svc,
                'rounding' => $round,
                'customer' => $customerRaw,
                'pax'      => $pax,
            ];
        }

        fclose($handleSummary);

        /* =========================================================
         * 3. BACA FILE DETAIL (Export Transaction)
         * =======================================================*/

        $handleDetail = fopen($pathDetail, 'r');
        if (! $handleDetail) {
            abort(500, 'Gagal membuka file detail.');
        }

        $inputHeader = fgetcsv($handleDetail);
        if ($inputHeader === false) {
            abort(500, 'Header CSV detail tidak bisa dibaca.');
        }
        $inputHeader = array_map('trim', $inputHeader);

        $trxIndex   = array_search('Invoice', $inputHeader);
        $qtyIndex   = array_search('Quantity', $inputHeader);
        $priceIndex = array_search('UnitPrice', $inputHeader);
        $nameIndex  = array_search('Description', $inputHeader);
        $catIndex   = array_search('Category', $inputHeader);
        $deptIndex  = array_search('Department', $inputHeader);
        $custIndex  = array_search('Customer', $inputHeader);

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

        $modifierNotes = [
            '3.4','12','34','45659','45720','1 MNS','1 PDS','1 SMBEL MATAH','1 TDK PKE BWNG GRG','1 TLR MTG','1/2 MTG',
            '1 DINGIN','1 ES','1 GK PKE SAYUR','1 GPKE KORNET','1 LES ICE&UGAR','1 LS','1 MINYAK DIKIT','1MNS','2 CEPLOK MTG',
            '2 PEDES','2 SEDENG 1 PEDES','2MTG','3 LS (Price actual 4)','3/4 JGN ENCER','8 MNT','8MNT','ACEH','ADD ICE',
            'BUMBU JGN BANYAK','CABE PISAH','CBE PISAH','CEPLOK 1/2 MTG','CEPLOK MTG','CHANGE GAYO','COKLAT AJA','DADAR',
            'DDR (price actual 1)','DIBUAT NNTI','DIKLUARIN JAM 1','DINGIN','DOUBLE','EXTRA ICE','GAK PEDES','GARING',
            'GAPAKE KORNET. MIENYA SETENGAH MATENG','GAUSAH DIBUAT','GAYO','GAYO 2 PACK','GK PDES','GK PDS','GK PEDES',
            'GK PKE CABE','GK PKE GULA','GK PKE SAYUR (price 1)','GK PKE TELUR..','GPKE RAWIT','GPKE TELOR','GULA IKIT',
            'HOT','ICE','ICED','JD 1 CUP','JGN DIBIKIN','JGN PKE CABE','JGN PKE COKLAT','JGN PKE KECAP','JGN PKE SAYUR',
            'Kalibrasi - Espresso','Kalibrasi - Latte','KCAP DIKIT','KERING','KERING TKWWWWW','KERIS','KERUPUK',
            'KOENET TELUR D DADAR','KORNET DADAR','KUAH DIBANYAKIN','L SUSU','LC','LECI','LEMON','LEMONADE','LES ICE',
            'LESS COFE','LESSWEET','LI','LILS','LMON','LS','LS LC','LS LI','M','MANDAILING','MANIS','MATANG','MATENG','MEDIU',
            'MEDIUM','MINYAK DIKIT','MNS','MS','MTG','MTG JNG BYK MINYAK','MTG PEDES','MTG REBUS','NASI 1/2','NASI STGH',
            'NO CABE','NO CARAMEL','NO ICING','NO KORNET','NO SAYUR','NO SAYUR NO DAUNG BWG','NO SAYUR NO KORNET',
            'NORMAL SUGAR','ON THE ROCK','PAKE RAWIT','PDS','PDS MNS','PEACH','PEACH 2','PEDAS','PEDES','PEES','PUTIH AJA',
            'PUTIHNYA AJA','RAWIT','REBUS','REBUS MATENG','REBUS MTG','SAMBEL PISAH','SAYURAN','SDG','SDIKIT','SDNG','SEDANG',
            'SMBL PISAH','iSTGH MTG','SWEET','T','TA','TAKE AWAY SEMUA','TAKEAWZY','TANPA CABE','TANPA CABE KORNET','TDK PDS',
            'TDK PKE BWNG GORENG','TDK PKE DAUN BWNG','TDK PKE GULA','TDK PKE KECAP','TDK PKE KRNT','TDK PKE NASI..',
            'TDK PKE PISNG','TELOR DDR','TELOR MTG','TIMUN TOMAT','TIPIS','TIPIS KERING','TKW','TKWWW','TKWWWWW',
            'TLR 1/2 MTG','TLR 3/4','TLR DADAR','TLR GANTI KORNET','TLR MTG','TLR MTG/TNP SAYUR','TLRNYA 1/2 MTG',
            'TNPA GULA','TNPA KORNET','TNPA KORNET & SYR','TNPA NASI','TNPA PISANG','TNPACABE','TWR','TANPA KORNET','TIMUN',
            'TDK PKE NASI','3 LS','DDR','PDS  MNS','TANPA KORNET TANPA SAYUR','TANPA SAYUR','SEDENG','TDK PEDES','STGH MTG',
            'PISAH GULA','TNPA KRNT','GK PKE TELUR','TANPA KORNET','JGN BERMINYAK','LI LS','GK PKE SAYUR', '1 MTG PDS',
        ];

        $modifierNotesUpper = array_map(function ($v) {
            return strtoupper(trim($v));
        }, $modifierNotes);

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

        $rawRows = [];

        while (($row = fgetcsv($handleDetail)) !== false) {
            if (! isset($row[$trxIndex])) {
                continue;
            }

            $trxCode = trim($row[$trxIndex]);

            // ✅ skip kalau kosong
            if ($trxCode === '') {
                continue;
            }

            // ✅ skip kalau invoice mengandung VOID
            if (stripos($trxCode, 'VOID') !== false) {
                continue;
            }

            // ✅ skip kalau invoice bukan angka (misalnya "VOID 103282", "CANCEL", dll)
            if (!ctype_digit($trxCode)) {
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

            // ✅ FIX: UnitPrice dari CSV sudah per item, jadi jangan dibagi qty
            $unitPrice = $price;

            if ($this->isTakeAwayZeroRow($current)) {
                continue;
            }

            if ($this->isModifierRow($current, $modifierNotesUpper)) {
                continue;
            }

            if (in_array($name, $comboNames, true)) {

                $childrenIndex = [];
                $j = $i + 1;

                while ($j < $totalRaw) {
                    $next = $rawRows[$j];

                    // trx beda → stop
                    if ($next['trx'] !== $trxCode) break;

                    // skip modifier & takeaway item
                    if ($this->isModifierRow($next, $modifierNotesUpper)) {
                        $j++;
                        continue;
                    }

                    if ($this->isTakeAwayItem($next)) {
                        $j++;
                        continue;
                    }

                    // ketemu paket lagi → stop
                    if (in_array($next['name'], $comboNames, true)) break;

                    // ✅ hanya ambil anak paket price=0
                    if ((float) $next['price'] == 0.0) {
                        $childrenIndex[] = $j;
                        $j++;
                        continue;
                    }

                    // ✅ ketemu item normal price>0 → stop scan
                    break;
                }

                $childCount = count($childrenIndex);

                if ($childCount > 0 && $qty > 0 && $price > 0) {
                    $unitPriceChild = $price / $childCount;

                    foreach ($childrenIndex as $idxChild) {
                        $child = $rawRows[$idxChild];

                        $logicalRows[] = [
                            'trx'          => $trxCode,
                            'name'         => trim($child['name']),
                            'qty'          => $child['qty'] > 0 ? $child['qty'] : $qty,
                            'unitPrice'    => $unitPriceChild,
                            'customer'     => $child['customer'] ?? '',
                            'category'     => $child['category'] ?? '',
                            'is_combo_child' => true,
                        ];

                    }
                }

                // ✅ lompat hanya sampai anak paket terakhir
                if ($childCount > 0) {
                    $i = end($childrenIndex);
                }

                continue;
            }

            $logicalRows[] = [
                'trx'          => $trxCode,
                'name'         => $name,
                'qty'          => $qty,
                'unitPrice'    => $unitPrice,
                'customer'     => $current['customer'],
                'category'     => $current['category'] ?? '',
                'is_combo_child' => false,
            ];

        }

        // =========================================================
        // Tambahan: merge base drink + (Oat Milk / Soy Milk)
        // =========================================================
        $logicalRows = $this->applyMilkAddonMerge($logicalRows);

        /* =========================================================
         * 4. HEADER OUTPUT
         * =======================================================*/

$headers = [
    'AD_Org_ID[Name]',
    'FORCA_TransactionType',
    'C_BPartner_ID[Value]',
    'Description',
    'DateOrdered',
    'FORCA_POSID',
    'total_discount',
    'FORCA_DescriptionDisc',
    'FORCA_ServiceCharge',
    'FORCA_RoundingAmt',
    'C_BankAccount_ID[Value]',
    'C_Project_ID[Value]',
    'C_Activity_ID[Value]',
    'M_Warehouse_ID[Value]',
    'SalesRep_ID[Name]',
    'C_Currency_ID',
    'IsActive',
    'C_DocType_ID[Name]',
    'M_PriceList_ID[Name]',
    'C_PaymentTerm_ID[Value]',
    'FORCA_ImportSalesPOSLine>AD_Org_ID[Name]',
    'FORCA_ImportSalesPOSLine>LineNo',
    'FORCA_ImportSalesPOSLine>M_Product_ID[Value]',
    'FORCA_ImportSalesPOSLine>PriceActual',
    'FORCA_ImportSalesPOSLine>QtyOrdered',
    'FORCA_ImportSalesPOSLine>C_Tax_ID[Name]',
    'FORCA_ImportSalesPOSLine>IsActive',
];


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
            'Cafe Latte'                              => '1001807_Cafe Latte (Iced) - KOPI KILEN (DRINKS)',
            'Cafe Latte (Iced)'                       => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',
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
            'Espresso Shot'                            => '1002337_Add on Essppresso - KOPI KILEN (DRINKS)',
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
            // 'Milk'                                     => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            // 'Oat Milk'                                 => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            // 'Oat Milk Ice'                             => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            // 'Oat Milk Hot'                             => '1001512_Oat Milk - KOPI KILEN (DRINKS)',
            // 'Soy Milk'                                 => '1001511_Soy Milk - KOPI KILEN (DRINKS)',
            // 'Soy Milk Ice'                             => '1001511_Soy Milk - KOPI KILEN (DRINKS)',
            // 'Soy Milk Hot'                             => '1001511_Soy Milk - KOPI KILEN (DRINKS)',
            'Soy Milk' => '1002740_Add on Soy Milk 150Ml - KOPI KILEN (DRINKS)',
            'Oat Milk' => '1002737_Add on Oat Milk 150Ml - KOPI KILEN (DRINKS)',

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

            // Perlengkapan
            'Paper Bag Kilen'                          => '1002214_Paper Bag Kilen - KOPI KILEN PERLENGKAPAN',
            'Spunbond Kilen'                           => '1002213_Paper Bag Hitam Spunbond - KOPI KILEN PERLENGKAPAN',
            'Spunbond Bag Kilen'                       => '1002213_Paper Bag Hitam Spunbond - KOPI KILEN PERLENGKAPAN',
            'TAKE AWAY HOT'                            => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Hot'                            => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Iced'                           => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
            'Take Away Bowl'                           => '1000819_Take Away Snack - KOPI KILEN (MEALS)',
            'Take Away Lunch Box'                      => '1000820_Take Away Food - KOPI KILEN (MEALS)',
            'Take Away Hot 08 Oz'                      => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Cup Hot 8 Oz'                   => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Take Away Iced 12 Oz'                     => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
            'Take Away Cup Iced 12 Oz'                 => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',

            /* =========================================================
             * Tambahan mapping varian Oat/Soy (DRINKS)
             * =======================================================*/
            'Aren Latte Oat Milk (Hot)'                => '1002342_Aren Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Aren Latte Oat Milk (Iced)'               => '1002343_Aren Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Aren Latte Soy Milk (Hot)'                => '1002344_Aren Latte Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Aren Latte Soy Milk (Iced)'               => '1002345_Aren Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Cafe Latte Soy Milk (Hot)'                => '1002676_Cafe Latte Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Cafe Latte Oat Milk (Hot)'                => '1002677_Cafe Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Cafe Latte Oat Milk (Iced)'               => '1002678_Cafe Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Cafe Latte Soy Milk (Iced)'               => '1002679_Cafe Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Cappuccino Soy Milk (Hot)'                => '1002680_Cappuccino Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Cappuccino Oat Milk (Hot)'                => '1002681_Cappuccino Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Cappuccino Oat Milk (Iced)'               => '1002682_Cappuccino Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Cappuccino Soy Milk (Iced)'               => '1002683_Cappuccino Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Caramel Latte Soy Milk (Hot)'             => '1002684_Caramel Latte Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Caramel Latte Oat Milk (Hot)'             => '1002685_Caramel Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Caramel Latte Oat Milk (Iced)'            => '1002686_Caramel Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Caramel Latte Soy Milk (Iced)'            => '1002687_Caramel Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Chocolate Soy Milk (Hot)'                 => '1002688_Chocolate Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Chocolate Oat Milk (Hot)'                 => '1002689_Chocolate Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Chocolate Oat Milk (Iced)'                => '1002690_Chocolate Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Chocolate Soy Milk (Iced)'                => '1002691_Chocolate Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Flat White Soy Milk'                      => '1002692_Flat White Soy Milk - KOPI KILEN (DRINKS)',
            'Flat White Oat Milk'                      => '1002693_Flat White Oat Milk - KOPI KILEN (DRINKS)',
            'Flat White Oat Milk (Hot)' => '1002693_Flat White Oat Milk - KOPI KILEN (DRINKS)',
'Flat White Soy Milk (Hot)' => '1002692_Flat White Soy Milk - KOPI KILEN (DRINKS)',


            'Green Tea Soy Milk (Hot)'                 => '1002694_Green Tea Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Green Tea Oat Milk (Hot)'                 => '1002695_Green Tea Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Green Tea Soy Milk (Iced)'                => '1002696_Green Tea Soy Milk (Iced) - KOPI KILEN (DRINKS)',
            'Green Tea Oat Milk (Iced)'                => '1002697_Green Tea Oat Milk (Iced) - KOPI KILEN (DRINKS)',

            'Hazelnut Latte Soy Milk (Hot)'            => '1002698_Hazelnut Latte Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Hazelnut Latte Oat Milk (Hot)'            => '1002699_Hazelnut Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Hazelnut Latte Soy Milk (Iced)'           => '1002700_Hazelnut Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',
            'Hazelnut Latte Oat Milk (Iced)'           => '1002701_Hazelnut Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',

            'Macchiato Soy Milk (Hot)'                 => '1002702_Macchiato Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Macchiato Oat Milk (Hot)'                 => '1002703_Macchiato Oat Milk (Hot) - KOPI KILEN (DRINKS)',

            'Mocha Latte Soy Milk (Hot)'               => '1002704_Mocha Latte Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Mocha Latte Oat Milk (Hot)'               => '1002705_Mocha Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Mocha Latte Oat Milk (Iced)'              => '1002706_Mocha Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Mocha Latte Soy Milk (Iced)'              => '1002707_Mocha Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Piccolo Soy Milk (Hot)'                   => '1002708_Piccolo Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Piccolo Oat Milk (Hot)'                   => '1002709_Piccolo Oat Milk (Hot) - KOPI KILEN (DRINKS)',

            'Red Velvet Soy Milk (Hot)'                => '1002710_Red Velvet Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Red Velvet Oat Milk (Hot)'                => '1002711_Red Velvet Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Red Velvet Oat Milk (Iced)'               => '1002712_Red Velvet Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Red Velvet Soy Milk (Iced)'               => '1002713_Red Velvet Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Vanilla Latte Soy Milk (Hot)'             => '1002714_Vanilla Latte Soy Milk (Hot) - KOPI KILEN (DRINKS)',
            'Vanilla Latte Oat Milk (Hot)'             => '1002715_Vanilla Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
            'Vanilla Latte Oat Milk (Iced)'            => '1002716_Vanilla Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
            'Vanilla Latte Soy Milk (Iced)'            => '1002717_Vanilla Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',

            'Creamy Dopio Espresso Soy Milk (Iced)'     => '1002718_Creamy Dopio Espresso Soy Milk (Iced) - KOPI KILEN (DRINKS)',
            'Creamy Dopio Espresso Oat Milk iced (Iced)' => '1002719_Creamy Dopio Espresso Oat Milk iced (Iced) - KOPI KILEN (DRINKS)',
            'Add On Lemon Juice Syrup 1L' => '1002336_Add on Lemon Juice Syrup 1L - KOPI KILEN (DRINKS)',
            'Milk' => '1002720_Add On Milk - KOPI KILEN (DRINKS)',
            'Add On Food' => '1002482_Add On Food - Kopi Kilen',
            'Additional Food' => '1002482_Add On Food - Kopi Kilen',
            'Addtional Syrup' => '1002483_Add on Syrup - Kopi Kilen',
            'Sanqua Mineral Water 330 Ml' => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
            'Add on Essppresso'                   => '1002337_Add on Essppresso - KOPI KILEN (DRINKS)',
'Aren Latte Fresh Milk (Iced)'        => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
'Esspreso'                            => '1001823_Esspreso - KOPI KILEN (DRINKS)',
'Aren Latte Fresh Milk (Hot)'         => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
'Creamy Dopio Espresso (Iced)'        => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
'Add on Caramel Syrup Delifru'        => '1002727_Add on Caramel Syrup - KOPI KILEN (DRINKS)',
'Daily Brew (V-60) Iced (Mandailin)'  => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
'Cup Ukuran 12 Oz'                    => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
'Add on Peach Syrup Delifru'          => '1002335_Add on Peach Syrup Delifru - KOPI KILEN (DRINKS)',
'Daily Brew (V-60) Hot (Trj Sapan)'   => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
'Daily Brew (V-60) Iced (Trj Sapan)'  => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
'Add on Palm Sugar Syrup Delifru'      => '1002728_Add on Palm Sugar Syrup - KOPI KILEN (DRINKS)',
'Mie Ayam'                            => '1001783_Mie Ayam - KOPI KILEN (MEALS)',
'Lemon Tea (Iced)'                    => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',
'Spunbond'                            => '1002213_Spunbond - KOPI KILEN PERLENGKAPAN',
'Tahu Isi Sayur'                      => '1001793_Tahu Isi Sayur - KOPI KILEN (MEALS)',
'Macchiato (Hot)'                     => '1001835_Macchiato (Hot) - KOPI KILEN (DRINKS)',
'Add on Lychee Syrup Delifru'          => '1002334_Add on Lychee Syrup Delifru - KOPI KILEN (DRINKS)',
'Peach Tea (Iced)'                    => '1001838_Peach Tea - KOPI KILEN (DRINKS)',
'Cold Brew Sweet'                     => '1001686_Cold Brew Sweet - KOPI KILEN (DRINKS)',
'Cold White Coconut'                  => '1001685_Cold White Coconut - KOPI KILEN (DRINKS)',
'Add On Milk'                         => '1002720_Add On Milk - KOPI KILEN (DRINKS)',
'Add Sambal Setan'                    => '1002332_Add Sambal Setan - KOPI KILEN (MEALS)',
'Cold White Caramel'                  => '1001684_Cold White Caramel - KOPI KILEN (DRINKS)',
'Cup Ukuran 8 Oz'                     => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
'Coca Cola @250Ml'                    => '1001698_Coca Cola @250Ml - KOPI KILEN (DRINKS)',
'Roti Bakar Bundling'                 => '1001789_Roti Bakar Bundling - KOPI KILEN (MEALS)',
'Red Velvet (Hot)'                    => '1001840_Red Velvet (Hot) - KOPI KILEN (DRINKS)',
'Add Ketupat'                         => '1002328_Add Ketupat - KOPI KILEN (MEALS)',
'Toraja Sapan Biji @250 Gr'            => '1001520_Toraja Sapan Biji @250 Gr - KOPI KILEN (DRINKS)',
'Milk (Iced)'                         => '1002017_Milk (Iced) - KOPI KILEN (DRINKS)',
'Add Cucumber / Timun'                => '1002730_Add Cucumber / Timun - KOPI KILEN (MEALS)',
'Lemon Tea (Hot)'                     => '1001832_Lemon Tea (Hot) - KOPI KILEN (DRINKS)',
// 'Lemon Tea (Hot)'                     => '1001832_Lemon Tea (Hot) - KOPI KILEN (DRINKS)',
'Equil Sparkling Water'             => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
'Pineapple Upside Down Cake'        => '1001702_Banana Caramel - KOPI KILEN (MEALS)',
'Add Tomat'                         => '1002751_Add on Tomat - KOPI KILEN (MEALS)',
'Add on Lemon Juice Syrup 1L'       => '1002336_Add on Lemon Juice Syrup 1L - KOPI KILEN (DRINKS)',
'Daily Brew (V-60) Iced (Mandailin' => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
'Daily Brew (V-60) Iced (Trj Sapan)n)' => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
'Aren Latte Fresh Milk (Hot)'
    => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
'Aren Latte Fresh Milk (Iced)'
    => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
    'Green Tea (Ice)'                     => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',
'Java Tea (Ice)'                      => '1001831_Java Tea (Ice) - KOPI KILEN (DRINKS)',
'Aren Latte Ice'                      => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
'Cafe Latte (Ice)'                    => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',
'Aren Latte Hot'                      => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
'Creamy Dopio Espresso Ice'           => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
'Daily Brew (V-60) Ice (Mandailin'    => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
'Chocolate (Ice)'                     => '1001813_Chocolate (Ice) - KOPI KILEN (DRINKS)',
'Daily Brew(V-60) Hot (Trj Sapan)'    => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
'Daily Brew(V-60) Ice (Trj Sapan)'    => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
'Vanilla Latte (Ice)'                 => '1001843_Vanilla Latte (Ice) - KOPI KILEN (DRINKS)',
'Lemon Tea (Ice)'                     => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',
'Red Velvet (Ice)'                    => '1001841_Red Velvet (Ice) - KOPI KILEN (DRINKS)',
'Daily Brew (V-60) Iced (Trj Sapa'    => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
'Kopi Kilend Blend Biji @250 Gr'      => '1002486_Kopi Kilen Blend 250g - Kopi Kilen',
'Gayo Bubuk @250 Gr'                  => '1002485_Aceh Gayo 250 Gr - Kopi Kilen',
'Kopi Kilend Blend Bubuk @250 Gr'     => '1002486_Kopi Kilen Blend 250g - Kopi Kilen',
'Daily Brew (V-60) Hot (Trj Sapan'    => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
'Add on Palm Sugar Syrup'             => '1002728_Add on Palm Sugar Syrup - KOPI KILEN (DRINKS)',

        ];

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
            $sum = $summaryMap[$trxCode] ?? [
                'date'     => '',
                'discount' => '0',
                'service'  => '0',
                'rounding' => '0',
                'pax' => 1
            ];
            $categoryUpper = strtoupper(trim($row['category'] ?? ''));
            $isComboChild  = (bool)($row['is_combo_child'] ?? false);
            
            // rule: PB1 0% hanya untuk Extra yang bukan combo
            $taxName = (!$isComboChild && $categoryUpper === 'EXTRA')
                ? 'PB1 0%'
                : 'PB1 10%';
            // $pax = isset($sum['pax']) ? (float) $sum['pax'] : 1;
            // if ($pax <= 0) $pax = 1;

            $qtyOutput = $qty;

            if (! isset($lineNoByTrx[$trxCode])) {
                $lineNoByTrx[$trxCode] = 10;
            } else {
                $lineNoByTrx[$trxCode] += 10;
            }
            $lineNo = $lineNoByTrx[$trxCode];

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
                3  => $description,
                4  => $sum['date'],
                5  => $trxCode,
                6  => $sum['discount'],
                7  => $descriptionDiscount,
                8  => $sum['service'],
                9  => $sum['rounding'],
                10 => '1113037',
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
                21 => $lineNo,
                22 => $productTrim,
                23 => $unitPrice,
                24 => $qtyOutput,
                25 => $taxName,
                26 => 'Y',
            ];

            if (isset($masterWritten[$trxCode])) {
                foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19] as $idx) {
                    $outputRow[$idx] = '';
                }
            } else {
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