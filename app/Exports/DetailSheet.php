<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class DetailSheet implements FromArray, WithTitle
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        $data[] = [
            'Produk',
            'Qty',
            'Harga Input',
            'Unit Price',
            'Net Sales',
            'Diskon',
            'Diskon All',
            'Pajak',
            'Pajak All',
            'SC',
            'SC All',
            'Subtotal',
            'Subtotal All'
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['product'],
                $row['qty'],
                $row['price_input'],
                $row['unit_price'],
                $row['net_sales'],
                $row['discount'],
                $row['discount_all'],
                $row['tax'],
                $row['tax_all'],
                $row['sc'],
                $row['sc_all'],
                $row['subtotal'],
                $row['subtotal_all'],
            ];
        }

        return $data;
    }

    public function title(): string
    {
        return 'Detail Input';
    }
}