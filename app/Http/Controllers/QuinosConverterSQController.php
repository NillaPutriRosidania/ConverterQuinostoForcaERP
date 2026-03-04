<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuinosConverterSQController extends Controller
{
    public function index()
    {
        return view('converter.convertSQ');
    }

    private function isNotes(array $row, array $modifierNotesUpper): bool
    {
        $nameUpper = strtoupper(trim($row['name'] ?? ''));

        if ($nameUpper !== '' && in_array($nameUpper, $modifierNotesUpper, true)) {
            return true;
        }

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

        if (in_array($nameUpper, $modifierNotesUpper, true)) {
            return true;
        }

        if ($category === 'MODIFIER' || $department === 'MODIFIER') {
            return true;
        }

        return false;
    }

    private function isTakeAwayZeroRow(array $row): bool
    {
        $name = trim($row['name'] ?? '');
        $price = (float) ($row['price'] ?? 0);

        $code = trim($row['code'] ?? '');
        $codeUpper = strtoupper($code);

        if (stripos($name, 'Take Away') !== 0) {
            return false;
        }
        if (
            strpos($codeUpper, 'TKW') !== 0
            || $codeUpper === 'TKW'
        ) {
            return false;
        }

        if ($price != 0.0) {
            return false;
        }

        return true;
    }

    private function isTakeAwayItem(array $row): bool
    {
        $name = trim($row['name'] ?? '');
        $code = strtoupper(trim($row['code'] ?? ''));

        if (stripos($name, 'Take Away') !== 0) {
            return false;
        }

        if (strpos($code, 'TKW') !== 0) {
            return false;
        }

        return true;
    }

    private function normalizeProductName(string $name): string
    {
        return trim(str_replace('#', '', $name));
    }

    private function roundMoney0(float $value): float
    {
        return (float) round($value, 0, PHP_ROUND_HALF_UP);
    }

        private function shouldPb0(array $r): bool
        {
            if (!empty($r['is_combo_child'])) return false;

            $category = strtoupper(trim($r['category'] ?? ''));
            $name     = trim($r['name'] ?? '');

            return ($category === 'EXTRA' || $name === 'Paper Bag Kilen');
        }
    public function convert(Request $request)
    {
        $request->validate([
            'file_detail'  => 'required|file|mimes:csv,txt',
            'file_summary' => 'required|file|mimes:csv,txt',
        ]);

        $pathDetail  = $request->file('file_detail')->getRealPath();
        $pathSummary = $request->file('file_summary')->getRealPath();

        $handleSummary = fopen($pathSummary, 'r');
        if (! $handleSummary) {
            abort(500, 'Gagal membuka file summary.');
        }

        $summaryHeader = fgetcsv($handleSummary);
        if ($summaryHeader === false) {
            abort(500, 'Header CSV summary tidak bisa dibaca.');
        }
        $summaryHeader = array_map('trim', $summaryHeader);

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
            if ($trxCode === '') {
                continue;
            }
            if (stripos($trxCode, 'VOID') !== false) {
                continue;
            }
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

        // =========================
        // invoiceFlags:
        // has_extra_or_ta => hanya kalau EXTRA berharga (price != 0) atau Take Away berharga (price != 0)
        // =========================
        $invoiceFlags = [];

        foreach ($rawRows as $r) {
            $trx = $r['trx'];

            if (!isset($invoiceFlags[$trx])) {
                $disc = (float) str_replace(',', '', $summaryMap[$trx]['discount'] ?? '0');
                $invoiceFlags[$trx] = [
                    'has_extra_or_ta' => false,
                    'has_discount'    => ($disc != 0.0),
                ];
            }

            $catUpper = strtoupper(trim($r['category'] ?? ''));
            $isExtra  = ($catUpper === 'EXTRA');

            $name = trim($r['name'] ?? '');
            $code = strtoupper(trim($r['code'] ?? ''));

            $isTakeAway = (stripos($name, 'Take Away') === 0) && (strpos($code, 'TKW') === 0);
            $price = (float) ($r['price'] ?? 0);

            $isExtraCharged      = $isExtra && ($price != 0.0);
            $isTakeAwayCharged   = $isTakeAway && ($price != 0.0);

            if ($isExtraCharged || $isTakeAwayCharged) {
                $invoiceFlags[$trx]['has_extra_or_ta'] = true;
            }
        }

        // =========================
        // Build logicalRows
        // =========================
        $logicalRows = [];
        $totalRaw    = count($rawRows);

        for ($i = 0; $i < $totalRaw; $i++) {
            $current = $rawRows[$i];

            $trxCode = $current['trx'];
            $name    = $current['name'];
            $qty     = $current['qty'];
            $price   = $current['price'];
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
                    if ($next['trx'] !== $trxCode) break;

                    if ($this->isModifierRow($next, $modifierNotesUpper)) {
                        $j++;
                        continue;
                    }

                    if ($this->isTakeAwayItem($next)) {
                        $j++;
                        continue;
                    }

                    if (in_array($next['name'], $comboNames, true)) break;

                    if ((float) $next['price'] == 0.0) {
                        $childrenIndex[] = $j;
                        $j++;
                        continue;
                    }
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

        // HEADER SHEET 1 & 2 (tanpa FORCA_TotalDiscNoTax)
        $headers12 = [
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

        // HEADER SHEET 3 (ada FORCA_TotalDiscNoTax)
        $headers3 = [
            'AD_Org_ID[Name]',
            'FORCA_TransactionType',
            'C_BPartner_ID[Value]',
            'Description',
            'DateOrdered',
            'FORCA_POSID',
            'total_discount',
            'FORCA_DescriptionDisc',
            'FORCA_TotalDiscNoTax',
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
            'Egg'                                      => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'egg'                                      => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'Add Egg Chicken/Telur Ayam'               => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'Add Rice'                                 => '1002327_Add Rice - KOPI KILEN (MEALS)',
            'Add On Rice'                              => '1002327_Add Rice - KOPI KILEN (MEALS)',
            'Add on Rice'                              => '1002327_Add Rice - KOPI KILEN (MEALS)',
            'Add On Chicken'                           => '1002326_Add On Chicken - KOPI KILEN (MEALS)',
            'Add On Beef'                              => '1002325_Add On Beef - KOPI KILEN (MEALS)',
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
            'Add On Lemon Juice Syrup 1L'              => '1002336_Add on Lemon Juice Syrup 1L - KOPI KILEN (DRINKS)',
            'Milk'                                     => '1002720_Add On Milk - KOPI KILEN (DRINKS)',
            'Add On Food'                              => '1002482_Add On Food - Kopi Kilen',
            'Additional Food'                          => '1002482_Add On Food - Kopi Kilen',
            'Addtional Syrup'                          => '1002483_Add on Syrup - Kopi Kilen',
            'Sanqua Mineral Water 330 Ml'              => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
            'Add on Essppresso'                         => '1002337_Add on Essppresso - KOPI KILEN (DRINKS)',
            'Aren Latte Fresh Milk (Iced)'              => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
            'Esspreso'                                  => '1001823_Esspreso - KOPI KILEN (DRINKS)',
            'Aren Latte Fresh Milk (Hot)'               => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
            'Creamy Dopio Espresso (Iced)'              => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
            'Add on Caramel Syrup Delifru'              => '1002727_Add on Caramel Syrup - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Iced (Mandailin)'        => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
            'Cup Ukuran 12 Oz'                          => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
            'Add on Peach Syrup Delifru'                => '1002335_Add on Peach Syrup Delifru - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Hot (Trj Sapan)'         => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Iced (Trj Sapan)'        => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Add on Palm Sugar Syrup Delifru'           => '1002728_Add on Palm Sugar Syrup - KOPI KILEN (DRINKS)',
            'Mie Ayam'                                  => '1001783_Mie Ayam - KOPI KILEN (MEALS)',
            'Lemon Tea (Iced)'                          => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',
            'Spunbond'                                  => '1002213_Spunbond - KOPI KILEN PERLENGKAPAN',
            'Tahu Isi Sayur'                            => '1001793_Tahu Isi Sayur - KOPI KILEN (MEALS)',
            'Macchiato (Hot)'                           => '1001835_Macchiato (Hot) - KOPI KILEN (DRINKS)',
            'Add on Lychee Syrup Delifru'               => '1002334_Add on Lychee Syrup Delifru - KOPI KILEN (DRINKS)',
            'Peach Tea (Iced)'                          => '1001838_Peach Tea - KOPI KILEN (DRINKS)',
            'Cold Brew Sweet'                           => '1001686_Cold Brew Sweet - KOPI KILEN (DRINKS)',
            'Cold White Coconut'                        => '1001685_Cold White Coconut - KOPI KILEN (DRINKS)',
            'Add On Milk'                               => '1002720_Add On Milk - KOPI KILEN (DRINKS)',
            'Add Sambal Setan'                          => '1002332_Add Sambal Setan - KOPI KILEN (MEALS)',
            'Cold White Caramel'                        => '1001684_Cold White Caramel - KOPI KILEN (DRINKS)',
            'Cup Ukuran 8 Oz'                           => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
            'Coca Cola @250Ml'                          => '1001698_Coca Cola @250Ml - KOPI KILEN (DRINKS)',
            'Roti Bakar Bundling'                       => '1001789_Roti Bakar Bundling - KOPI KILEN (MEALS)',
            'Red Velvet (Hot)'                          => '1001840_Red Velvet (Hot) - KOPI KILEN (DRINKS)',
            'Add Ketupat'                               => '1002328_Add Ketupat - KOPI KILEN (MEALS)',
            'Toraja Sapan Biji @250 Gr'                 => '1001520_Toraja Sapan Biji @250 Gr - KOPI KILEN (DRINKS)',
            'Milk (Iced)'                               => '1002017_Milk (Iced) - KOPI KILEN (DRINKS)',
            'Add Cucumber / Timun'                      => '1002730_Add Cucumber / Timun - KOPI KILEN (MEALS)',
            'Lemon Tea (Hot)'                           => '1001832_Lemon Tea (Hot) - KOPI KILEN (DRINKS)',
            'Equil Sparkling Water'                     => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
            'Pineapple Upside Down Cake'                => '1001702_Banana Caramel - KOPI KILEN (MEALS)',
            'Add Tomat'                                 => '1002751_Add on Tomat - KOPI KILEN (MEALS)',
            'Add on Lemon Juice Syrup 1L'               => '1002336_Add on Lemon Juice Syrup 1L - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Iced (Mandailin'         => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Iced (Trj Sapan)n)'      => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Green Tea (Ice)'                           => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',
            'Java Tea (Ice)'                            => '1001831_Java Tea (Ice) - KOPI KILEN (DRINKS)',
            'Aren Latte Ice'                            => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
            'Cafe Latte (Ice)'                          => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',
            'Aren Latte Hot'                            => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
            'Creamy Dopio Espresso Ice'                 => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Ice (Mandailin'          => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
            'Chocolate (Ice)'                           => '1001813_Chocolate (Ice) - KOPI KILEN (DRINKS)',
            'Daily Brew(V-60) Hot (Trj Sapan)'          => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Daily Brew(V-60) Ice (Trj Sapan)'          => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Vanilla Latte (Ice)'                       => '1001843_Vanilla Latte (Ice) - KOPI KILEN (DRINKS)',
            'Lemon Tea (Ice)'                           => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',
            'Red Velvet (Ice)'                          => '1001841_Red Velvet (Ice) - KOPI KILEN (DRINKS)',
            'Daily Brew (V-60) Iced (Trj Sapa'          => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Kopi Kilend Blend Biji @250 Gr'            => '1002486_Kopi Kilen Blend 250g - Kopi Kilen',
            'Gayo Bubuk @250 Gr'                        => '1002485_Aceh Gayo 250 Gr - Kopi Kilen',
            'Kopi Kilend Blend Bubuk @250 Gr'           => '1002486_Kopi Kilen Blend 250g - Kopi Kilen',
            'Daily Brew (V-60) Hot (Trj Sapan'          => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
            'Add on Palm Sugar Syrup'                   => '1002728_Add on Palm Sugar Syrup - KOPI KILEN (DRINKS)',
            'Add Syrup'                                 => '1002483_Add on Syrup - KOPI KILEN (DRINKS)',
        ];

        

        // ===== Special mapping Oat/Soy berdasarkan parent PRODUCT ID (setelah map) =====
        $milkModifierNames = ['Oat Milk', 'Soy Milk'];

        // Addon ID
        $OAT_150 = '1002737_Add on Oat Milk 150Ml - KOPI KILEN (DRINKS)';
        $OAT_60  = '1002738_Add on Oat Milk 60Ml - KOPI KILEN (DRINKS)';
        $OAT_75  = '1002739_Add on Oat Milk 75Ml - KOPI KILEN (DRINKS)';
        $OAT_30  = '1002743_Add on Oat Milk 30Ml - KOPI KILEN (DRINKS)';

        $SOY_150 = '1002740_Add on Soy Milk 150Ml - KOPI KILEN (DRINKS)';
        $SOY_75  = '1002741_Add on Soy Milk 75Ml - KOPI KILEN (DRINKS)';
        $SOY_60  = '1002742_Add on Soy Milk 60Ml - KOPI KILEN (DRINKS)';
        $SOY_30  = '1002744_Add on Soy Milk 30Ml - KOPI KILEN (DRINKS)';

        // parent product ids (angka) -> addon
        $milkAddonByParentProductId = [];

        // group 150ml parents
        $parents150 = [
            '1001804','1001805','1001806','1001807','1001808','1001809','1001810','1001811',
            '1001812','1001813','1001824','1001825','1001826','1001827','1001828','1001836',
            '1001837','1001840','1001841','1001842','1001843',
        ];
        foreach ($parents150 as $pid) {
            $milkAddonByParentProductId[$pid]['Oat Milk'] = $OAT_150;
            $milkAddonByParentProductId[$pid]['Soy Milk'] = $SOY_150;
        }

        // macchiato (Hot)
        $milkAddonByParentProductId['1001835']['Oat Milk'] = $OAT_60;
        $milkAddonByParentProductId['1001835']['Soy Milk'] = $SOY_75;

        // piccolo (Hot)
        $milkAddonByParentProductId['1001839']['Oat Milk'] = $OAT_75;
        $milkAddonByParentProductId['1001839']['Soy Milk'] = $SOY_60;

        // creamy dopio espresso (iced)
        $milkAddonByParentProductId['1001814']['Oat Milk'] = $OAT_30;
        $milkAddonByParentProductId['1001814']['Soy Milk'] = $SOY_30;

// =========================
// DETECT trx MIXED MILK (LEVEL INVOICE)
// mixed kalau: total milk-drink qty > total oat/soy qty, dan oat/soy qty > 0
// =========================
$milkParents = array_fill_keys(array_keys($milkAddonByParentProductId), true);

$trxMilkParentQty = []; // [trx => total milk parent qty]
$trxAltQty        = []; // [trx => total oat+soy qty]
$trxIsMixedMilk   = []; // [trx => true]

foreach ($logicalRows as $r) {
    $trxCode = $r['trx'];
    $qty     = (float) ($r['qty'] ?? 0);
    if ($qty <= 0) continue;

    $categoryUpper = strtoupper(trim($r['category'] ?? ''));
    $isComboChild  = (bool)($r['is_combo_child'] ?? false);

    $cleanName = $this->normalizeProductName($r['name'] ?? '');

    // 1) hitung Oat/Soy modifier
    if (
        !$isComboChild
        && $categoryUpper === 'EXTRA'
        && in_array($cleanName, $milkModifierNames, true)
    ) {
        if (!isset($trxAltQty[$trxCode])) $trxAltQty[$trxCode] = 0.0;
        $trxAltQty[$trxCode] += $qty;
        continue;
    }

    // 2) hitung milk parent drinks (berdasarkan PID yg ada di milkAddonByParentProductId)
    if (!$isComboChild && $categoryUpper !== 'EXTRA') {
        $productId = $productMap[$cleanName] ?? $cleanName;
        $parentPid = explode('_', $productId)[0];

        if (isset($milkParents[$parentPid])) {
            if (!isset($trxMilkParentQty[$trxCode])) $trxMilkParentQty[$trxCode] = 0.0;
            $trxMilkParentQty[$trxCode] += $qty;
        }
    }
}

// finalize mixed flag
foreach ($trxMilkParentQty as $trxCode => $totalMilk) {
    $alt = (float) ($trxAltQty[$trxCode] ?? 0.0);

    // mixed: ada alt dan tidak semua milk diganti alt
    if ($alt > 0.0 && $totalMilk > $alt) {
        $trxIsMixedMilk[$trxCode] = true;
    }
}


        // === OUTPUT XLSX 4 SHEET ===
        $spreadsheet = new Spreadsheet();

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Sheet 1');
        $sheet1->fromArray($headers12, null, 'A1');

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Sheet 2');
        $sheet2->fromArray($headers12, null, 'A1');

        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Sheet 3');
        $sheet3->fromArray($headers3, null, 'A1');

        // Sheet 4: invoice mixed milk (pakai format Sheet 1/2 agar bisa import)
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Sheet 4');
        $sheet4->fromArray($headers12, null, 'A1');

        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Sheet 5');
        $sheet5->fromArray($headers3, null, 'A1'); // sama seperti Sheet 3

        $rowPtr = [1=>2,2=>2,3=>2,4=>2,5=>2];

        $masterWrittenBySheet = [1=>[],2=>[],3=>[],4=>[],5=>[]];

        $lineNoByTrxBySheet = [1=>[],2=>[],3=>[],4=>[],5=>[]];
        
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

        // =========================================================
        // ROUTING FIX:
        // Kalau hasil logicalRows TIDAK punya PB1 0% line (amount > 0),
        // maka transaksi WAJIB masuk Sheet 1 (meskipun raw ada Take Away 0).
        // =========================================================
        $trxHasPb0 = []; // [trx => true]
        foreach ($logicalRows as $r) {
            $trxCode = $r['trx'];
            $qty = (float) ($r['qty'] ?? 0);
            $unitPrice = (float) ($r['unitPrice'] ?? 0);
            $amount = $qty * $unitPrice;

            $categoryUpper = strtoupper(trim($r['category'] ?? ''));
            $isComboChild  = (bool)($r['is_combo_child'] ?? false);

            $taxName = $this->shouldPb0($r) ? 'PB1 0%' : 'PB1 10%';

            if ($taxName === 'PB1 0%' && $amount != 0.0) {
                $trxHasPb0[$trxCode] = true;
            }
        }
        // =========================
        // ORIGIN sheet by trx (1/2/3) sebelum dipindah ke sheet 4/5
        // =========================
        $originSheetByTrx = [];

        foreach ($invoiceFlags as $trxCode => $flags) {
            if (!($trxHasPb0[$trxCode] ?? false)) {
                $originSheetByTrx[$trxCode] = 1;
            } else {
                $originSheetByTrx[$trxCode] = ($flags['has_discount'] ? 3 : 2);
            }
        }

        // =========================================================
        // DISCOUNT SPLIT (HANYA SHEET 3):
        // - total_discount         => diskon PB1 10%
        // - FORCA_TotalDiscNoTax   => diskon PB1 0%
        // =========================================================
        $trxSalesByTaxForSheet3 = []; // [trx => ['pb10' => x, 'pb0' => y]]

        foreach ($logicalRows as $r) {
            $trxCode   = $r['trx'];
            $qty       = (float) ($r['qty'] ?? 0);
            $unitPrice = (float) ($r['unitPrice'] ?? 0);

            $flags = $invoiceFlags[$trxCode] ?? ['has_extra_or_ta' => false, 'has_discount' => false];

            $origin = $originSheetByTrx[$trxCode] ?? 1;

            if ($trxIsMixedMilk[$trxCode] ?? false) {
                $targetSheetNo = ($origin === 3) ? 5 : 4;
            } else {
                $targetSheetNo = $origin;
            }

            if ($targetSheetNo !== 3 && $targetSheetNo !== 5) {
                continue;
            }

            $categoryUpper = strtoupper(trim($r['category'] ?? ''));
            $isComboChild  = (bool)($r['is_combo_child'] ?? false);

            $taxName = $this->shouldPb0($r) ? 'PB1 0%' : 'PB1 10%';

            $lineAmount = $qty * $unitPrice;

            if (!isset($trxSalesByTaxForSheet3[$trxCode])) {
                $trxSalesByTaxForSheet3[$trxCode] = ['pb10' => 0.0, 'pb0' => 0.0];
            }

            if ($taxName === 'PB1 0%') {
                $trxSalesByTaxForSheet3[$trxCode]['pb0'] += $lineAmount;
            } else {
                $trxSalesByTaxForSheet3[$trxCode]['pb10'] += $lineAmount;
            }
        }

        $discSplitMap = []; // [trx => ['disc10' => x, 'disc0' => y]]
        foreach ($trxSalesByTaxForSheet3 as $trxCode => $sales) {
            $totalDisc = (float) str_replace(',', '', ($summaryMap[$trxCode]['discount'] ?? '0'));

            $pb10Sales = (float) ($sales['pb10'] ?? 0.0);
            $pb0Sales  = (float) ($sales['pb0'] ?? 0.0);
            $denom     = $pb10Sales + $pb0Sales;

            $disc10 = 0.0;
            $disc0  = 0.0;

            if ($totalDisc != 0.0 && $denom > 0) {
                $disc10 = $this->roundMoney0($totalDisc * ($pb10Sales / $denom));
                $disc0  = $totalDisc - $disc10;
            } else {
                $disc10 = $totalDisc;
                $disc0  = 0.0;
            }

            $discSplitMap[$trxCode] = [
                'disc10' => $disc10,
                'disc0'  => $disc0,
            ];
        }

        $lastParentProductIdByTrx  = []; // parent terakhir per transaksi

        foreach ($logicalRows as $row) {
            $trxCode   = $row['trx'];
            $nameRaw   = $row['name'];
            $qty       = (float) ($row['qty'] ?? 0);
            $unitPrice = (float) ($row['unitPrice'] ?? 0);

            $sum = $summaryMap[$trxCode] ?? [
                'date'     => '',
                'discount' => '0',
                'service'  => '0',
                'rounding' => '0',
                'pax'      => 1,
                'customer' => '',
            ];

            $flags = $invoiceFlags[$trxCode] ?? ['has_extra_or_ta' => false, 'has_discount' => false];

            $origin = $originSheetByTrx[$trxCode] ?? 1;

            if ($trxIsMixedMilk[$trxCode] ?? false) {
                $targetSheetNo = ($origin === 3) ? 5 : 4;
            } else {
                $targetSheetNo = $origin;
            }

            $categoryUpper = strtoupper(trim($row['category'] ?? ''));
            $isComboChild  = (bool)($row['is_combo_child'] ?? false);

            $taxName = $this->shouldPb0($row) ? 'PB1 0%' : 'PB1 10%';

            // LineNo per trx per sheet
            if (!isset($lineNoByTrxBySheet[$targetSheetNo][$trxCode])) {
                $lineNoByTrxBySheet[$targetSheetNo][$trxCode] = 10;
            } else {
                $lineNoByTrxBySheet[$targetSheetNo][$trxCode] += 10;
            }
            $lineNo = $lineNoByTrxBySheet[$targetSheetNo][$trxCode];

            $description = '';
            $tanggalIndo = '';
            $bulanText   = '';

            if (!empty($sum['date'])) {
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

            // default mapping normal
            $productId = $productMap[$cleanName] ?? $cleanName;
            $productTrim = explode('_', $productId)[0];

            $categoryUpper = strtoupper(trim($row['category'] ?? ''));
            $isComboChild  = (bool)($row['is_combo_child'] ?? false);

            // simpan parent product id: item utama (bukan EXTRA, bukan combo child)
            if (!$isComboChild && $categoryUpper !== 'EXTRA') {
                $lastParentProductIdByTrx[$trxCode] = $productTrim;
            }

            // override khusus Oat/Soy: gunakan parentProductTrim
            if (!$isComboChild && $categoryUpper === 'EXTRA' && in_array($cleanName, $milkModifierNames, true)) {
                $parentPid = $lastParentProductIdByTrx[$trxCode] ?? null;

                if ($parentPid !== null && isset($milkAddonByParentProductId[$parentPid][$cleanName])) {
                    $productId = $milkAddonByParentProductId[$parentPid][$cleanName];
                    $productTrim = explode('_', $productId)[0];
                }
            }

            // =========================
            // DISCOUNT MAPPING
            // Sheet 1 & 2:
            //   - total_discount = summary.discount
            //   - FORCA_TotalDiscNoTax kosong
            // Sheet 3:
            //   - total_discount = diskon PB1 10%
            //   - FORCA_TotalDiscNoTax = diskon PB1 0%
            // =========================
            $totalDiscMaster = (float) str_replace(',', '', ($sum['discount'] ?? '0'));
            $disc10Master = $totalDiscMaster;
            $disc0Master  = ''; // default kosong untuk sheet 1 & 2

            if ($targetSheetNo === 3 || $targetSheetNo === 5) {
                $split = $discSplitMap[$trxCode] ?? ['disc10' => $totalDiscMaster, 'disc0' => 0.0];
                $disc10Master = (float) ($split['disc10'] ?? 0.0);
                $disc0Master  = (float) ($split['disc0'] ?? 0.0);
            }

            // FIX: FORCA_DescriptionDisc hanya kalau ada diskon di total_discount (PB1 10%)
            $descriptionDiscount = ($disc10Master == 0.0) ? '' : 'Diskon';

        $descDisc = ($disc10Master != 0.0) ? 'Diskon' : '';

        if ($targetSheetNo === 3 || $targetSheetNo === 5) {
            // SHEET 3 (ADA DiscNoTax)
            $outputRow = [
                'Head Office',            // 0
                'N',                      // 1
                '1000750',                // 2
                $description,             // 3
                $sum['date'],             // 4
                $trxCode,                 // 5
                $disc10Master,            // 6 total_discount (PB1 10%)
                $descDisc,                // 7 FORCA_DescriptionDisc (HANYA kalau disc10 != 0)
                $disc0Master,             // 8 FORCA_TotalDiscNoTax (PB1 0%)
                $sum['service'],          // 9 FORCA_ServiceCharge
                $sum['rounding'],         // 10 FORCA_RoundingAmt
                '1113037',                // 11
                '1000009',                // 12
                '1000006',                // 13
                'KOPI KILEN SQ - JAKARTA', // 14
                'Ely Ruknia Sari',        // 15
                '303',                    // 16
                'Y',                      // 17
                'Food and Beverage',      // 18
                'Sales Exclude Tax',      // 19
                '1. Immediate',           // 20
                'Head Office',            // 21
                $lineNo,                  // 22
                $productTrim,             // 23
                $unitPrice,               // 24
                $qty,                     // 25
                $taxName,                 // 26
                'Y',                      // 27
            ];
        } else {
            // SHEET 1 & 2 (TANPA DiscNoTax)
            $outputRow = [
                'Head Office',            // 0
                'N',                      // 1
                '1000750',                // 2
                $description,             // 3
                $sum['date'],             // 4
                $trxCode,                 // 5
                $disc10Master,            // 6 total_discount
                $descDisc,                // 7 FORCA_DescriptionDisc
                $sum['service'],          // 8 FORCA_ServiceCharge (GESER ke sini)
                $sum['rounding'],         // 9 FORCA_RoundingAmt
                '1113037',                // 10
                '1000009',                // 11
                '1000006',                // 12
                'KOPI KILEN SQ - JAKARTA', // 13
                'Ely Ruknia Sari',        // 14
                '303',                    // 15
                'Y',                      // 16
                'Food and Beverage',      // 17
                'Sales Exclude Tax',      // 18
                '1. Immediate',           // 19
                'Head Office',            // 20
                $lineNo,                  // 21
                $productTrim,             // 22
                $unitPrice,               // 23
                $qty,                     // 24
                $taxName,                 // 25
                'Y',                      // 26
            ];
        }

            // Blank master columns kalau trx sudah pernah ditulis di sheet itu
            if (isset($masterWrittenBySheet[$targetSheetNo][$trxCode])) {
                $maxMasterIdx = ($targetSheetNo === 3 || $targetSheetNo === 5) ? 20 : 19;
                foreach (range(0, $maxMasterIdx) as $idx) {
                    $outputRow[$idx] = '';
                }
            } else {
                $masterWrittenBySheet[$targetSheetNo][$trxCode] = true;
            }

            $ws = ($targetSheetNo === 1) ? $sheet1
                : (($targetSheetNo === 2) ? $sheet2
                : (($targetSheetNo === 3) ? $sheet3
                : (($targetSheetNo === 4) ? $sheet4
                : $sheet5)));
            $ws->fromArray($outputRow, null, 'A' . $rowPtr[$targetSheetNo]);
            $rowPtr[$targetSheetNo]++;
        }

        $filename = 'Mapping_Quinos_5Sheets_' . date('Ymd_His') . '.xlsx';
        $tmpPath = storage_path('app/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
}