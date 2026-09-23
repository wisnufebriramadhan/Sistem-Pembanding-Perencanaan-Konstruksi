<?php

use App\Http\Controllers\Api\CrawlQueueController;
use App\Http\Controllers\Api\PriceSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/price-candidates', [PriceSyncController::class, 'store'])->middleware('throttle:30,1');
Route::post('/crawls/next', [CrawlQueueController::class, 'next'])->middleware('throttle:30,1');
Route::post('/crawls/{crawl}/extract-government-pdf', [CrawlQueueController::class, 'extractGovernmentPdf'])->middleware('throttle:5,1');
Route::post('/crawls/{crawl}/complete', [CrawlQueueController::class, 'complete'])->middleware('throttle:30,1');
