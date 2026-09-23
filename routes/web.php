<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\BoqImportController;
use App\Http\Controllers\CrawlRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriceCandidateReviewController;
use App\Http\Controllers\PriceSourceController;
use App\Http\Controllers\ReferencePriceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/boq-imports/create', [BoqImportController::class, 'create'])->name('boq-imports.create')->middleware('role:admin,estimator');
    Route::post('/boq-imports', [BoqImportController::class, 'store'])->name('boq-imports.store')->middleware('role:admin,estimator');
    Route::get('/boq-imports/{boqImport}', [BoqImportController::class, 'show'])->name('boq-imports.show');
    Route::post('/boq-imports/{boqImport}/crawl', [CrawlRequestController::class, 'store'])->name('crawls.store')->middleware('role:admin,estimator');
    Route::get('/crawls/{crawl}', [CrawlRequestController::class, 'show'])->name('crawls.show');
    Route::get('/price-candidates', [PriceCandidateReviewController::class, 'index'])->name('price-candidates.index')->middleware('role:admin,reviewer');
    Route::post('/price-candidates/{priceCandidate}/approve', [PriceCandidateReviewController::class, 'approve'])->name('price-candidates.approve')->middleware('role:admin,reviewer');
    Route::post('/price-candidates/{priceCandidate}/reject', [PriceCandidateReviewController::class, 'reject'])->name('price-candidates.reject')->middleware('role:admin,reviewer');
    Route::resource('reference-prices', ReferencePriceController::class)->only(['index', 'create', 'store'])->middleware('role:admin,estimator');
    Route::resource('price-sources', PriceSourceController::class)->only(['index', 'create', 'store'])->middleware('role:admin');
});
