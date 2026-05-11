<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// This route will catch all non-API requests and serve the main view.
// Vue Router will then handle the client-side routing.
Route::get('/{any?}', function () {
    return view('welcome'); // Assuming 'welcome.blade.php' is your SPA entry point
})->where('any', '.*');
