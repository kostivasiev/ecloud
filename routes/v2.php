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

        /** Availability Zones Routers */
        $router->group([], function () use ($router) {
            $router->put(
                'availability-zones/{zoneId}/routers/{routerUuid}',
                'AvailabilityZonesController@routersCreate'
            );
            $router->delete(
                'availability-zones/{zoneId}/routers/{routerUuid}',
                'AvailabilityZonesController@routersDestroy'
            );
        });
    });

    /** Virtual Data Centres */
    $router->group([], function () use ($router) {
        $router->get('vpcs', 'VirtualPrivateCloudsController@index');
        $router->get('vpcs/{vdcUuid}', 'VirtualPrivateCloudsController@show');
        $router->post('vpcs', 'VirtualPrivateCloudsController@create');
        $router->patch('vpcs/{vdcUuid}', 'VirtualPrivateCloudsController@update');
        $router->delete('vpcs/{vdcUuid}', 'VirtualPrivateCloudsController@destroy');
    });

    /** Routers */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('routers', 'RoutersController@index');
        $router->get('routers/{routerUuid}', 'RoutersController@show');
        $router->post('routers', 'RoutersController@create');
        $router->patch('routers/{routerUuid}', 'RoutersController@update');
        $router->delete('routers/{routerUuid}', 'RoutersController@destroy');

        /** Routers Gateways */
        $router->group([], function () use ($router) {
            $router->put(
                'routers/{routerUuid}/gateways/{gatewaysUuid}',
                'RoutersController@gatewaysCreate'
            );
            $router->delete(
                'routers/{routerUuid}/gateways/{gatewaysUuid}',
                'RoutersController@gatewaysDestroy'
            );
        });
    });

    /** Gateways */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('gateways', 'GatewaysController@index');
        $router->get('gateways/{gatewayUuid}', 'GatewaysController@show');
        $router->post('gateways', 'GatewaysController@create');
        $router->patch('gateways/{gatewayUuid}', 'GatewaysController@update');
        $router->delete('gateways/{gatewayUuid}', 'GatewaysController@destroy');
    });
});
