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
Route::post('/register',            'AuthController@register');
Route::post('/login',               'AuthController@login');
Route::post('/password-recovery',   'AuthController@recovery');
Route::get('/logout',               'AuthController@logout');

Route::get('/', function () {
    return (new \App\Http\Controllers\PageController())->page();
})->name('home');

Route::get('/login', function () {
    return (new \App\Http\Controllers\PageController())->page('login');
})->name('login');

Route::get('/{page}', 'PageController@page')->where('page', '[A-Za-z\-]+');