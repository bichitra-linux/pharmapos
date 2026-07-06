<?php

declare(strict_types=1);

use App\Http\Controllers\Public\LandingPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'show']);

Route::get('/{path?}', function (?string $path = null) {
    return view('app');
})->where('path', '.*');
