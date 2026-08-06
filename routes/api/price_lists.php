<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Pricing\PriceListController;

Route::get('price-lists/next-code', [PriceListController::class, 'nextCode']);
Route::apiResource('price-lists', PriceListController::class);
Route::post('price-lists/{id}/restore', [PriceListController::class, 'restore']);
Route::delete('price-lists/{id}/force-delete', [PriceListController::class, 'forceDelete']);