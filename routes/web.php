<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
require __DIR__.'/products.php';
require __DIR__.'/customer.php';
require __DIR__.'/orders.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/reports.php';
require __DIR__.'/users.php';

Route::get('/', function () {
    return view('welcome');
});