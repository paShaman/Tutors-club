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
Route::get('/login/vkontakte',          'Social\VkontakteController@redirectToProvider');
Route::get('/login/vkontakte/callback', 'Social\VkontakteController@handleProviderCallback');
Route::get('/login/facebook',           'Social\FacebookController@redirectToProvider');
Route::get('/login/facebook/callback',  'Social\FacebookController@handleProviderCallback');
Route::get('/login/google',             'Social\GoogleController@redirectToProvider');
Route::get('/login/google/callback',    'Social\GoogleController@handleProviderCallback');

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