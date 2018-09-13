<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/auth', function () {
    return (new \App\Http\Controllers\PageController())->page('auth');
});
$router->get('/registration', function () {
    return (new \App\Http\Controllers\PageController())->page('registration');
});
$router->get('/policy', function () {
    return (new \App\Http\Controllers\PageController())->page('policy');
});
$router->get('/password-recovery', function () {
    return (new \App\Http\Controllers\PageController())->page('password-recovery');
});

$router->get('/[{page}]', ['middleware' => 'auth', 'PageController@page']);

$router->post('/register', 'AuthController@register');
$router->post('/login',    'AuthController@login');