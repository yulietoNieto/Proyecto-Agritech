<?php

use Illuminate\Support\Facades\Route;

// SPA: serve the frontend shell for all routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
