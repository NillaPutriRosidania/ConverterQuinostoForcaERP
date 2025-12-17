<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Log;


    class QuinosConverterController extends Controller
    {
        public function index()
        {
            return view('converter.index');
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

            $sumTrxIndex   = array_search('Trx Code', $summaryHeader);
            $sumDateIndex  = array_search('Date', $summaryHeader);
            $sumDiscIndex  = array_search('Discount', $summaryHeader);
            $sumSvcIndex   = array_search('Service charge', $summaryHeader);
            $sumRoundIndex = array_search('Rounding', $summaryHeader);

            if (
                $sumTrxIndex   === false ||
                $sumDateIndex  === false ||
                $sumDiscIndex  === false ||
                $sumSvcIndex   === false ||
                $sumRoundIndex === false
            ) {
                fclose($handleSummary);
                abort(500, 'Kolom summary tidak lengkap. Pastikan ada "Trx Code, Date, Discount, Service charge, Rounding". Header: ' . implode(', ', $summaryHeader));
            }
            $customerIndex = $sumDateIndex > 0 ? $sumDateIndex - 1 : null;
            $summaryMap = [];
    $masterWritten = [];

    $lineNoByTrx = [];

            while (($row = fgetcsv($handleSummary)) !== false) {
                if (! isset($row[$sumTrxIndex])) continue;

                $trxCode = trim($row[$sumTrxIndex]);
                if ($trxCode === '') continue;

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

                        $dateFormatted = sprintf('%04d-%02d-%02d', $year, $month, $day); // 2025-10-08
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

            $trxIndex   = array_search('Trx Code', $inputHeader);
            $qtyIndex   = array_search('Qty', $inputHeader);
            $priceIndex = array_search('Price', $inputHeader);
            $nameIndex  = array_search('Name', $inputHeader);

            if ($trxIndex === false || $qtyIndex === false || $priceIndex === false || $nameIndex === false) {
                fclose($handleDetail);
                abort(500, 'Kolom "Trx Code", "Qty", "Price", atau "Name" tidak ditemukan di file detail. Header: ' . implode(', ', $inputHeader));
            }

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
                'Hot Americano'           => '1001802_Americano (Hot) - KOPI KILEN (DRINKS)',
                'Iced Americano'          => '1001803_Americano (Iced) - KOPI KILEN (DRINKS)',

                'Hot Cafe Latte'          => '1001806_Cafe Latte (Hot) - KOPI KILEN (DRINKS)',
                'Iced Cafe Latte'         => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',

                'Hot Cappuccino'          => '1001808_Cappuccino (Hot) - KOPI KILEN (DRINKS)',
                'Iced Cappuccino'         => '1001809_Cappuccino (Iced) - KOPI KILEN (DRINKS)',

                'Hot Caramel Latte'       => '1001810_Caramel Latte (Hot) - KOPI KILEN (DRINKS)',
                'Iced Caramel Latte'      => '1001811_Caramel Latte (Iced) - KOPI KILEN (DRINKS)',

                'Hot Chocolate'           => '1001812_Chocolate (Hot) - KOPI KILEN (DRINKS)',
                'Iced Chocolate'          => '1001813_Chocolate (Ice) - KOPI KILEN (DRINKS)',

                'Hot Green Tea'           => '1001825_Green Tea (Hot) - KOPI KILEN (DRINKS)',
                'Iced Green Tea'          => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',

                'Hot Hazelnut Latte'      => '1001827_Hazelnut Latte (Hot) - KOPI KILEN (DRINKS)',
                'Iced Hazelnut Latte'     => '1001828_Hazelnut Latte (Iced) - KOPI KILEN (DRINKS)',

                'Hot Java Tea'            => '1001830_Java Tea (Hot) - KOPI KILEN (DRINKS)',
                'Iced Java Tea'           => '1001831_Java Tea (Ice) - KOPI KILEN (DRINKS)',

                'Hot Lemon Tea'           => '1001832_Lemon Tea (Hot) - KOPI KILEN (DRINKS)',
                'Iced Lemon Tea'          => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',

                'Iced Lecy Tea'           => '1001834_Lychee Tea - KOPI KILEN (DRINKS)',

                'Hot Mocha Latte'         => '1001836_Mocha Latte (Hot) - KOPI KILEN (DRINKS)',
                'Iced Mocha Latte'        => '1001837_Mocha Latte (Ice) - KOPI KILEN (DRINKS)',

                'Iced Peach Tea'          => '1001838_Peach Tea - KOPI KILEN (DRINKS)',

                'Hot Red Velvet'          => '1001840_Red Velvet (Hot) - KOPI KILEN (DRINKS)',
                'Iced Red Velvet'         => '1001841_Red Velvet (Ice) - KOPI KILEN (DRINKS)',

                'Hot Vanilla Latte'       => '1001842_Vanilla Latte (Hot) - KOPI KILEN (DRINKS)',
                'Iced Vanilla Latte'      => '1001843_Vanilla Latte (Ice) - KOPI KILEN (DRINKS)',

                'Hot Coffee Palm Sugar'   => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
                'Iced Coffee Palm Sugar'  => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',

                'Espresso'                => '1001823_Esspreso - KOPI KILEN (DRINKS)',
                'Cleo 330ml'              => '1002211_Cleo Mineral Water 350 Ml - KOPI KILEN (DRINKS)',

                'Indomie Goreng Kilen'    => '1002341_Indomie Goreng Kilen - KOPI KILEN (MEALS)',
                'Indomie Rebus Kilen'     => '1002340_Indomie Rebus Kilen - KOPI KILEN (MEALS)',
                'Kilen Blend Bubuk'       => '1001518_Kopi Kilend Blend Bubuk @250 Gr - KOPI KILEN (DRINKS)',
                'Aceh Gayo Bubuk'       => '1001523_Gayo Bubuk @250 Gr - KOPI KILEN (DRINKS)',
                'Toraja Sapan Biji'       => '1001520_Toraja Sapan Biji @250 Gr - KOPI KILEN (DRINKS)',
                
                'Iced Honey Citron Tea'   => '1001931_Honey Citron Tea Iced - KOPI KILEN (DRINKS)',

                            'Pokka Green Tea'   => '1001687_Pokka Green Tea - KOPI KILEN (DRINKS)',
                            '# Milk'   => '1002017_Milk (Iced) - KOPI KILEN (DRINKS)',
                            '# Syrup'   => '1002483_Add on Syrup - Kopi Kilen',
                            '# Shot'   => '1002337_Add on Essppresso - KOPI KILEN (DRINKS)',
                            'Flat White'   => '1001824_Flat White - KOPI KILEN (DRINKS)',
                            'V60 ( Japanessee )'   => '1002484_Daily Brew V60 - Kopi Kilen',
                            'Aceh Gayo Biji'   => '1001522_Gayo Biji @250 Gr - KOPI KILEN (DRINKS)',
                            'Fanta'   => '1001700_Fanta @250Ml - KOPI KILEN (DRINKS)',
                

                
            ];

            $temp = fopen('php://temp', 'r+');
            fputcsv($temp, $headers);
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

            while (($row = fgetcsv($handleDetail)) !== false) {
                if (! isset($row[$trxIndex])) continue;

                $trxCode = trim($row[$trxIndex]);
                if ($trxCode === '') continue;
                $nameRaw   = trim($row[$nameIndex]  ?? '');
                $qtyRaw    = trim($row[$qtyIndex]   ?? '0');
                $priceRaw  = trim($row[$priceIndex] ?? '0');
                $qtyStr   = str_replace(',', '', $qtyRaw);
                $priceStr = str_replace(',', '', $priceRaw);
                $qty   = (float) $qtyStr;
                $price = (float) $priceStr;
                $unitPrice = $qty != 0 ? $price / $qty : 0;
                if (!isset($lineNoByTrx[$trxCode])) {
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
                    'customer' => '',
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
                    $tanggalIndo = sprintf('%02d %s %04d', (int)$day, $bulanText, (int)$year);
                }

                $customer = $sum['customer'];

                if ($tanggalIndo !== '') {
                    if ($customer === '') {
                        $description = 'Migrasi Quinos Transaksi Bulan ' . $bulanText . '_' . $tanggalIndo;
                    } else {
                        $description = 'an ' . $customer . ' (Migrasi Quinos Transaksi ' . $tanggalIndo . ')';
                    }
                }
                $productFull = $productMap[$nameRaw] ?? $nameRaw;
                $productId = explode('_', $productFull)[0];


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
                    21 => $lineNo,
                    22 => $productId,
                    23 => $unitPrice,
                    24 => $qty,
                    25 => 'PB1 10%',
                    26 => 'Y',
                ];
                if (isset($masterWritten[$trxCode])) {
                    foreach ([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19] as $idx) {
                        $outputRow[$idx] = '';
                    }
                } else {
                    $masterWritten[$trxCode] = true;
                }

                fputcsv($temp, $outputRow);
            }

            fclose($handleDetail);

            rewind($temp);
            $csvContent = stream_get_contents($temp);
            fclose($temp);

            $filename = 'Mapping_Quinos_2Files_' . date('Ymd_His') . '.csv';

            return response($csvContent, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }
    }       