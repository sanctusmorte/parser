<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import/pornstars', [\App\Http\Controllers\ImportController::class, 'pornstars']);
Route::get('/import/tags', [\App\Http\Controllers\ImportController::class, 'tags']);
Route::get('/import/categories', [\App\Http\Controllers\ImportController::class, 'categories']);


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
