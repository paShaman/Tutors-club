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

//pages
Route::get('/',                     'PageController@page')->name('home');
Route::get('/login',                'PageController@login')->name('login')->middleware('guest');
Route::get('/register',             'PageController@register')->middleware('guest');
Route::get('/password-recovery',    'PageController@passwordRecovery')->middleware('guest');
Route::get('/settings',             'PageController@settings')->name('settings');
Route::get('/info/{page}',          'PageController@page')->where('page', '[A-Za-z\-]+');

//social
Route::get('/login/vkontakte',          'Social\VkontakteController@redirectToProvider');
Route::get('/login/vkontakte/callback', 'Social\VkontakteController@handleProviderCallback');
Route::get('/login/facebook',           'Social\FacebookController@redirectToProvider');
Route::get('/login/facebook/callback',  'Social\FacebookController@handleProviderCallback');
Route::get('/login/google',             'Social\GoogleController@redirectToProvider');
Route::get('/login/google/callback',    'Social\GoogleController@handleProviderCallback');
Route::post('/social/disconnect',       'SocialController@disconnect')->middleware('auth');

//auth
Route::post('/register',            'AuthController@register');
Route::post('/login',               'AuthController@login');
Route::post('/password-recovery',   'AuthController@recovery');
Route::get('/logout',               'AuthController@logout');

//sender
Route::post('/sender/subscribe',   'SenderController@subscribe')->middleware('auth');
Route::post('/sender/unsubscribe', 'SenderController@unsubscribe')->middleware('auth');

//user
Route::post('/user/settings', 'UserController@settings')->middleware('auth');

//test urls
Route::get('/sender/test', 'SenderController@test');