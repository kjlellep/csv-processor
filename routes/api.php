<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{VendorController, VendorRuleController};
use App\Http\Controllers\{InventoryController, ExportController};

Route::apiResource('vendors', VendorController::class);
Route::get('vendors/{vendor}/rules', [VendorRuleController::class, 'index']);
Route::post('vendors/{vendor}/rules', [VendorRuleController::class, 'store']);
Route::put('vendors/{vendor}/rules/{rule}', [VendorRuleController::class, 'update']);
Route::delete('vendors/{vendor}/rules/{rule}', [VendorRuleController::class, 'destroy']);
Route::post('vendors/{vendor}/rules/reorder', [VendorRuleController::class, 'reorder']);
Route::post('vendors/{vendor}/inventory', [InventoryController::class, 'store']);
Route::get('vendors/{vendor}/export', [ExportController::class, 'index']);

