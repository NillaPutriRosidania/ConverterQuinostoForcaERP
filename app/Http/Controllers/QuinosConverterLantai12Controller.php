<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DateTime;

class QuinosConverterLantai12Controller extends Controller
{
    public function index()
    {
        return view('converter.lantai12');
    }
    private function detectCsvDelimiter(string $filePath): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $line = fgets(fopen($filePath, 'r'));

        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    public function convert(Request $request)
    {
        $request->validate([
            'file_transaksi' => 'required|file|mimes:csv,xlsx',
        ]);

        $file = $request->file('file_transaksi');
        $ext  = strtolower($file->getClientOriginalExtension());
        $headers = [
            'AD_Org_ID[Name]',
            'FORCA_TransactionType',
            'C_BPartner_ID[Value]',
            'Description',
            'DateOrdered',
            'FORCA_POSID',
            'total_discount',
            'FORCA_DescriptionDisc',
            'FORCA_ServiceCharge',
            'FORCA_RoundingAmt',
            'C_BankAccount_ID[Value]',
            'C_Project_ID[Value]',
            'C_Activity_ID[Value]',
            'M_Warehouse_ID[Value]',
            'SalesRep_ID[Name]',
            'C_Currency_ID',
            'IsActive',
            'C_DocType_ID[Name]',
            'M_PriceList_ID[Name]',
            'C_PaymentTerm_ID[Value]',
            'FORCA_ImportSalesPOSLine>AD_Org_ID[Name]',
            'FORCA_ImportSalesPOSLine>LineNo',
            'FORCA_ImportSalesPOSLine>M_Product_ID[Value]',
            'FORCA_ImportSalesPOSLine>PriceActual',
            'FORCA_ImportSalesPOSLine>QtyOrdered',
            'FORCA_ImportSalesPOSLine>C_Tax_ID[Name]',
            'FORCA_ImportSalesPOSLine>IsActive',
        ];

        $temp = fopen('php://temp', 'r+');
        fputcsv($temp, $headers);
        if ($ext === 'csv') {
            $delimiter = $this->detectCsvDelimiter($file->getRealPath());

            $handle = fopen($file->getRealPath(), 'r');
            $inputHeader = array_map('trim', fgetcsv($handle, 0, $delimiter));

            $idxTanggalAwal  = array_search('Tanggal Awal', $inputHeader);
            $idxTanggalAkhir = array_search('Tanggal Akhir', $inputHeader);
            $idxDiscount     = array_search('total_discount', $inputHeader);
            $idxService      = array_search('ServiceCharge', $inputHeader);
            $idxProduk       = array_search('Produk', $inputHeader);
            $idxQty          = array_search('Qty', $inputHeader);
            $idxItem         = array_search('Unit Price', $inputHeader);

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $tanggalAwal  = trim($row[$idxTanggalAwal] ?? '');
                $tanggalAkhir = trim($row[$idxTanggalAkhir] ?? '');
                if ($tanggalAkhir === '') continue;

                $date  = new DateTime($tanggalAkhir);
                $day   = (int) $date->format('d');
                $month = $date->format('m');
                $year  = $date->format('Y');
                $week = str_pad(ceil($day / 7), 2, '0', STR_PAD_LEFT);
                $forcaPosId = 'LT12W' . $week . $month . $year;
                $bulanNama = $date->format('F');
                $description = "Migrasi Quinos Lantai 12 Transaksi Bulan {$bulanNama}_{$tanggalAwal} - {$tanggalAkhir}";
                $qty          = (float) str_replace(',', '', $row[$idxQty] ?? 0);
                $priceActual = (float) str_replace(',', '', $row[$idxItem] ?? 0);

                $out = array_fill(0, count($headers), '');

                $out[0]  = 'Head Office';
                $out[1]  = 'N';
                $out[2]  = '1000750';
                $out[3]  = $description;
                $out[4]  = $tanggalAkhir;
                $out[5]  = $forcaPosId;
                $out[6]  = str_replace(',', '', $row[$idxDiscount] ?? 0);
                $out[7]  = 'Diskon';
                $out[8]  = str_replace(',', '', $row[$idxService] ?? 0);
                $out[9]  = 0;
                $out[10] = '1113037 Temporary Transit Account';
                $out[11] = '1000009';
                $out[12] = '1000006';
                $out[13] = 'KOPI KILEN SQ - JAKARTA';
                $out[14] = 'Ely Ruknia Sari';
                $out[15] = '303';
                $out[16] = 'Y';
                $out[17] = 'Food and Beverage';
                $out[18] = 'Sales Exclude Tax';
                $out[19] = '1. Immediate';
                $out[20] = 'Head Office';
                $out[21] = '';
                $out[22] = $row[$idxProduk] ?? '';
                $out[23] = $priceActual;
                $out[24] = $qty;
                $out[25] = 'PB1 10%';
                $out[26] = 'Y';

                fputcsv($temp, $out);
            }

            fclose($handle);
        }

        rewind($temp);
        $csvContent = stream_get_contents($temp);
        fclose($temp);

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Mapping_Quinos_Lantai12_FINAL.csv"',
        ]);
    }
}