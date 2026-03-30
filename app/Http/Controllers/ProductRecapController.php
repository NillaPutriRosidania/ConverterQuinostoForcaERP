<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
 use App\Exports\ProductRecapExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductRecapController extends Controller
{
    public function index()
    {
        $products = $this->getProducts();

        return view('rekap-products.index', compact('products'));
    }

    public function result(Request $request)
    {
        $items = $request->input('items', []);

        $filteredItems = collect($items)
            ->map(function ($item) {
                return [
                    'product' => trim((string)($item['product'] ?? '')),
                    'qty' => (float)($item['qty'] ?? 0),
                    'price_input' => (float)($item['price_input'] ?? 0),
                ];
            })
            ->filter(function ($item) {
                return $item['qty'] > 0 || $item['price_input'] > 0 || $item['product'] !== '';
            })
            ->values()
            ->all();

        if (count($filteredItems) === 0) {
            return redirect()
                ->route('rekap.products.index')
                ->with('error', 'Belum ada data yang bisa direkap.');
        }

        $detailRows = [];
        $groupedRows = [];

        foreach ($filteredItems as $item) {
            $product = $item['product'];
            $qty = $item['qty'];
            $priceInput = $item['price_input'];

            if ($qty <= 0 || $priceInput <= 0) {
                continue;
            }

            $displayProduct = $product !== '' ? $product : '(Produk tidak ditemukan)';

            $unitPrice = $priceInput / 0.8;
            $netSales = $priceInput / 1.155;
            $discount = $unitPrice - $netSales;
            $discountAll = $discount * $qty;
            $tax = 0.105 * $netSales;
            $taxAll = $tax * $qty;
            $sc = 0.05 * $netSales;
            $scAll = $sc * $qty;
            $subtotal = $netSales + $tax + $sc;
            $subtotalAll = $subtotal * $qty;
            $priceInputAll = $priceInput * $qty;

            $row = [
                'product' => $displayProduct,
                'qty' => $qty,
                'price_input' => $priceInput,
                'price_input_all' => $priceInputAll,
                'unit_price' => $unitPrice,
                'unit_price_all' => $unitPrice * $qty,
                'net_sales' => $netSales,
                'net_sales_all' => $netSales * $qty,
                'discount' => $discount,
                'discount_all' => $discountAll,
                'tax' => $tax,
                'tax_all' => $taxAll,
                'sc' => $sc,
                'sc_all' => $scAll,
                'subtotal' => $subtotal,
                'subtotal_all' => $subtotalAll,
            ];

            $detailRows[] = $row;

            // grouping berdasarkan produk + harga input
            $groupKey = $displayProduct . '||' . number_format($priceInput, 2, '.', '');

            if (!isset($groupedRows[$groupKey])) {
                $groupedRows[$groupKey] = [
                    'product' => $displayProduct,
                    'price_input' => $priceInput,
                    'qty' => 0,
                    'price_input_all' => 0,
                    'unit_price' => $unitPrice,
                    'unit_price_all' => 0,
                    'net_sales' => $netSales,
                    'net_sales_all' => 0,
                    'discount' => $discount,
                    'discount_all' => 0,
                    'tax' => $tax,
                    'tax_all' => 0,
                    'sc' => $sc,
                    'sc_all' => 0,
                    'subtotal' => $subtotal,
                    'subtotal_all' => 0,
                ];
            }

            $groupedRows[$groupKey]['qty'] += $qty;
            $groupedRows[$groupKey]['price_input_all'] += $priceInputAll;
            $groupedRows[$groupKey]['unit_price_all'] += $unitPrice * $qty;
            $groupedRows[$groupKey]['net_sales_all'] += $netSales * $qty;
            $groupedRows[$groupKey]['discount_all'] += $discountAll;
            $groupedRows[$groupKey]['tax_all'] += $taxAll;
            $groupedRows[$groupKey]['sc_all'] += $scAll;
            $groupedRows[$groupKey]['subtotal_all'] += $subtotalAll;
        }

        $groupedRows = array_values($groupedRows);

        $grandTotals = [
            'qty' => 0,
            'price_input_all' => 0,
            'unit_price_all' => 0,
            'net_sales_all' => 0,
            'discount_all' => 0,
            'tax_all' => 0,
            'sc_all' => 0,
            'subtotal_all' => 0,
        ];

        foreach ($detailRows as $row) {
            $grandTotals['qty'] += $row['qty'];
            $grandTotals['price_input_all'] += $row['price_input_all'];
            $grandTotals['unit_price_all'] += $row['unit_price_all'];
            $grandTotals['net_sales_all'] += $row['net_sales_all'];
            $grandTotals['discount_all'] += $row['discount_all'];
            $grandTotals['tax_all'] += $row['tax_all'];
            $grandTotals['sc_all'] += $row['sc_all'];
            $grandTotals['subtotal_all'] += $row['subtotal_all'];
        }

        return view('rekap-products.result', [
            'detailRows' => $detailRows,
            'groupedRows' => $groupedRows,
            'grandTotals' => $grandTotals,
        ]);
    }

    private function getProducts(): array
    {
        $products = [
            'Esspreso - KOPI KILEN (DRINKS)',
            'Americano (Iced) - KOPI KILEN (DRINKS)',
            'Americano (Hot) - KOPI KILEN (DRINKS)',
            'Macchiato (Hot) - KOPI KILEN (DRINKS)',
            'Piccolo (Hot) - KOPI KILEN (DRINKS)',
            'Flat White - KOPI KILEN (DRINKS)',
            'Cafe Latte (Hot) - KOPI KILEN (DRINKS)',
            'Cafe Latte (Iced) - KOPI KILEN (DRINKS)',
            'Cappuccino (Hot) - KOPI KILEN (DRINKS)',
            'Cappuccino (Iced) - KOPI KILEN (DRINKS)',
            'Aren Latte Fresh Milk (Hot) - KOPI KILEN (DRINKS)',
            'Aren Latte Fresh Milk (Iced) - KOPI KILEN (DRINKS)',
            'Caramel Latte (Iced) - KOPI KILEN (DRINKS)',
            'Hazelnut Latte (Iced) - KOPI KILEN (DRINKS)',
            'Vanilla Latte (Iced) - KOPI KILEN (DRINKS)',
            'Mocha Latte (Iced) - KOPI KILEN (DRINKS)',
            'Cold White Original - KOPI KILEN (DRINKS)',
            'Cold Brew Sweet - KOPI KILEN (DRINKS)',
            'Cold White Caramel - KOPI KILEN (DRINKS)',
            'Cold White Coconut - KOPI KILEN (DRINKS)',
            'Honey Citron Tea (Hot) - KOPI KILEN (DRINKS)',
            'Honey Citron Tea (Iced) - KOPI KILEN (DRINKS)',
            'Green Tea (Hot) - KOPI KILEN (DRINKS)',
            'Green Tea (Iced) - KOPI KILEN (DRINKS)',
            'Red Velvet (Iced) - KOPI KILEN (DRINKS)',
            'Chocolate (Hot) - KOPI KILEN (DRINKS)',
            'Chocolate (Iced) - KOPI KILEN (DRINKS)',
            'Lemon Tea (Iced) - KOPI KILEN (DRINKS)',
            'Lychee Tea (Iced) - KOPI KILEN (DRINKS)',
            'Java Tea (Hot) - KOPI KILEN (DRINKS)',
            'Java Tea (Iced) - KOPI KILEN (DRINKS)',
            'Add on Lemon Juice Syrup 1L - KOPI KILEN (DRINKS)',
            'Coca Cola @250Ml - KOPI KILEN (DRINKS)',
            'Coca Cola Zero - KOPI KILEN (DRINKS)',
            'Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',
            'Gayo Bubuk @250 Gr - KOPI KILEN (DRINKS)',
            'Toraja Sapan (V60) Beans - KOPI KILEN (DRINKS)',

            'Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'Tahu Lada Garam - KOPI KILEN (MEALS)',
            'Tahu Isi Sayur - KOPI KILEN (MEALS)',
            'Tempe Goreng - KOPI KILEN (MEALS)',
            'Pisang Goreng - KOPI KILEN (MEALS)',
            'Ubi Goreng - KOPI KILEN (MEALS)',
            'Cireng - KOPI KILEN (MEALS)',
            'Roti Bakar - KOPI KILEN (MEALS)',
            'Toast Kaya Butter - KOPI KILEN (MEALS)',
            'French Fries - KOPI KILEN (MEALS)',
            'Chocolate Cake with Espresso - KOPI KILEN (MEALS)',
            'Banana Caramel - KOPI KILEN (MEALS)',
            'Add On Indomie Goreng - KOPI KILEN (MEALS)',
            'Indomie Goreng - KOPI KILEN (MEALS)',
            'Indomie Rebus - KOPI KILEN (MEALS)',
            'Add On Indomie Rebus - KOPI KILEN (MEALS)',
            'Internet Rebus - KOPI KILEN (MEALS)',
            'Nasi Goreng Sapi - KOPI KILEN (MEALS)',
            'Mie Goreng Ayam Betutu - KOPI KILEN (MEALS)',
            'Nasi Goreng Kilen - KOPI KILEN (MEALS)',
            'Kwetiau Goreng - KOPI KILEN (MEALS)',
            'Mie Ayam - KOPI KILEN (MEALS)',
            'Soto Ayam - KOPI KILEN (MEALS)',
            'Soto Betawi - KOPI KILEN (MEALS)',
            'Lontong Sayur - KOPI KILEN (MEALS)',
            'Sate Ayam - KOPI KILEN (MEALS)',
            'Ayam Bakar - KOPI KILEN (MEALS)',
            'Chicken Katsu Rice Bowl - KOPI KILEN (MEALS)',
            'Chicken Blackpepper - KOPI KILEN (MEALS)',
            'Mie Ayam - KOPI KILEN (MEALS)',
            'Dori Sambal Matah - KOPI KILEN (MEALS)',
            'Ayam Geprek - KOPI KILEN (MEALS)',
        ];

        $products = array_map(fn ($item) => trim($item), $products);
        $products = array_values(array_unique($products));
        sort($products);

        return $products;
    }



    public function download(Request $request)
    {
        $groupedRows = json_decode($request->groupedRows, true);
        $detailRows = json_decode($request->detailRows, true);

        return Excel::download(
            new ProductRecapExport($groupedRows, $detailRows),
            'rekap_produk.xlsx'
        );
    }}