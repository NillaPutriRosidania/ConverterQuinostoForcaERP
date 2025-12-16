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

        /* =========================
           PRODUCT MAP (ONLY ADDITION)
           ========================= */
        $productMap = [
            'Americano' => '1001803_Americano (Iced) - KOPI KILEN (DRINKS)',
            'Aren Latte' => '1001805_Aren Latte Ice - KOPI KILEN (DRINKS)',
            'Caffe Latte' => '1001807_Cafe Latte (Ice) - KOPI KILEN (DRINKS)',
            'Espresso' => '1001823_Esspreso - KOPI KILEN (DRINKS)',
            'Machiato' => '1001835_Macchiato (Hot) - KOPI KILEN (DRINKS)',
            'Caramel Latte' => '1001811_Caramel Latte (Iced) - KOPI KILEN (DRINKS)',
            'Vanila Latte' => '1001843_Vanilla Latte (Ice) - KOPI KILEN (DRINKS)',
            'Cappucino' => '1001809_Cappuccino (Iced) - KOPI KILEN (DRINKS)',
            'Creamy Dopio' => '1001814_Creamy Dopio Espresso Ice - KOPI KILEN (DRINKS)',
            'Mochalatte' => '1001837_Mocha Latte (Ice) - KOPI KILEN (DRINKS)',
            'Flat White' => '1001824_Flat White - KOPI KILEN (DRINKS)',
            'V60' => 'V60',

            'Cold White Original' => '1001683_Cold White Original - KOPI KILEN (DRINKS)',
            'Cold White Coconut' => '1001685_Cold White Coconut - KOPI KILEN (DRINKS)',
            'Cold White Caramel' => '1001684_Cold White Caramel - KOPI KILEN (DRINKS)',
            'Cold Brew Sweet' => '1001686_Cold Brew Sweet - KOPI KILEN (DRINKS)',

            'Cold White Original Diskon' => '1001683_Cold White Original - KOPI KILEN (DRINKS)',
            'Cold White Coconut Dsikon' => '1001685_Cold White Coconut - KOPI KILEN (DRINKS)',
            'Cold White Caramel Diskon' => '1001684_Cold White Caramel - KOPI KILEN (DRINKS)',
            'Cold Brew Sweet Diskon' => '1001686_Cold Brew Sweet - KOPI KILEN (DRINKS)',

            'Honey Citrun Tea' => '1001931_Honey Citron Tea Iced - KOPI KILEN (DRINKS)',
            'Lychee Tea' => '1001834_Lychee Tea - KOPI KILEN (DRINKS)',
            'Green tea Latte' => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',
            'Java Tea' => '1001831_Java Tea (Ice) - KOPI KILEN (DRINKS)',
            'Red Velvet' => '1001841_Red Velvet (Ice) - KOPI KILEN (DRINKS)',
            'Lemon Tea' => '1001833_Lemon Tea (Ice) - KOPI KILEN (DRINKS)',
            'Green Tea' => '1001826_Green Tea (Ice) - KOPI KILEN (DRINKS)',
            'Pokka Green Tea' => '1001687_Pokka Green Tea - KOPI KILEN (DRINKS)',
            'Coca-Cola' => '1001698_Coca Cola @250Ml - KOPI KILEN (DRINKS)',
            'Air Mineral' => '1001504_Sanqua Mineral Water 330 Ml - KOPI KILEN (DRINKS)',

            'Add On Lemon' => 'Add On Lemon',
            'Add on Lontong' => 'Add on Lontong',

            'Tahu Lada Garam' => '1001794_Tahu Lada Garam - KOPI KILEN (MEALS)',
            'Tahu Isi' => '1001793_Tahu Isi Sayur - KOPI KILEN (MEALS)',
            'French Fries' => '1001779_French Fries - KOPI KILEN (MEALS)',
            'Cireng' => '1001775_Cireng - KOPI KILEN (MEALS)',
            'Pisang Goreng' => '1001787_Pisang Goreng - KOPI KILEN (MEALS)',
            'Tempe Goreng' => '1001795_Tempe Goreng - KOPI KILEN (MEALS)',
            'Ubi Goreng' => '1001797_Ubi Goreng - KOPI KILEN (MEALS)',
            'Roti Bakar' => '1001788_Roti Bakar - KOPI KILEN (MEALS)',

            'Spundbond' => '1002213_Paper Bag Hitam Spunbond - KOPI KILEN PERLENGKAPAN',

            'Indomie Goreng' => '1002341_Indomie Goreng Kilen - KOPI KILEN (MEALS)',
            'Indomie Goreng Double' => 'Indomie Goreng Double',
            'Indomie Rebus' => '1002340_Indomie Rebus Kilen - KOPI KILEN (MEALS)',
            'Indomie Rebus Double' => 'Indomie Rebus Double',
            'Indomie Kornet' => 'Indomie Kornet',
            'Indomie Kuah Double' => 'Indomie Kuah Double',

            'Internet Kuah' => '1001781_Internet Rebus - KOPI KILEN (MEALS)',
            'Internet Goreng' => '1001780_Internet Goreng Kilen - KOPI KILEN (MEALS)',
            'Nasi Goreng Sapi' => '1001786_Nasi Goreng Sapi - KOPI KILEN (MEALS)',
            'Nasi Goreng Kilen' => '1001785_Nasi Goreng Kilen - KOPI KILEN (MEALS)',
            'Mie Ayam' => '1001783_Mie Ayam - KOPI KILEN (MEALS)',
            'Mie Goreng Betutu' => '1001784_Mie Goreng Ayam Betutu - KOPI KILEN (MEALS)',
            'Kwetiaw Goreng' => '1002296_Kwetiau Goreng - KOPI KILEN (MEALS)',
            'Lontong Sayur' => '1001782_Lontong Sayur - KOPI KILEN (MEALS)',
            'Soto Betawi' => '1001791_Soto Betawi - KOPI KILEN (MEALS)',
            'Ayam Geprek' => '1001770_Ayam Geprek - KOPI KILEN (MEALS)',
            'Chicken Katsu' => '1001773_Chicken Katsu Rice Bowl - KOPI KILEN (MEALS)',
            'Ayam Bakar' => '1001769_Ayam Bakar - KOPI KILEN (MEALS)',
            'Sate Ayam' => '1001774_Chicken Satay - KOPI KILEN (MEALS)',
            'Dori Sambal Matah' => '1001778_Dori Sambal Matah - KOPI KILEN (MEALS)',
            'Bubur Ayam' => '1001771_Bubur Ayam - KOPI KILEN (MEALS)',

            'Telur' => '1002324_Add Egg Chicken/Telur Ayam - KOPI KILEN (MEALS)',
            'Tempe goreng' => '1001795_Tempe Goreng - KOPI KILEN (MEALS)',
            'Chocolatte' => '1001813_Chocolate (Ice) - KOPI KILEN (DRINKS)',
            'Cold White Brew Sweet' => '1001686_Cold Brew Sweet - KOPI KILEN (DRINKS)',
            
            
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


            $masterWritten = [];


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

                $qty = (float) str_replace(',', '', $row[$idxQty] ?? 0);
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
                $nameRaw   = trim($row[$idxProduk] ?? '');
                $productFull = $productMap[$nameRaw] ?? $nameRaw;
                $productId = explode('_', $productFull)[0];

                $out[22] = $productId;


                $out[23] = $priceActual;
                $out[24] = $qty;
                $out[25] = 'PB1 10%';
                $out[26] = 'Y';

                if ($qty == 0) {
                    continue;
                }

                if (isset($masterWritten[$forcaPosId])) {
    foreach ([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20] as $idx) {
        $out[$idx] = '';
    }
} else {
    $masterWritten[$forcaPosId] = true;
}
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