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

// api docs
Route::get('{apiVersion}/docs.yaml', function ($apiVersion) {
    if (!preg_match('/v[0-9]+/si', $apiVersion)) {
        return 'Invalid version';
    }

    $filePath = base_path() . '/docs/' . $apiVersion . '/public-openapi.yaml';
    if (!file_exists($filePath)) {
        return 'Version not found';
    }

    return \cebe\openapi\Writer::writeToYaml(\cebe\openapi\Reader::readFromYamlFile($filePath));
});


Route::group(['middleware' => ['auth', 'is-admin']], function ()  {
    Route::get('{apiVersion}/admin-docs.yaml', function ($apiVersion) {
        if (!preg_match('/v[0-9]+/si', $apiVersion)) {
            return 'Invalid version';
        }

        $filePath = base_path() . '/docs/' . $apiVersion . '/admin-openapi.yaml';
        if (!file_exists($filePath)) {
            return 'Version not found';
        }

        return \cebe\openapi\Writer::writeToYaml(\cebe\openapi\Reader::readFromYamlFile($filePath));
    });
});
