<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/{path?}', function (?string $path = null) {
    return view('app');
})->where('path', '.*');
