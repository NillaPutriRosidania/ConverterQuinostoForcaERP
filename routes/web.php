<?php

use App\Http\Controllers\HitungBahanBakuController;
use App\Http\Controllers\PosIdMaterialController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuinosConverterController;
use App\Http\Controllers\QuinosConverterLantai12Controller;
use App\Http\Controllers\QuinosConverterSQController;
use App\Http\Controllers\GresikSalesController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\ArchiveController;
// use App\Http\Controllers\EbupotController;
use App\Http\Controllers\RevenueJournalController;
use App\Http\Controllers\ProductRecapController;



Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/converter', [QuinosConverterController::class, 'index'])->name('converter.index');
Route::post('/converter/run', [QuinosConverterController::class, 'convert'])->name('converter.run');

Route::get('/converter/lantai-12', 
    [QuinosConverterLantai12Controller::class, 'index']
)->name('converter.lantai12.index');

Route::post('/converter/lantai-12/run', 
    [QuinosConverterLantai12Controller::class, 'convert']
)->name('converter.lantai12.run');

Route::get('/converterSQ', [QuinosConverterSQController::class, 'index'])->name('converterSQ.index');
Route::post('/converterSQ/run', [QuinosConverterSQController::class, 'convert'])->name('converterSQ.run');

// Route::get('/sales', [GresikSalesController::class, 'index'])
//     ->name('sales.index');

    Route::get('/sales/upload', [GresikSalesController::class, 'upload'])
    ->name('sales.upload');

Route::post('/sales/process', [GresikSalesController::class, 'process'])
    ->name('sales.process');

Route::get('/sales/result', [GresikSalesController::class, 'result'])
    ->name('sales.result');

Route::post('/sales/convert-bom', [GresikSalesController::class, 'convertToBom'])
    ->name('sales.convertBom');

    Route::get('/revenue/upload', [RevenueController::class, 'upload'])->name('revenue.upload');
Route::post('/revenue/run', [RevenueController::class, 'run'])->name('revenue.run');Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');

Route::post('/archive/upload', [ArchiveController::class, 'upload'])->name('archive.upload');

Route::get('/archive/browse/{branch?}/{year?}/{month?}', [ArchiveController::class, 'browse'])
    ->name('archive.browse');

Route::get('/archive-download/{path}', [ArchiveController::class, 'download'])
    ->where('path', '.*')
    ->name('archive.download');

Route::post('/archive-delete', [ArchiveController::class, 'delete'])
    ->name('archive.delete');

    
Route::get('/bahan-baku/upload', [HitungBahanBakuController::class, 'rawUpload'])->name('raw.upload');
Route::post('/bahan-baku/process', [HitungBahanBakuController::class, 'rawProcess'])->name('raw.process');
Route::get('/bahan-baku/result', [HitungBahanBakuController::class, 'rawResult'])->name('raw.result');
Route::get('/bahan-baku/download', [HitungBahanBakuController::class, 'rawDownload'])->name('raw.download');


Route::get('/posid-material', [PosIdMaterialController::class, 'upload'])
    ->name('posidMaterial.upload');

Route::post('/posid-material/process', [PosIdMaterialController::class, 'process'])
    ->name('posidMaterial.process');

Route::get('/posid-material/search', [PosIdMaterialController::class, 'search'])
    ->name('posidMaterial.search');

Route::get('/posid-material/result', [PosIdMaterialController::class, 'result'])
    ->name('posidMaterial.result');

Route::get('/posid-material/export', [PosIdMaterialController::class, 'export'])
    ->name('posidMaterial.export');

Route::get('/revenue-journal', [RevenueJournalController::class, 'upload'])->name('revenue_journal.upload');
Route::post('/revenue-journal/run', [RevenueJournalController::class, 'run'])->name('revenue_journal.run');
Route::get('/revenue-journal/download', [RevenueJournalController::class, 'download'])->name('revenue_journal.download');

Route::get('/rekap-products', [ProductRecapController::class, 'index'])
    ->name('rekap.products.index');
Route::post('/rekap-products/result', [ProductRecapController::class, 'result'])->name('rekap.products.result');
Route::post('/rekap-products/download', [ProductRecapController::class, 'download'])
    ->name('rekap.products.download');