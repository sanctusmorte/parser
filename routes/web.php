<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ParseSiteController;
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

Route::get('/import/pornstars', [ImportController::class, 'pornstars']);
Route::get('/import/tags', [ImportController::class, 'tags']);
Route::get('/import/categories', [ImportController::class, 'categories']);
Route::get('/import/sites', [ImportController::class, 'sites']);

Route::get('/parse/sites/first/{id}', [ParseSiteController::class, 'first'])->name('parse-site-first');


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
