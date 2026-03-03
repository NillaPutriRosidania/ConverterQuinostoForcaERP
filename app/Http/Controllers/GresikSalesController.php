<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;


class GresikSalesController extends Controller
{
    /**
     * Halaman upload
     */
    public function upload()
    {
        return view('sales.upload');
    }

    /**
     * Proses CSV & hitung sales per product code
     */
public function process(Request $request)
{
    $request->validate([
        'files'   => 'required',
        'files.*' => 'file|mimes:csv,txt'
    ]);

    $totalPerCode = [];

    foreach ($request->file('files') as $file) {

        $rows = array_map('str_getcsv', file($file->getRealPath()));
        if (count($rows) < 2) continue;

        $header = array_map('trim', array_shift($rows));
        $codeIndex = array_search(
            'FORCA_ImportSalesPOSLine>M_Product_ID[Value]',
            $header
        );

        $qtyIndex = array_search(
            'FORCA_ImportSalesPOSLine>QtyOrdered',
            $header
        );

        if ($codeIndex === false || $qtyIndex === false) {
            continue;
        }

        foreach ($rows as $row) {

            if (
                !isset($row[$codeIndex]) ||
                !isset($row[$qtyIndex])
            ) {
                continue;
            }

            $code = trim($row[$codeIndex]);
            if ($code === '' || !is_numeric($code)) {
                continue;
            }

            $qty = (int) trim($row[$qtyIndex]);
            if ($qty <= 0) {
                continue;
            }

            if (!isset($totalPerCode[$code])) {
                $totalPerCode[$code] = 0;
            }

            $totalPerCode[$code] += $qty;
        }
    }

    session(['sales_result' => $totalPerCode]);

    return redirect()->route('sales.result');
}


    /**
     * Halaman hasil (kode → nama panjang)
     */
public function result()
{
    $rawData = session('sales_result', []);
    $map = $this->productMap();

    $finalData = [];
    foreach ($rawData as $code => $qty) {
        $finalData[] = [
            'code' => (string)$code,
            'name' => $map[$code] ?? '(Belum dimapping)',
            'qty'  => (int)$qty,
        ];
    }

    // urutkan qty terbesar
    usort($finalData, fn($a,$b) => $b['qty'] <=> $a['qty']);

    return view('sales.result', ['data' => $finalData]);
}



    // public function result()
    // {
    //     $rawData = session('sales_result', []);
    //     $map = $this->productMap();

    //     $finalData = [];

    //     foreach ($rawData as $code => $qty) {

    //         $label = $map[$code] ?? $code;

    //         $finalData[$label] = $qty;
    //     }

    //     return view('sales.result', [
    //         'data' => $finalData
    //     ]);
    // }

    /**
     * Mapping KODE → NAMA PANJANG FORCA
     */
private function productMap()
{
    return [

        // === AMERICANO ===
        '1001802' => '1001802_Americano (Hot) - KOPI KILEN (DRINKS)',
        '1001803' => '1001803_Americano (Iced) - KOPI KILEN (DRINKS)',

        // === CAFE LATTE ===
        '1001806' => '1001806_Cafe Latte (Hot) - KOPI KILEN (DRINKS)',
        '1001807' => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',

        // === CAPPUCCINO ===
        '1001808' => '1001808_Cappuccino (Hot) - KOPI KILEN (DRINKS)',
        '1001809' => '1001809_Cappuccino (Iced) - KOPI KILEN (DRINKS)',

        // === CARAMEL LATTE ===
        '1001810' => '1001810_Caramel Latte (Hot) - KOPI KILEN (DRINKS)',
        '1001811' => '1001811_Caramel Latte (Iced) - KOPI KILEN (DRINKS)',

        // === CHOCOLATE ===
        '1001812' => '1001812_Chocolate (Hot) - KOPI KILEN (DRINKS)',
        '1001813' => '1001813_Chocolate (Ice) - KOPI KILEN (DRINKS)',

        // === GREEN TEA ===
        '1001825' => '1001825_Green Tea (Hot) - KOPI KILEN (DRINKS)',
        '1001826' => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',

        // === HAZELNUT LATTE ===
        '1001827' => '1001827_Hazelnut Latte (Hot) - KOPI KILEN (DRINKS)',
        '1001828' => '1001828_Hazelnut Latte (Iced) - KOPI KILEN (DRINKS)',

        // === JAVA TEA ===
        '1001830' => '1001830_Java Tea (Hot) - KOPI KILEN (DRINKS)',
        '1001831' => '1001831_Java Tea (Ice) - KOPI KILEN (DRINKS)',

        // === LEMON TEA ===
        '1001832' => '1001832_Lemon Tea (Hot) - KOPI KILEN (DRINKS)',
        '1001833' => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',

        // === FRUITY TEA ===
        '1001834' => '1001834_Lychee Tea - KOPI KILEN (DRINKS)',
        '1001838' => '1001838_Peach Tea - KOPI KILEN (DRINKS)',
        '1001931' => '1001931_Honey Citron Tea Iced - KOPI KILEN (DRINKS)',

        // === MOCHA ===
        '1001836' => '1001836_Mocha Latte (Hot) - KOPI KILEN (DRINKS)',
        '1001837' => '1001837_Mocha Latte (Ice) - KOPI KILEN (DRINKS)',

        // === RED VELVET ===
        '1001840' => '1001840_Red Velvet (Hot) - KOPI KILEN (DRINKS)',
        '1001841' => '1001841_Red Velvet (Ice) - KOPI KILEN (DRINKS)',

        // === VANILLA LATTE ===
        '1001842' => '1001842_Vanilla Latte (Hot) - KOPI KILEN (DRINKS)',
        '1001843' => '1001843_Vanilla Latte (Ice) - KOPI KILEN (DRINKS)',

        // === AREN LATTE ===
        '1001804' => '1001804_Aren Latte Hot - KOPI KILEN (DRINKS)',
        '1001805' => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',

        // === OTHER DRINKS ===
        '1001823' => '1001823_Espresso - KOPI KILEN (DRINKS)',
        '1002211' => '1002211_Cleo Mineral Water 350 Ml - KOPI KILEN (DRINKS)',
        '1001687' => '1001687_Pokka Green Tea - KOPI KILEN (DRINKS)',

        // === MEALS ===
        '1002341' => '1002341_Indomie Goreng Kilen - KOPI KILEN (MEALS)',
        '1002340' => '1002340_Indomie Rebus Kilen - KOPI KILEN (MEALS)',

        // === BEANS / POWDER ===
        '1001518' => '1001518_Kopi Kilend Blend Bubuk @250 Gr - KOPI KILEN (DRINKS)',
        '1001523' => '1001523_Gayo Bubuk @250 Gr - KOPI KILEN (DRINKS)',
        '1001520' => '1001520_Toraja Sapan Biji @250 Gr - KOPI KILEN (DRINKS)',
        '1001504' => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
        '1001785' => '1001785_Nasi Goreng Kilen - KOPI KILEN (MEALS)',
        '1002324' => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
        '1001824' => '1001824_Flat White - KOPI KILEN (DRINKS)',
        '1001787' => '1001787_Pisang Goreng - KOPI KILEN (MEALS)',
        '1001839' => '1001839_Piccolo (Hot) - KOPI KILEN (DRINKS)',
        '1001818' => '1001818_Daily Brew (V-60) Iced (Bali Batukaru) - KOPI KILEN (DRINKS)',
        '1001794' => '1001794_Tahu Lada Garam - KOPI KILEN (MEALS)',
        '1001796' => '1001796_Toast Kaya Butter - KOPI KILEN (MEALS)',
        '1001780' => '1001780_Internet Goreng Kilen - KOPI KILEN (MEALS)',
        '1001773' => '1001773_Chicken Katsu Rice Bowl - KOPI KILEN (MEALS)',
        '1002337' => '1002337_Add on Essppresso - KOPI KILEN (DRINKS)',
        '1001775' => '1001775_Cireng - KOPI KILEN (MEALS)',
        '1001821' => '1001821_Daily Brew(V-60) Ice (Trj Sapan) - KOPI KILEN (DRINKS)',
        '1001795' => '1001795_Tempe Goreng - KOPI KILEN (MEALS)',
        '1001814' => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
        '1001781' => '1001781_Internet Rebus - KOPI KILEN (MEALS)',
        '1002296' => '1002296_Kwetiau Goreng - KOPI KILEN (MEALS)',
        '1001683' => '1001683_Cold White Original - KOPI KILEN (DRINKS)',
        '1001701' => '1001701_Coca Cola Zero - KOPI KILEN (DRINKS)',
        '1001779' => '1001779_French Fries - KOPI KILEN (MEALS)',
        '1001788' => '1001788_Roti Bakar - KOPI KILEN (MEALS)',
        '1001815' => '1001815_Daily Brew (V-60) Hot (Bali Batukaru) - KOPI KILEN (DRINKS)',
        '1001702' => '1001702_Banana Caramel - KOPI KILEN (MEALS)',
        '1001820' => '1001820_Daily Brew(V-60) Hot (Trj Sapan) - KOPI KILEN (DRINKS)',
        '1001797' => '1001797_Ubi Goreng - KOPI KILEN (MEALS)',
        '1001786' => '1001786_Nasi Goreng Sapi - KOPI KILEN (MEALS)',
        '1001829' => '1001829_Honey Citron Tea Hot - KOPI KILEN (DRINKS)',
        '1001817' => '1001817_Daily Brew (V-60) Ice (Mandailing) - KOPI KILEN (DRINKS)',
        '1001685' => '1001685_Cold White Coconut - KOPI KILEN (DRINKS)',
        '1001793' => '1001793_Tahu Isi Sayur - KOPI KILEN (MEALS)',
        '1001784' => '1001784_Mie Goreng Ayam Betutu - KOPI KILEN (MEALS)',
        '1001684' => '1001684_Cold White Caramel - KOPI KILEN (DRINKS)',
        '1001782' => '1001782_Lontong Sayur - KOPI KILEN (MEALS)',
        '1001771' => '1001771_Bubur Ayam - KOPI KILEN (MEALS)',
        '1002336' => '1002336_Add on Lemon Juice Syrup 1L - KOPI KILEN (DRINKS)',
        '1001703' => '1001703_Chocolate Cake with Espresso - KOPI KILEN (MEALS)',
        '1001835' => '1001835_Macchiato (Hot) - KOPI KILEN (DRINKS)',
        '1001769' => '1001769_Ayam Bakar - KOPI KILEN (MEALS)',
        '1002335' => '1002335_Add on Peach Syrup Delifru - KOPI KILEN (DRINKS)',
        '1001783' => '1001783_Mie Ayam - KOPI KILEN (MEALS)',
        '1001686' => '1001686_Cold Brew Sweet - KOPI KILEN (DRINKS)',
        '1000819' => '1000819_Take Away Snack - KOPI KILEN (MEALS)',
        '1001772' => '1001772_Chicken Blackpepper - KOPI KILEN (MEALS)',
        '1000820' => '1000820_Take Away Food - KOPI KILEN (MEALS)',
        '1001791' => '1001791_Soto Betawi - KOPI KILEN (MEALS)',
        '1001790' => '1001790_Soto Ayam - KOPI KILEN (MEALS)',
        '1001770' => '1001770_Ayam Geprek - KOPI KILEN (MEALS)',
        '1002728' => '1002728_Add on Palm Sugar Syrup - KOPI KILEN (DRINKS)',
        '1002213' => '1002213_Spunbond - KOPI KILEN PERLENGKAPAN',
        '1001816' => '1001816_Daily Brew (V-60) Hot (Mandailing) - KOPI KILEN (DRINKS)',
        '1002334' => '1002334_Add on Lychee Syrup Delifru - KOPI KILEN (DRINKS)',
        '1001698' => '1001698_Coca Cola @250Ml - KOPI KILEN (DRINKS)',
        '1001774' => '1001774_Chicken Satay - KOPI KILEN (MEALS)',
        '1000821' => '1000821_Take Away Hot - KOPI KILEN (DRINKS)',
        '1002720' => '1002720_Add On Milk - KOPI KILEN (DRINKS)',
        '1002327' => '1002327_Add Rice - KOPI KILEN (MEALS)',
        '1002214' => '1002214_Paper Bag Kilen - KOPI KILEN PERLENGKAPAN',
        '1001789' => '1001789_Roti Bakar Bundling - KOPI KILEN (MEALS)',
        '1002727' => '1002727_Add on Caramel Syrup - KOPI KILEN (DRINKS)',
        '1000822' => '1000822_Take Away Iced - KOPI KILEN (DRINKS)',
        '1002332' => '1002332_Add Sambal Setan - KOPI KILEN (MEALS)',
        '1002326' => '1002326_Add On Chicken - KOPI KILEN (MEALS)',
        '1002328' => '1002328_Add Ketupat - KOPI KILEN (MEALS)',
        '1002017' => '1002017_Milk (Iced) - KOPI KILEN (DRINKS)',
        '1002751' => '1002751_Add on Tomat - KOPI KILEN (MEALS)',
        '1002730' => '1002730_Add Cucumber / Timun - KOPI KILEN (MEALS)',
        '1002325' => '1002325_Add On Beef - KOPI KILEN (MEALS)',
        '1001537' => '1001537_Lemon Juice Syrup 1L - KOPI KILEN (DRINKS)',
        '1001819' => '1001819_Daily Brew (V-60) Hot (Aceh Gayo) - KOPI KILEN (DRINKS)',
        '1001521' => '1001521_Toraja Bubuk @250 Gr - KOPI KILEN (DRINKS)',
        '1002483' => '1002483_Add on Syrup - Kopi Kilen',
        '1002678' => '1002678_Cafe Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
        '1001778' => '1001778_Dori Sambal Matah - KOPI KILEN (MEALS)',
        '1001822' => '1001822_Daily Brew(V-60) Iced (Aceh Gayo) - KOPI KILEN (DRINKS)',
        '1002693' => '1002693_Flat White Oat Milk - KOPI KILEN (DRINKS)',
        '1002677' => '1002677_Cafe Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
        '1002343' => '1002343_Aren Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
        '1002681' => '1002681_Cappuccino Oat Milk (Hot) - KOPI KILEN (DRINKS)',
        '1002486' => '1002486_Kopi Kilen Blend 250g - Kopi Kilen',
        '1002482' => '1002482_Add On Food - Kopi Kilen',
        '1002311' => '1002311_Kwetiau Goreng Sapi - KOPI KILEN (MEALS)',
        '1002701' => '1002701_Hazelnut Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
        '1002697' => '1002697_Green Tea Oat Milk (Iced) - KOPI KILEN (DRINKS)',
        '1002709' => '1002709_Piccolo Oat Milk (Hot) - KOPI KILEN (DRINKS)',
        '1002484' => '1002484_Daily Brew V60 - Kopi Kilen',
        '1001776' => '1001776_Dori Asam Manis - KOPI KILEN (MEALS)',
        '1002679' => '1002679_Cafe Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',
        '1002345' => '1002345_Aren Latte Soy Milk (Iced) - KOPI KILEN (DRINKS)',
        '1002737' => '1002737_Add on Oat Milk 150Ml - KOPI KILEN (DRINKS)',
        '1001700' => '1001700_Fanta @250Ml - KOPI KILEN (DRINKS)',
        '1001522' => '1001522_Gayo Biji @250 Gr - KOPI KILEN (DRINKS)',
        '1002699' => '1002699_Hazelnut Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
        '1002705' => '1002705_Mocha Latte Oat Milk (Hot) - KOPI KILEN (DRINKS)',
        '1002695' => '1002695_Green Tea Oat Milk (Hot) - KOPI KILEN (DRINKS)',
        '1002716' => '1002716_Vanilla Latte Oat Milk (Iced) - KOPI KILEN (DRINKS)',
        '1002719' => '1002719_Creamy Dopio Espresso Oat Milk iced (Iced) - KOPI KILEN (DRINKS)',
        '1002703' => '1002703_Macchiato Oat Milk (Hot) - KOPI KILEN (DRINKS)',
        '1002487' => '1002487_Sop Sapi - Kopi Kilen',
        '1001792' => '1001792_Spicy Chicken Bites - KOPI KILEN (MEALS)',
        '1002836' => '1002836_Pastry Kilen - KOPI KILEN (MEALS)',
        '1002825' => '1002825_Iced Calamanci Black Tea - KOPI KILEN (DRINKS)',



        
    ];
}
// public function convertToBom()
// {
//     // =========================
//     // 1. Ambil hasil sales POS
//     // =========================
//     $salesRaw = session('sales_result', []);
//     if (empty($salesRaw)) {
//         abort(404, 'Data sales tidak ditemukan');
//     }

//     // hanya kode numeric
//     $sales = [];
//     foreach ($salesRaw as $key => $qty) {
//         if (is_numeric($key)) {
//             $sales[(string)$key] = (int)$qty;
//         }
//     }

//     // =========================
//     // 2. Load TEMPLATE BOM
//     // =========================
//     $templatePath = storage_path('app/template/TEMPLATE_BOM_FIX.xlsx');
//     if (!file_exists($templatePath)) {
//         abort(500, 'Template BOM tidak ditemukan');
//     }

//     $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
//     $sheet = $spreadsheet->getActiveSheet();

//     $highestRow = $sheet->getHighestRow();
//     $highestCol = $sheet->getHighestColumn();

//     // =========================
//     // 3. Ambil HEADER
//     // =========================
//     $header = $sheet
//         ->rangeToArray("A1:{$highestCol}1", null, true, false)[0];
//     $header = array_map('trim', $header);

//     $codeCol     = array_search('NAMA PRODUK JADI', $header);
//     $kategoriCol = array_search('KATEGORI', $header);
//     $penyusunCol = array_search('PENYUSUN', $header);
//     $gramCol     = array_search('GRAMASI', $header);
//     $salesCol    = array_search('PENJUALAN', $header);
//     $hasilCol    = array_search('HASIL (GRAMASI X PENJULAN)', $header);

//     if (
//         $codeCol === false ||
//         $kategoriCol === false ||
//         $penyusunCol === false ||
//         $gramCol === false ||
//         $salesCol === false ||
//         $hasilCol === false
//     ) {
//         abort(500, 'Header BOM tidak lengkap / tidak sesuai');
//     }

//     // =========================
//     // 4. PASS 1 — FINISH GOOD
//     // =========================
//     // Hitung:
//     // - Penjualan FINISH GOOD
//     // - Akumulasi pemakaian SEMI FINISH GOOD
//     $semiFinishSales = [];

//     for ($row = 2; $row <= $highestRow; $row++) {

//         $kategori = trim(
//             (string)$sheet->getCellByColumnAndRow($kategoriCol + 1, $row)->getValue()
//         );

//         $penyusun = trim(
//             (string)$sheet->getCellByColumnAndRow($penyusunCol + 1, $row)->getValue()
//         );

//         $gramasi = (float)$sheet
//             ->getCellByColumnAndRow($gramCol + 1, $row)
//             ->getValue();

//         // ambil kode produk jadi
//         $productCode = trim(
//             explode(
//                 ' ',
//                 (string)$sheet->getCellByColumnAndRow($codeCol + 1, $row)->getValue()
//             )[0]
//         );

//         $penjualan = $sales[$productCode] ?? 0;
//         $hasil = $penjualan * $gramasi;

//         // isi kolom PENJUALAN & HASIL (FINISH GOOD)
//         $sheet->setCellValueByColumnAndRow($salesCol + 1, $row, $penjualan);
//         $sheet->setCellValueByColumnAndRow($hasilCol + 1, $row, $hasil);

//         // =========================
//         // AKUMULASI SEMI FINISH
//         // =========================
//         if (
//             strpos($kategori, 'FINISH GOOD') !== false &&
//             $penyusun !== ''
//         ) {
//             // contoh: 1001543_Simple Sirup...
//             $penyusunCode = explode('_', $penyusun)[0];

//             if (!isset($semiFinishSales[$penyusunCode])) {
//                 $semiFinishSales[$penyusunCode] = 0;
//             }

//             $semiFinishSales[$penyusunCode] += $hasil;
//         }
//     }

//     // =========================
//     // 5. PASS 2 — SEMI FINISH GOOD
//     // =========================
//     for ($row = 2; $row <= $highestRow; $row++) {

//         $kategori = trim(
//             (string)$sheet->getCellByColumnAndRow($kategoriCol + 1, $row)->getValue()
//         );

//         if (strpos($kategori, 'SEMI FINISH GOOD') === false) {
//             continue;
//         }

//         $productCode = trim(
//             explode(
//                 ' ',
//                 (string)$sheet->getCellByColumnAndRow($codeCol + 1, $row)->getValue()
//             )[0]
//         );

//         if (!isset($semiFinishSales[$productCode])) {
//             continue;
//         }

//         $gramasi = (float)$sheet
//             ->getCellByColumnAndRow($gramCol + 1, $row)
//             ->getValue();

//         $penjualan = $semiFinishSales[$productCode];
//         $hasil = $penjualan * $gramasi;

//         $sheet->setCellValueByColumnAndRow($salesCol + 1, $row, $penjualan);
//         $sheet->setCellValueByColumnAndRow($hasilCol + 1, $row, $hasil);
//     }

//     // =========================
//     // 6. SIMPAN & DOWNLOAD
//     // =========================
//     $tempDir = storage_path('app/public/temp');
//     if (!is_dir($tempDir)) {
//         mkdir($tempDir, 0777, true);
//     }

//     $outputPath = $tempDir . '/BOM_DENGAN_PENJUALAN.xlsx';

//     $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
//     $writer->save($outputPath);

//     return response()
//         ->download($outputPath)
//         ->deleteFileAfterSend(true);
// }

public function convertToBom()
{
    $salesRaw = session('sales_result', []);
    if (empty($salesRaw)) {
        abort(404, 'Data sales tidak ditemukan');
    }

    $sales = [];
    foreach ($salesRaw as $key => $qty) {
        if (is_numeric($key)) {
            $sales[(string)$key] = (int)$qty;
        }
    }

    $templatePath = storage_path('app/template/TEMPLATE_BOM_FIX.xlsx');
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();

    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    $header = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0];
    $header = array_map('trim', $header);

    $codeCol     = array_search('KODE PRODUK JADI', $header);
    $salesCol    = array_search('PENJUALAN', $header);
    $gramCol     = array_search('GRAMASI', $header);
    $hasilCol    = array_search('HASIL (GRAMASI X PENJULAN)', $header);
    $kategoriCol = array_search('KATEGORI', $header);
    $penyusunCol = array_search('PENYUSUN', $header);

    if (
        $codeCol === false || $salesCol === false ||
        $gramCol === false || $hasilCol === false ||
        $kategoriCol === false || $penyusunCol === false
    ) {
        abort(500, 'Header tidak lengkap / salah nama');
    }

    // =========================================
    // PASS 1: ISI FINISH GOOD & HITUNG HASIL
    // =========================================
    for ($row = 2; $row <= $highestRow; $row++) {

        $code = trim((string)$sheet->getCellByColumnAndRow($codeCol + 1, $row)->getValue());
        if ($code === '') continue;

        $penjualan = $sales[$code] ?? 0;
        $gramasi   = (float)$sheet->getCellByColumnAndRow($gramCol + 1, $row)->getValue();

        $sheet->setCellValueByColumnAndRow($salesCol + 1, $row, $penjualan);
        $sheet->setCellValueByColumnAndRow($hasilCol + 1, $row, $penjualan * $gramasi);
    }

    // =========================================
    // PASS 2: HITUNG TOTAL PEMAKAIAN SEMI FINISH
    // SUM HASIL DARI SEMUA BARIS YG MEMAKAI DIA
    // =========================================
    $semiUsage = []; // semiCode => total HASIL

    for ($row = 2; $row <= $highestRow; $row++) {

        $penyusun = trim((string)$sheet->getCellByColumnAndRow($penyusunCol + 1, $row)->getValue());
        if ($penyusun === '') continue;

        $semiCode = trim(explode('_', $penyusun)[0]);
        if (!is_numeric($semiCode)) continue;

        $hasil = (float)$sheet->getCellByColumnAndRow($hasilCol + 1, $row)->getValue();

        if (!isset($semiUsage[$semiCode])) {
            $semiUsage[$semiCode] = 0;
        }

        $semiUsage[$semiCode] += $hasil;
    }

// =========================================
// PASS 3: ISI SEMUA BARIS SEMI FINISH GOOD
// BERDASARKAN KODE PRODUK JADI
// =========================================
for ($row = 2; $row <= $highestRow; $row++) {

    $kategori = trim(
        (string)$sheet->getCellByColumnAndRow($kategoriCol + 1, $row)->getValue()
    );

    if (
        $kategori !== 'SEMI FINISH GOOD - KOPI KILEN (DRINKS)' &&
        $kategori !== 'SEMI FINISH GOOD - KOPI KILEN (MEALS)'
    ) {
        continue;
    }

    $semiCode = trim(
        (string)$sheet->getCellByColumnAndRow($codeCol + 1, $row)->getValue()
    );
    $semiCode = trim(explode(' ', $semiCode)[0]);

    $pemakaian = $semiUsage[$semiCode] ?? 0;
    $gramasi   = (float)$sheet
        ->getCellByColumnAndRow($gramCol + 1, $row)
        ->getValue();

    // ⚠️ INI YANG PENTING
    $sheet->setCellValueByColumnAndRow($salesCol + 1, $row, $pemakaian);
    $sheet->setCellValueByColumnAndRow($hasilCol + 1, $row, $pemakaian * $gramasi);
}

    // =========================================
    // SHEET 2: SALES PRODUK JADI (KODE + NAMA PANJANG + QTY)
    // =========================================
    $map = $this->productMap();

    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('SALES_PRODUK_JADI');

    $sheet2->fromArray(['Product ID', 'Nama Produk', 'Total Qty'], null, 'A1');

    $rowS = 2;

    // optional: urutkan qty terbesar dulu
    arsort($sales);

    foreach ($sales as $code => $qty) {
        $namaPanjang = $map[$code] ?? '(Belum dimapping)';

        $sheet2->setCellValue("A{$rowS}", $code);
        $sheet2->setCellValue("B{$rowS}", $namaPanjang);
        $sheet2->setCellValue("C{$rowS}", $qty);
        $rowS++;
    }


    // =========================================
    // SAVE & DOWNLOAD
    // =========================================
    $tempDir = storage_path('app/public/temp');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    $outputPath = $tempDir . '/BOM_DENGAN_PENJUALAN.xlsx';

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($outputPath);

    return response()->download($outputPath)->deleteFileAfterSend(true);
}





// public function convertToBom()
// {
//     $salesRaw = session('sales_result', []);
//     if (empty($salesRaw)) {
//         abort(404, 'Data sales tidak ditemukan');
//     }

//     $sales = [];
//     foreach ($salesRaw as $key => $qty) {
//         if (is_numeric($key)) {
//             $sales[(string)$key] = (int)$qty;
//         }
//     }

//     $templatePath = storage_path('app/template/TEMPLATE_BOM_FIX.xlsx');
//     $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
//     $sheet = $spreadsheet->getActiveSheet();

//     $highestRow = $sheet->getHighestRow();
//     $highestCol = $sheet->getHighestColumn();

//     $header = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0];
//     $header = array_map('trim', $header);

//     $codeCol  = array_search('KODE PRODUK JADI', $header);
//     $salesCol = array_search('PENJUALAN', $header);

//     // ➕ TAMBAHAN
//     $gramCol  = array_search('GRAMASI', $header);
// $hasilCol = array_search('HASIL (GRAMASI X PENJULAN)', $header);


//     for ($row = 2; $row <= $highestRow; $row++) {

//         $code = trim(
//             (string)$sheet->getCellByColumnAndRow($codeCol + 1, $row)->getValue()
//         );

//         $penjualan = $sales[$code] ?? 0;

//         // KODE LAMA (tetap)
//         $sheet->setCellValueByColumnAndRow(
//             $salesCol + 1,
//             $row,
//             $penjualan
//         );

//         // ➕ TAMBAHAN: HITUNG HASIL
//         if ($gramCol !== false && $hasilCol !== false) {

//             $gramasi = (float)$sheet
//                 ->getCellByColumnAndRow($gramCol + 1, $row)
//                 ->getValue();

//             $hasil = $gramasi * $penjualan;

//             $sheet->setCellValueByColumnAndRow(
//                 $hasilCol + 1,
//                 $row,
//                 $hasil
//             );
//         }
//     }

//     // 🔥 SIMPAN KE PUBLIC STORAGE (tetap)
//     $tempDir = storage_path('app/public/temp');
//     if (!is_dir($tempDir)) {
//         mkdir($tempDir, 0777, true);
//     }

//     $outputPath = $tempDir . '/BOM_DENGAN_PENJUALAN.xlsx';

//     $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
//     $writer->save($outputPath);

//     return response()->download($outputPath)->deleteFileAfterSend(true);
// }


// public function convertToBom()
// {
//     $salesRaw = session('sales_result', []);
//     if (empty($salesRaw)) {
//         abort(404, 'Data sales tidak ditemukan');
//     }

//     $sales = [];
//     foreach ($salesRaw as $key => $qty) {
//         if (is_numeric($key)) {
//             $sales[(string)$key] = (int)$qty;
//         }
//     }

//     $templatePath = storage_path('app/template/TEMPLATE_BOM_FIX.xlsx');
//     $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
//     $sheet = $spreadsheet->getActiveSheet();

//     $highestRow = $sheet->getHighestRow();
//     $highestCol = $sheet->getHighestColumn();

//     $header = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0];
//     $header = array_map('trim', $header);

//     $codeCol  = array_search('KODE PRODUK JADI', $header);
//     $salesCol = array_search('PENJUALAN', $header);

//     for ($row = 2; $row <= $highestRow; $row++) {
//         $code = trim((string)$sheet->getCellByColumnAndRow($codeCol + 1, $row)->getValue());
//         $sheet->setCellValueByColumnAndRow(
//             $salesCol + 1,
//             $row,
//             $sales[$code] ?? 0
//         );
//     }

//     // 🔥 SIMPAN KE PUBLIC STORAGE
//     $tempDir = storage_path('app/public/temp');
//     if (!is_dir($tempDir)) {
//         mkdir($tempDir, 0777, true);
//     }

//     $outputPath = $tempDir . '/BOM_DENGAN_PENJUALAN.xlsx';

//     $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
//     $writer->save($outputPath);

//     return response()->download($outputPath)->deleteFileAfterSend(true);
// }








// public function convertToBom()
// {
//     $templatePath = storage_path('app/template/TEMPLATE_BOM_FIX.xlsx');

//     $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);

//     dd('FILE XLSX BERHASIL DIBACA');
// }
}