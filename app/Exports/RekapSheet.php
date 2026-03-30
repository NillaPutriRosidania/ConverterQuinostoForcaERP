<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class RekapSheet implements FromArray, WithTitle
{
    protected array $rows;

    public function __construct($rows)
    {
        $this->rows = is_array($rows) ? $rows : [];
    }

    public function array(): array
    {
        $data = [];

        $data[] = [
            'Produk',
            'Harga Input',
            'Unit Price',
            'Net Sales',
            'Diskon',
            'Pajak',
            'SC',
            'Subtotal',
            'Total Qty',
            'Total Harga Input',
            'Total Unit Price',
            'Total Net Sales',
            'Total Diskon',
            'Total Pajak',
            'Total SC',
            'Total Subtotal',
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['product'] ?? '',
                $row['price_input'] ?? 0,
                $row['unit_price'] ?? 0,
                $row['net_sales'] ?? 0,
                $row['discount'] ?? 0,
                $row['tax'] ?? 0,
                $row['sc'] ?? 0,
                $row['subtotal'] ?? 0,
                $row['qty'] ?? 0,
                $row['price_input_all'] ?? 0,
                $row['unit_price_all'] ?? 0,
                $row['net_sales_all'] ?? 0,
                $row['discount_all'] ?? 0,
                $row['tax_all'] ?? 0,
                $row['sc_all'] ?? 0,
                $row['subtotal_all'] ?? 0,
            ];
        }

        return $data;
    }

    public function title(): string
    {
        return 'Rekap Produk';
    }
}