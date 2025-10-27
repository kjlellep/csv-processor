<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app'     => config('app.name'),
        'env'     => config('app.env'),
        'version' => app()->version(),
        'status'  => 'ok',
    ]);
});

Route::view('/docs', 'docs');
