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

Route::get('/auth', function () {
    return (new \App\Http\Controllers\PageController())->page('auth');
});
Route::get('/registration', function () {
    return (new \App\Http\Controllers\PageController())->page('registration');
});
Route::get('/policy', function () {
    return (new \App\Http\Controllers\PageController())->page('policy');
});
Route::get('/password-recovery', function () {
    return (new \App\Http\Controllers\PageController())->page('password-recovery');
});

Route::get('/[{page}]', ['middleware' => 'auth', 'PageController@page']);

Route::post('/register', 'AuthController@register');
Route::post('/login',    'AuthController@login');