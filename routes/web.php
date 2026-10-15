<?php

use Illuminate\Support\Facades\Route;

/*
 * In production the React app is built into public/. Every non-API URL
 * returns its index.html so React Router can take over.
 */
Route::fallback(function () {
    $spa = public_path('index.html');

    if (! file_exists($spa)) {
        return response('Frontend not built yet. Run the Vite dev server in /frontend, or build it into public/.', 404);
    }

    return response()->file($spa, ['Cache-Control' => 'no-cache']);
});
