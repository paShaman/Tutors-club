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
Route::post('/register',            'AuthController@register')->middleware('guest');
Route::post('/login',               'AuthController@login')->middleware('guest');
Route::post('/password-recovery',   'AuthController@recovery')->middleware('guest');
Route::get('/logout',               'AuthController@logout')->middleware('auth');
Route::get('/auth',                 'AuthController@auth')->name('auth')->middleware('signed');
Route::get('/email/verify/{id}',    'AuthController@verify')->name('verification.verify')->middleware('signed')->middleware('auth');
Route::get('/email/resend',         'AuthController@resend')->name('verification.resend')->middleware('auth');

//sender
Route::post('/sender/subscribe',   'SenderController@subscribe')->middleware('auth');
Route::post('/sender/unsubscribe', 'SenderController@unsubscribe')->middleware('auth');

//user
Route::post('/user/settings', 'UserController@settings')->middleware('auth');

//admin
Route::get('/admin', 'Admin\PanelController@index')->middleware('admin');
Route::get('/admin/user/list', 'Admin\UserController@usersList')->middleware('admin');
Route::get('/admin/payment/list', 'Admin\PaymentController@paymentsList')->middleware('admin');
Route::post('/admin/payment/add', 'Admin\PaymentController@paymentAdd')->middleware('admin');

//test urls
Route::get('/sender/test', 'SenderController@test')->middleware('verified');