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

$router->get('/version', function () use ($router) {
    return $router->app->version();
});

$router->get('/minecraft', 'MinecraftController@index');
$router->get('/minecraft/getDataDump', 'MinecraftController@getDataDump');
$router->get('/minecraft/getPlayersData', 'MinecraftController@getPlayersData');

$router->get('/minecraft/syncMinecraft', 'MinecraftController@syncMinecraft');
$router->get('/minecraft/syncTelegram', 'MinecraftController@syncTelegram');