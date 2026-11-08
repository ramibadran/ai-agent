<?php

use App\Http\Controllers\API\V1\Commerce\CommerceReportController;
use Illuminate\Support\Facades\Route;

Route::name('commerce.reports.')
    ->prefix('reports')
    ->middleware('commerceApiKey')
    ->group(function (): void {
        Route::get('sales-summary', [CommerceReportController::class, 'salesSummary'])->name('sales-summary');
        Route::get('sales-comparison', [CommerceReportController::class, 'salesComparison'])->name('sales-comparison');
        Route::get('top-products', [CommerceReportController::class, 'topProducts'])->name('top-products');
        Route::get('products/{sku}/stock', [CommerceReportController::class, 'productStock'])->name('products.stock');
        Route::get('inventory/low-stock', [CommerceReportController::class, 'lowStockProducts'])->name('inventory.low-stock');
    });
