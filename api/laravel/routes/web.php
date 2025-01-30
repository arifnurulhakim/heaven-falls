<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Route untuk autentikasi broadcasting
// Route::post('/broadcasting/auth', [\App\Http\Controllers\BroadcastController::class, 'auth']);
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

Route::get('/', function () {
    return view('welcome');
});


Broadcast::routes();  // Otomatis menambahkan route untuk broadcasting, termasuk otentikasi

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/chat', [App\Http\Controllers\PusherController::class, 'index']);
Route::get('/messages', [App\Http\Controllers\PusherController::class, 'fetchMessages']);
Route::post('/messages', [App\Http\Controllers\PusherController::class, 'sendMessage']);

