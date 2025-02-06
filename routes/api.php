<?php

use App\Http\Controllers\LotImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/import-lots', [LotImportController::class, 'import'])
    ->name('lots.import');
