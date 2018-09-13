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

Auth::routes();

Route::get('/{page}', 'PageController@page')
    ->where('page', '[A-Za-z\-]*')
;

/*Route::post('/register', 'AuthController@register');
/*Route::post('/register', 'AuthController@register');
Route::post('/login',    'LoginController@login');*/