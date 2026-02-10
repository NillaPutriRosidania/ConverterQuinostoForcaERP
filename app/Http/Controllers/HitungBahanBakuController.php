<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class HitungBahanBakuController extends Controller
{
    public function rawUpload(): View
    {
        return view('raw.upload');
    }

public function rawProcess(Request $request): \Illuminate\Http\RedirectResponse
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
    ]);

    $path = $request->file('file')->getRealPath();

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();

    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    $header = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0];
    $header = array_map('trim', $header);

    $penyusunCol = array_search('PENYUSUN', $header);
    $namaCol     = array_search('NAMA PRODUK JADI', $header);
    $kodeCol     = array_search('KODE PRODUK JADI', $header);
    $gramCol     = array_search('GRAMASI', $header);
    $salesCol    = array_search('PENJUALAN', $header);
    $hasilCol    = array_search('HASIL (GRAMASI X PENJULAN)', $header);

    if ($penyusunCol === false || $hasilCol === false) {
        abort(500, 'Header tidak sesuai. Minimal harus ada PENYUSUN dan HASIL (GRAMASI X PENJULAN)');
    }

    $totals = [];   // bahan => total hasil
    $details = [];  // bahan => [ [finish_good, gramasi, penjualan, hasil], ... ]

    for ($row = 2; $row <= $highestRow; $row++) {
        $bahan = trim((string) $sheet->getCellByColumnAndRow($penyusunCol + 1, $row)->getValue());
        if ($bahan === '') continue;

        $hasil = (float) $sheet->getCellByColumnAndRow($hasilCol + 1, $row)->getCalculatedValue();
        if ($hasil == 0) continue;

        $nama = $namaCol !== false
            ? trim((string) $sheet->getCellByColumnAndRow($namaCol + 1, $row)->getValue())
            : '';

        $kode = $kodeCol !== false
            ? trim((string) $sheet->getCellByColumnAndRow($kodeCol + 1, $row)->getValue())
            : '';

        $finishGood = trim($kode . ' ' . $nama);
        if ($finishGood === '') $finishGood = '(Tidak ada nama produk)';

        $gramasi = $gramCol !== false
            ? (float) $sheet->getCellByColumnAndRow($gramCol + 1, $row)->getCalculatedValue()
            : 0;

        $penjualan = $salesCol !== false
            ? (float) $sheet->getCellByColumnAndRow($salesCol + 1, $row)->getCalculatedValue()
            : 0;

        $totals[$bahan] = ($totals[$bahan] ?? 0) + $hasil;

        $details[$bahan][] = [
            'finish_good' => $finishGood,
            'gramasi' => $gramasi,
            'penjualan' => $penjualan,
            'hasil' => $hasil,
        ];
    }

    // sort totals terbesar dulu
    arsort($totals);

    session([
        'raw_totals' => $totals,
        'raw_details' => $details,
    ]);

    return redirect()->route('raw.result');
}


public function rawResult(): \Illuminate\View\View
{
    return view('raw.result', [
        'totals' => session('raw_totals', []),
        'details' => session('raw_details', []),
    ]);
}


    
public function rawDownload()
{
    $details = session('raw_details', []);
    $totals  = session('raw_totals', []);

    if (empty($details) || empty($totals)) {
        abort(404, 'Data bahan baku tidak ditemukan. Silakan proses file dulu.');
    }

    // Sheet 1: TOTAL
    $spreadsheet = new Spreadsheet();
    $sheet1 = $spreadsheet->getActiveSheet();
    $sheet1->setTitle('TOTAL_BAHAN_BAKU');
    $sheet1->setCellValue('A1', 'BAHAN BAKU (PENYUSUN)');
    $sheet1->setCellValue('B1', 'TOTAL PEMAKAIAN');

    arsort($totals);
    $r = 2;
    foreach ($totals as $bahan => $total) {
        $sheet1->setCellValue("A{$r}", $bahan);
        $sheet1->setCellValue("B{$r}", $total);
        $r++;
    }

    // Sheet 2: DETAIL
    $sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('DETAIL_PEMAKAIAN');

// Header (tambah TOTAL)
$sheet2->fromArray(
    ['BAHAN BAKU', 'TOTAL PEMAKAIAN', 'FINISH GOOD', 'GRAMASI', 'PENJUALAN', 'HASIL'],
    null,
    'A1'
);

$row = 2;
foreach ($details as $bahan => $rows) {

    $totalBahan = $totals[$bahan] ?? 0; // ambil totalnya

    foreach ($rows as $item) {
        $sheet2->setCellValue("A{$row}", $bahan);
        $sheet2->setCellValue("B{$row}", $totalBahan);          // ðŸ‘ˆ TOTAL di detail
        $sheet2->setCellValue("C{$row}", $item['finish_good']);
        $sheet2->setCellValue("D{$row}", $item['gramasi']);
        $sheet2->setCellValue("E{$row}", $item['penjualan']);
        $sheet2->setCellValue("F{$row}", $item['hasil']);
        $row++;
    }
}
    $tempDir = storage_path('app/public/temp');
    if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

    $outputPath = $tempDir . '/PEMAKAIAN_BAHAN_BAKU.xlsx';
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($outputPath);

    return response()->download($outputPath)->deleteFileAfterSend(true);
}
}