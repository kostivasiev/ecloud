<?php

/**
 * v2 Routes
 */

$middleware = [
    'auth',
    'paginator-limit:'.env('PAGINATION_LIMIT')
];

$baseRouteParameters = [
    'prefix' => 'v2',
    'namespace' => 'V2',
    'middleware' => $middleware
];

/** @var \Laravel\Lumen\Routing\Router $router */
$router->group($baseRouteParameters, function () use ($router) {
    /** Availability Zones */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('availability-zones', 'AvailabilityZonesController@index');
        $router->get('availability-zones/{zoneId}', 'AvailabilityZonesController@show');
        $router->post('availability-zones', 'AvailabilityZonesController@create');
        $router->patch('availability-zones/{zoneId}', 'AvailabilityZonesController@update');
        $router->delete('availability-zones/{zoneId}', 'AvailabilityZonesController@destroy');
    });

    /** Virtual Data Centres */
    $router->group([], function () use ($router) {
        $router->get('vdcs', 'VirtualDataCentresController@index');
        $router->get('vdcs/{vdcUuid}', 'VirtualDataCentresController@show');
        $router->post('vdcs', 'VirtualDataCentresController@create');
        $router->patch('vdcs/{vdcUuid}', 'VirtualDataCentresController@update');
        $router->delete('vdcs/{vdcUuid}', 'VirtualDataCentresController@destroy');
    });

    /** Routers */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('routers', 'RoutersController@index');
        $router->get('routers/{routerUuid}', 'RoutersController@show');
        $router->post('routers', 'RoutersController@create');
        $router->patch('routers/{routerUuid}', 'RoutersController@update');
        $router->delete('routers/{routerUuid}', 'RoutersController@destroy');
    });
});
