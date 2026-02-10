<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;


class PosIdMaterialController extends Controller
{
    public function upload()
    {
        return view('posid_material.upload');
    }

    public function process(Request $request)
    {
        $request->validate([
            'files'   => 'required',
            'files.*' => 'file|mimes:csv,txt',
            // BOM optional (kalau mau upload sendiri)
            // 'bom'     => 'nullable|file|mimes:xlsx,xls',
        ]);

        // =========================
        // 1) Load BOM (template default, tidak perlu upload)
        // =========================
        $bomPath = storage_path('app/template/TEMPLATE_BOM_FIX.xlsx');

        if (!file_exists($bomPath)) {
            abort(500, 'File BOM tidak ditemukan di: ' . $bomPath);
        }

        [$productToIngredients, $allIngredients] = $this->buildBomIndex($bomPath);


        // =========================
        // 2) Read Mapping CSV(s) -> POSID => products
        // =========================
        $posidToProducts = []; // posid => [productId => qtySum]
        $posidToIngredients = []; // posid => [ingredientString => true]

        foreach ($request->file('files') as $file) {
            $rows = array_map('str_getcsv', file($file->getRealPath()));
            if (count($rows) < 2) continue;

            $header = array_map('trim', array_shift($rows));

            $posidIndex = array_search('FORCA_POSID', $header);
            $productIndex = array_search('FORCA_ImportSalesPOSLine>M_Product_ID[Value]', $header);
            $qtyIndex = array_search('FORCA_ImportSalesPOSLine>QtyOrdered', $header);

            if ($posidIndex === false || $productIndex === false) {
                continue;
            }

            foreach ($rows as $row) {
                if (!isset($row[$posidIndex], $row[$productIndex])) continue;

                $posid = trim((string)$row[$posidIndex]);
                $productId = trim((string)$row[$productIndex]);

                if ($posid === '' || $productId === '' || !is_numeric($productId)) {
                    continue;
                }

                // qty optional (kalau tidak ada, tetap simpan product)
                $qty = 1;
                if ($qtyIndex !== false && isset($row[$qtyIndex])) {
                    $qtyRaw = trim((string)$row[$qtyIndex]);
                    $qtyRaw = str_replace([' ', ','], ['', '.'], $qtyRaw); // jaga-jaga
                    $qty = (float)$qtyRaw;
                    if ($qty <= 0) $qty = 1;
                }

                if (!isset($posidToProducts[$posid])) $posidToProducts[$posid] = [];
                if (!isset($posidToProducts[$posid][$productId])) $posidToProducts[$posid][$productId] = 0;
                $posidToProducts[$posid][$productId] += $qty;

                // gabungkan ingredients dari product
                $ings = $productToIngredients[$productId] ?? [];
                if (!isset($posidToIngredients[$posid])) $posidToIngredients[$posid] = [];

                foreach ($ings as $ing) {
                    $posidToIngredients[$posid][$ing] = true;
                }
            }
        }

        // Simpan ke session untuk halaman search/result
        session([
            'posid_material_product_to_ingredients' => $productToIngredients,
            'posid_material_all_ingredients'        => $allIngredients,
            'posid_material_posid_products'         => $posidToProducts,
            'posid_material_posid_ingredients'      => $posidToIngredients,
        ]);

        return redirect()->route('posidMaterial.search');
    }

    public function search()
    {
        $allIngredients = session('posid_material_all_ingredients', []);
        if (empty($allIngredients)) {
            // belum upload
            return redirect()->route('posidMaterial.upload');
        }

        // tampilkan daftar bahan baku
        sort($allIngredients);

        return view('posid_material.search', [
            'ingredients' => $allIngredients
        ]);
    }

    public function result(Request $request)
    {
        $mode = $request->query('mode', 'using'); // using | not_using
        $selected = $request->query('ingredients', []); // array
        if (!is_array($selected)) $selected = [$selected];
        $selected = array_values(array_filter(array_map('trim', $selected)));

        $posidToProducts = session('posid_material_posid_products', []);
        $posidToIngredients = session('posid_material_posid_ingredients', []);

        if (empty($posidToProducts)) {
            return redirect()->route('posidMaterial.upload');
        }

        $results = [];

        foreach ($posidToProducts as $posid => $products) {
            $ingSet = $posidToIngredients[$posid] ?? [];
            $ingKeys = array_keys($ingSet);

            $match = true;

            if (!empty($selected)) {
                if ($mode === 'using') {
                    // AND: harus mengandung semua bahan baku terpilih
                    foreach ($selected as $need) {
                        if (!isset($ingSet[$need])) {
                            $match = false;
                            break;
                        }
                    }
                } else {
                    // not_using: TIDAK BOLEH pakai satupun bahan terpilih
                    foreach ($selected as $ban) {
                        if (isset($ingSet[$ban])) {
                            $match = false;
                            break;
                        }
                    }
                }
            }

            if (!$match) continue;

            // ringkasan produk terjual (ambil top 10 by qty)
            arsort($products);
            $topProducts = array_slice($products, 0, 10, true);

            $results[] = [
                'posid' => $posid,
                'product_count' => count($products),
                'top_products' => $topProducts,
                'ingredient_count' => count($ingKeys),
            ];
        }

        // urutkan posid (atau bisa urutkan product_count desc)
        usort($results, fn($a,$b) => $b['product_count'] <=> $a['product_count']);

        return view('posid_material.result', [
            'mode' => $mode,
            'selected' => $selected,
            'results' => $results,
            'total_posid' => count($posidToProducts),
        ]);
    }

    // =========================
    // Helper: Build BOM index
    // =========================
    private function buildBomIndex(string $bomPath): array
    {
        $spreadsheet = IOFactory::load($bomPath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        $header = $sheet->rangeToArray("A1:{$highestCol}1", null, true, false)[0];
        $header = array_map('trim', $header);

        $codeCol = array_search('KODE PRODUK JADI', $header);
        $penyusunCol = array_search('PENYUSUN', $header);

        if ($codeCol === false || $penyusunCol === false) {
            abort(500, 'Header BOM harus ada "KODE PRODUK JADI" & "PENYUSUN"');
        }

        $productToIngredients = [];
        $allIngredients = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $code = trim((string)$sheet->getCellByColumnAndRow($codeCol + 1, $row)->getValue());
            $penyusun = trim((string)$sheet->getCellByColumnAndRow($penyusunCol + 1, $row)->getValue());

            if ($code === '' || !is_numeric($code)) continue;
            if ($penyusun === '') continue;

            // normalisasi: rapihin spasi doang
            $penyusun = preg_replace('/\s+/', ' ', $penyusun);

            if (!isset($productToIngredients[$code])) $productToIngredients[$code] = [];
            $productToIngredients[$code][$penyusun] = true;
            $allIngredients[$penyusun] = true;
        }

        // set -> array
        foreach ($productToIngredients as $code => $set) {
            $productToIngredients[$code] = array_keys($set);
        }

        return [$productToIngredients, array_keys($allIngredients)];
    }

    public function export(Request $request)
{
    $mode = $request->query('mode', 'using');
    $selected = $request->query('ingredients', []);
    if (!is_array($selected)) $selected = [$selected];
    $selected = array_values(array_filter(array_map('trim', $selected)));

    $posidToProducts = session('posid_material_posid_products', []);
    $posidToIngredients = session('posid_material_posid_ingredients', []);

    if (empty($posidToProducts)) {
        return redirect()->route('posidMaterial.upload');
    }

    // ===== hitung results sama seperti result() =====
    $results = [];

    foreach ($posidToProducts as $posid => $products) {
        $ingSet = $posidToIngredients[$posid] ?? [];
        $ingKeys = array_keys($ingSet);

        $match = true;

        if (!empty($selected)) {
            if ($mode === 'using') {
                foreach ($selected as $need) {
                    if (!isset($ingSet[$need])) {
                        $match = false;
                        break;
                    }
                }
            } else {
                foreach ($selected as $ban) {
                    if (isset($ingSet[$ban])) {
                        $match = false;
                        break;
                    }
                }
            }
        }

        if (!$match) continue;

        // top products (10)
        arsort($products);
        $topProducts = array_slice($products, 0, 10, true);

        // total qty (sum semua produk di posid)
        $totalQty = 0;
        foreach ($products as $q) $totalQty += (float)$q;

        $results[] = [
            'posid' => $posid,
            'product_count' => count($products),
            'ingredient_count' => count($ingKeys),
            'total_qty' => $totalQty,
            'top_products_str' => collect($topProducts)
                ->map(fn($qty, $pid) => $pid . ' (' . $qty . ')')
                ->implode(', '),
        ];
    }

    usort($results, fn($a,$b) => $b['product_count'] <=> $a['product_count']);

    // ===== buat Excel =====
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('POSID_RESULT');

    // header
    $sheet->fromArray([
        ['Mode', 'Filter Bahan Baku', 'POS ID', 'Produk Unik', 'Bahan Unik', 'Total Qty', 'Top Produk (10)']
    ], null, 'A1');

    $filterText = empty($selected) ? '(Tidak ada filter)' : implode(' | ', $selected);
    $modeText = ($mode === 'using') ? 'Menggunakan' : 'Tidak menggunakan';

    $row = 2;
    foreach ($results as $r) {
        $sheet->setCellValue("A{$row}", $modeText);
        $sheet->setCellValue("B{$row}", $filterText);
        $sheet->setCellValue("C{$row}", $r['posid']);
        $sheet->setCellValue("D{$row}", $r['product_count']);
        $sheet->setCellValue("E{$row}", $r['ingredient_count']);
        $sheet->setCellValue("F{$row}", $r['total_qty']);
        $sheet->setCellValue("G{$row}", $r['top_products_str']);
        $row++;
    }

    // autosize kolom A-G
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'POSID_RESULT_' . strtoupper($mode) . '_' . date('Ymd_His') . '.xlsx';

    $response = new StreamedResponse(function () use ($spreadsheet) {
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    });

    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename.'"');
    $response->headers->set('Cache-Control', 'max-age=0');

    return $response;
}

}
