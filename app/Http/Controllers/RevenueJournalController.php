<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RevenueJournalController extends Controller
{
    private const SESSION_KEY = 'revenue_journal_rows_v1';

    public function upload()
    {
        return view('revenue_journal.upload');
    }

    public function run(Request $request)
    {
        $request->validate([
            'file_mapping'   => 'required',
            'file_mapping.*' => 'file|mimes:csv,txt,xlsx,xls',
        ]);

        $files = $request->file('file_mapping'); // array UploadedFile
        $allRowsByInvoice = []; // invoice => aggregated row

        foreach ($files as $f) {
            $path = $f->getRealPath();
            $ext  = strtolower($f->getClientOriginalExtension());

            if (in_array($ext, ['xlsx', 'xls'], true)) {
                $rows = $this->buildSummaryFromMappingXlsx($path);
            } else {
                $rows = $this->buildSummaryFromMappingCsv($path);
            }

            // merge invoice
            foreach ($rows as $r) {
                $inv = (string)($r['Invoice'] ?? '');
                if ($inv === '') continue;

                if (!isset($allRowsByInvoice[$inv])) {
                    $allRowsByInvoice[$inv] = $r;
                } else {
                    // jumlahkan field numeric
                    foreach ($r as $k => $v) {
                        if ($k === 'Invoice') continue;
                        $allRowsByInvoice[$inv][$k] = (float)($allRowsByInvoice[$inv][$k] ?? 0) + (float)$v;
                    }
                }
            }
        }

        // urut invoice numeric
        uksort($allRowsByInvoice, fn($a, $b) => (int)$a <=> (int)$b);

        // simpan ke session untuk tombol download
        session([self::SESSION_KEY => array_values($allRowsByInvoice)]);

        return view('revenue_journal.result', [
            'rows' => array_values($allRowsByInvoice),
        ]);
    }

    public function download()
    {
        $rows = session(self::SESSION_KEY, []);
        if (empty($rows)) {
            return redirect()
                ->route('revenue_journal.upload')
                ->withErrors(['Data kosong. Silakan upload & proses dulu sebelum download.']);
        }

        // buat xlsx
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Revenue Journal');

        // header sesuai tampilan “jurnal”
        $header = [
            'Invoice',
            'Penjualan (PB1 10%)',
            'Diskon (PB1 10%)',
            'Total Produk (PB1 10%)',
            'PB 10% Produk',
            '(SC) Hutang Karyawan 4%',
            '(SC) Hutang Accrue Lain 1%',
            'Total SC',
            'PB 10% Service Charge',
            'Total PB 10%',
            'Rounding',
            'Subtotal PB10 Side',
            'Total produk PB1 0%',
            'Grand Before DiscNoTax',
            'Diskon tanpa pajak (PB1 0%)',
            'Total akhir',
        ];

        $sheet->fromArray($header, null, 'A1');

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['Invoice'] ?? '',
                (float)($row['Penjualan'] ?? 0),
                (float)($row['Diskon'] ?? 0),
                (float)($row['Total Produk'] ?? 0),
                (float)($row['PB 10% Produk'] ?? 0),
                (float)($row['(SC) Hutang Karyawan 4%'] ?? 0),
                (float)($row['(SC) Hutang Accrue Lain 1%'] ?? 0),
                (float)($row['Total SC'] ?? 0),
                (float)($row['PB 10% Service Charge'] ?? 0),
                (float)($row['Total PB 10%'] ?? 0),
                (float)($row['Rounding'] ?? 0),
                (float)($row['Subtotal PB10 Side'] ?? 0),
                (float)($row['Total produk PB1 0%'] ?? 0),
                (float)($row['Grand Before DiscNoTax'] ?? 0),
                (float)($row['Diskon tanpa pajak (PB1 0%)'] ?? 0),
                (float)($row['Total akhir'] ?? 0),
            ], null, "A{$r}");
            $r++;
        }

        // autosize sederhana
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Revenue_Journal_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * CSV mapping → jadi ringkasan per invoice sesuai jurnal.
     */
    private function buildSummaryFromMappingCsv(string $csvPath): array
    {
        $fh = fopen($csvPath, 'r');
        if (!$fh) abort(500, 'Gagal membuka file mapping (CSV).');

        $header = fgetcsv($fh);
        if ($header === false) abort(500, 'Header CSV tidak bisa dibaca.');
        $header = array_map('trim', $header);

        $idxInvoice     = array_search('FORCA_POSID', $header, true);
        $idxDisc10      = array_search('total_discount', $header, true);
        $idxSvc         = array_search('FORCA_ServiceCharge', $header, true);
        $idxRounding    = array_search('FORCA_RoundingAmt', $header, true);
        $idxDiscNoTax   = array_search('FORCA_TotalDiscNoTax', $header, true); // optional

        $idxQty         = array_search('FORCA_ImportSalesPOSLine>QtyOrdered', $header, true);
        $idxPrice       = array_search('FORCA_ImportSalesPOSLine>PriceActual', $header, true);
        $idxTaxName     = array_search('FORCA_ImportSalesPOSLine>C_Tax_ID[Name]', $header, true);

        $required = [
            'FORCA_POSID' => $idxInvoice,
            'total_discount' => $idxDisc10,
            'FORCA_ServiceCharge' => $idxSvc,
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

            // carry-forward invoice (master hanya baris pertama)
            if ($invCell !== '') {
                $currentInvoice = $invCell;

                if (!isset($sum[$currentInvoice])) {
                    $sum[$currentInvoice] = $this->blankJournalRow($currentInvoice);
                }

                $disc10 = (float)str_replace(',', '', trim($row[$idxDisc10] ?? '0'));
                $svc    = (float)str_replace(',', '', trim($row[$idxSvc] ?? '0'));
                $rnd    = (float)str_replace(',', '', trim($row[$idxRounding] ?? '0'));

                $discNoTax = 0.0;
                if ($idxDiscNoTax !== false) {
                    $discNoTax = (float)str_replace(',', '', trim($row[$idxDiscNoTax] ?? '0'));
                }

                $sum[$currentInvoice]['Diskon'] = $disc10;
                $sum[$currentInvoice]['Service Charge Raw'] = $svc;
                $sum[$currentInvoice]['Rounding'] = $rnd;
                $sum[$currentInvoice]['Diskon tanpa pajak (PB1 0%)'] = (float)$discNoTax;
            }

            if ($currentInvoice === null) continue;

            $qty   = (float)str_replace(',', '', trim($row[$idxQty] ?? '0'));
            $price = (float)str_replace(',', '', trim($row[$idxPrice] ?? '0'));
            $base  = $qty * $price;

            $taxName = trim($row[$idxTaxName] ?? '');

            // kita butuh split sales: PB1 10% vs PB1 0%
            if (stripos($taxName, 'PB1 0%') !== false) {
                $sum[$currentInvoice]['Total produk PB1 0%'] += $base;
            } else {
                $sum[$currentInvoice]['Penjualan'] += $base;
            }
        }

        fclose($fh);

        // finalize journal math per invoice (sesuai tabel yang kamu kasih)
        foreach ($sum as &$v) {
            $penjualan10 = (float)$v['Penjualan'];
            $diskon10    = (float)$v['Diskon'];
            $totalProduk10 = $penjualan10 - $diskon10;

            $pb10Produk = $totalProduk10 * 0.10;

            // SC split 4% + 1% dari totalProduk10 (mengikuti contoh kamu)
            $svc = (float) $v['Service Charge Raw'];

            $sc4 = round($svc * 0.80, 2);
            $sc1 = round($svc - $sc4, 2);
            $totalSC = $sc4 + $sc1;

            $pb10SC = $totalSC * 0.10;
            $totalPB10 = $pb10Produk + $pb10SC;

            $rounding = (float)$v['Rounding'];

            $subtotalPB10Side = $totalProduk10 + $totalSC + $totalPB10 + $rounding;

            $totalPB0 = (float)$v['Total produk PB1 0%'];

            $grandBeforeDiscNoTax = $subtotalPB10Side + $totalPB0;

            // Diskon tanpa pajak: biasanya NEGATIVE (contoh -1000).
            // Kalau input di mapping kamu POSITIVE, kamu bisa bikin jadi negatif di sini.
            $discNoTax = (float)$v['Diskon tanpa pajak (PB1 0%)'];

            $totalAkhir = $grandBeforeDiscNoTax -  $discNoTax;

            $v['Total Produk'] = $totalProduk10;
            $v['PB 10% Produk'] = $pb10Produk;

            $v['(SC) Hutang Karyawan 4%'] = $sc4;
            $v['(SC) Hutang Accrue Lain 1%'] = $sc1;
            $v['Total SC'] = $totalSC;

            $v['PB 10% Service Charge'] = $pb10SC;
            $v['Total PB 10%'] = $totalPB10;

            $v['Subtotal PB10 Side'] = $subtotalPB10Side;
            $v['Grand Before DiscNoTax'] = $grandBeforeDiscNoTax;
            $v['Total akhir'] = $totalAkhir;

            unset($v['Service Charge Raw']); // raw master tidak dipakai di rumus contoh
        }
        unset($v);

        uksort($sum, fn($a, $b) => (int)$a <=> (int)$b);
        return array_values($sum);
    }

    /**
     * XLSX mapping → dijadikan array rows lalu reuse logic CSV (biar simpel)
     */
private function buildSummaryFromMappingXlsx(string $xlsxPath): array
{
    $spreadsheet = IOFactory::load($xlsxPath);

    $all = []; // invoice => aggregated

    foreach ($spreadsheet->getAllSheets() as $sheet) {

        $data = $sheet->toArray(null, false, false, false);

        if (empty($data) || empty($data[0])) {
            continue;
        }

        // convert jadi format CSV-like biar reuse logic lama
        $header = array_map('trim', $data[0]);

        $tmp = tmpfile();
        if ($tmp === false) continue;

        fputcsv($tmp, $header);

        for ($i = 1; $i < count($data); $i++) {
            fputcsv($tmp, $data[$i]);
        }

        $meta = stream_get_meta_data($tmp);
        $tmpPath = $meta['uri'];

        $rows = $this->buildSummaryFromMappingCsv($tmpPath);

        fclose($tmp);

        // merge antar sheet
        foreach ($rows as $r) {
            $inv = (string)($r['Invoice'] ?? '');
            if ($inv === '') continue;

            if (!isset($all[$inv])) {
                $all[$inv] = $r;
            } else {
                foreach ($r as $k => $v) {
                    if ($k === 'Invoice') continue;
                    $all[$inv][$k] = (float)($all[$inv][$k] ?? 0) + (float)$v;
                }
            }
        }
    }

    uksort($all, fn($a, $b) => (int)$a <=> (int)$b);

    return array_values($all);
}

    private function blankJournalRow(string $invoice): array
    {
        return [
            'Invoice' => $invoice,

            // input/sum
            'Penjualan' => 0.0,                      // PB1 10%
            'Diskon' => 0.0,                         // diskon PB1 10% (dari total_discount)
            'Rounding' => 0.0,
            'Total produk PB1 0%' => 0.0,
            'Diskon tanpa pajak (PB1 0%)' => 0.0,    // dari FORCA_TotalDiscNoTax (biasanya negatif)

            // computed
            'Total Produk' => 0.0,
            'PB 10% Produk' => 0.0,
            '(SC) Hutang Karyawan 4%' => 0.0,
            '(SC) Hutang Accrue Lain 1%' => 0.0,
            'Total SC' => 0.0,
            'PB 10% Service Charge' => 0.0,
            'Total PB 10%' => 0.0,
            'Subtotal PB10 Side' => 0.0,
            'Grand Before DiscNoTax' => 0.0,
            'Total akhir' => 0.0,

            // helper
            'Service Charge Raw' => 0.0,
        ];
    }
}