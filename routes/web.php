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

require('v1.php');
require('v2.php');

$router->get('docs.yml', function () {
    return \Illuminate\Support\Facades\File::get(base_path() . '/docs/public.yaml');
});
