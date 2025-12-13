<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuinosConverterController;
use App\Http\Controllers\QuinosConverterLantai12Controller;

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