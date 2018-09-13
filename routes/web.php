<?php

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
Route::post('/register', 'AuthController@register')->middleware('guest');
Route::post('/login',    'AuthController@login')->middleware('guest');
Route::get('/logout',    'AuthController@logout')->middleware('auth');

Route::get('/', function () {
    return (new \App\Http\Controllers\PageController())->page();
})->name('home');

Route::get('/auth', function () {
    return (new \App\Http\Controllers\PageController())->page('auth');
})->name('login');

Route::get('/{page}', 'PageController@page')->where('page', '[A-Za-z\-]+');