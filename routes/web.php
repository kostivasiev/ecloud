<?php
error_reporting(E_ALL ^ E_DEPRECATED);
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

$router->get('/ping', function () use ($router) {
    return 'pong';
});

// Hostname check
$router->get('/host', function () {
    return $_SERVER['SERVER_ADDR'];
});

require('v1.php');
