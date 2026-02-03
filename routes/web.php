<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    ____sql();
    return redirect('/api/v1/documentation');
    return view('welcome');
});
