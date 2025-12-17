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
    // dd(session('sales_result', []));

    return view('sales.result', [
        'data' => session('sales_result', [])
    ]);
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