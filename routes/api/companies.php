<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Company\CompanyController;

Route::get('companies/next-code', [CompanyController::class, 'nextCode']);
Route::apiResource('companies', CompanyController::class);
Route::post('companies/{id}/restore', [CompanyController::class, 'restore']);
Route::delete('companies/{id}/force-delete', [CompanyController::class, 'forceDelete']);
