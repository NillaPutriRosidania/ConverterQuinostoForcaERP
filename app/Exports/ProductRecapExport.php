<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductRecapExport implements WithMultipleSheets
{
    protected $groupedRows;
    protected $detailRows;

    public function __construct($groupedRows, $detailRows)
    {
        $this->groupedRows = $groupedRows;
        $this->detailRows = $detailRows;
    }

    public function sheets(): array
    {
        return [
            new RekapSheet($this->groupedRows),
            new DetailSheet($this->detailRows),
        ];
    }
}