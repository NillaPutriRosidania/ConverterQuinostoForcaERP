<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RevenueController extends Controller
{
    public function upload()
    {
        return view('revenue.upload');
    }

    public function run(Request $request)
    {
        $request->validate([
            'file_mapping' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('file_mapping')->getRealPath();

        $summaryRows = $this->buildSummaryFromMappingCsv($path);

        $templatePath = storage_path('app/template/template_summary.xlsx');
        if (file_exists($templatePath)) {
            return $this->downloadXlsxFromTemplate($templatePath, $summaryRows);
        }

        return $this->downloadCsv($summaryRows);
    }

    private function buildSummaryFromMappingCsv(string $csvPath): array
    {
        $fh = fopen($csvPath, 'r');
        if (!$fh) abort(500, 'Gagal membuka file mapping.');

        $header = fgetcsv($fh);
        if ($header === false) abort(500, 'Header CSV tidak bisa dibaca.');

        $header = array_map('trim', $header);

        // ✅ Header master sesuai format terbaru
        $idxInvoice  = array_search('FORCA_POSID', $header, true);
        $idxDiscount = array_search('total_discount', $header, true);
        $idxService  = array_search('FORCA_ServiceCharge', $header, true);
        $idxRounding = array_search('FORCA_RoundingAmt', $header, true);

        // Kolom line
        $idxQty      = array_search('FORCA_ImportSalesPOSLine>QtyOrdered', $header, true);
        $idxPrice    = array_search('FORCA_ImportSalesPOSLine>PriceActual', $header, true);
        $idxTaxName  = array_search('FORCA_ImportSalesPOSLine>C_Tax_ID[Name]', $header, true);

        $required = [
            'FORCA_POSID' => $idxInvoice,
            'total_discount' => $idxDiscount,
            'FORCA_ServiceCharge' => $idxService,
            'FORCA_RoundingAmt' => $idxRounding,
            'FORCA_ImportSalesPOSLine>QtyOrdered' => $idxQty,
            'FORCA_ImportSalesPOSLine>PriceActual' => $idxPrice,
            'FORCA_ImportSalesPOSLine>C_Tax_ID[Name]' => $idxTaxName,
        ];

        $missing = [];
        foreach ($required as $name => $idx) {
            if ($idx === false) $missing[] = $name;
        }
        if (!empty($missing)) {
            fclose($fh);
            abort(500, 'Kolom mapping tidak lengkap. Missing: ' . implode(', ', $missing));
        }

        $currentInvoice = null;
        $sum = []; // invoice => data

        while (($row = fgetcsv($fh)) !== false) {
            $invCell = trim($row[$idxInvoice] ?? '');

            // carry-forward invoice (master hanya di baris pertama invoice)
            if ($invCell !== '') {
                $currentInvoice = $invCell;

                if (!isset($sum[$currentInvoice])) {
                    $sum[$currentInvoice] = [
                        'Invoice'        => $currentInvoice,
                        'Subtotal'       => 0.0,
                        'Discount'       => 0.0,
                        'Net Sales'      => 0.0,
                        'Service Charge' => 0.0,
                        'LineTaxSum'     => 0.0,
                        'Tax'            => 0.0,
                        'Rounding'       => 0.0,
                        'Total'          => 0.0,
                    ];
                }

                $disc = (float) str_replace(',', '', trim($row[$idxDiscount] ?? '0'));
                $svc  = (float) str_replace(',', '', trim($row[$idxService] ?? '0'));
                $rnd  = (float) str_replace(',', '', trim($row[$idxRounding] ?? '0'));

                $sum[$currentInvoice]['Discount']       = $disc;
                $sum[$currentInvoice]['Service Charge'] = $svc;
                $sum[$currentInvoice]['Rounding']       = $rnd;
            }

            if ($currentInvoice === null) continue;

            $qty   = (float) str_replace(',', '', trim($row[$idxQty] ?? '0'));
            $price = (float) str_replace(',', '', trim($row[$idxPrice] ?? '0'));
            $base  = $qty * $price;

            $taxName = trim($row[$idxTaxName] ?? '');
            $rate = 0.0;
            if (preg_match('/(\d+(?:\.\d+)?)\s*%/i', $taxName, $m)) {
                $rate = ((float) $m[1]) / 100.0;
            }

            $sum[$currentInvoice]['Subtotal'] += $base;
            $sum[$currentInvoice]['LineTaxSum'] += ($base * $rate);
        }

        fclose($fh);

        foreach ($sum as &$v) {
            $v['Net Sales'] = $v['Subtotal'] - $v['Discount'];
            $v['Tax'] = $v['Net Sales'] * 0.105;
            $v['Total'] = $v['Net Sales'] + $v['Service Charge'] + $v['Tax'] + $v['Rounding'];
            unset($v['LineTaxSum']);
        }

        unset($v);

        uksort($sum, fn($a, $b) => (int)$a <=> (int)$b);

        return array_values($sum);
    }

    private function downloadCsv(array $rows)
    {
        $filename = 'Revenue_Summary_' . date('Ymd_His') . '.csv';
        $temp = fopen('php://temp', 'r+');

        fputcsv($temp, ['Invoice', 'Subtotal', 'Discount', 'Net Sales', 'Service Charge', 'Tax', 'Rounding', 'Total']);

        foreach ($rows as $r) {
            fputcsv($temp, [
                $r['Invoice'],
                $r['Subtotal'],
                $r['Discount'],
                $r['Net Sales'],
                $r['Service Charge'],
                $r['Tax'],
                $r['Rounding'],
                $r['Total'],
            ]);
        }

        rewind($temp);
        $csvContent = stream_get_contents($temp);
        fclose($temp);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function downloadXlsxFromTemplate(string $templatePath, array $rows)
    {
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $startRow = 2;
        $i = 0;

        foreach ($rows as $r) {
            $rowNo = $startRow + $i;

            $sheet->setCellValue("A{$rowNo}", $r['Invoice']);
            $sheet->setCellValue("B{$rowNo}", $r['Subtotal']);
            $sheet->setCellValue("C{$rowNo}", $r['Discount']);
            $sheet->setCellValue("D{$rowNo}", $r['Net Sales']);
            $sheet->setCellValue("E{$rowNo}", $r['Service Charge']);
            $sheet->setCellValue("F{$rowNo}", $r['Tax']);
            $sheet->setCellValue("G{$rowNo}", $r['Rounding']);
            $sheet->setCellValue("H{$rowNo}", $r['Total']);

            $i++;
        }

        $filename = 'Revenue_Summary_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
