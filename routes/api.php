<?php

use App\Http\Controllers\LotImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Needs an API token (Sanctum). The person must be able to edit lots in every
// division the file imports into; see LotImportController.
Route::post('/import-lots', [LotImportController::class, 'import'])
    ->middleware('auth:sanctum')
    ->name('lots.import');
