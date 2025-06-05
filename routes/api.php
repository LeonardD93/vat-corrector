<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VatController;


Route::post('/vat/upload', [VatController::class, 'upload']);
Route::post('/vat/check', [VatController::class, 'check']);
Route::get('/vat/export/{uuid}/{type}', [VatController::class, 'download']);