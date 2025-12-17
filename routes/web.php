<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuinosConverterController;
use App\Http\Controllers\QuinosConverterLantai12Controller;
use App\Http\Controllers\QuinosConverterSQController;
use App\Http\Controllers\GresikSalesController;

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