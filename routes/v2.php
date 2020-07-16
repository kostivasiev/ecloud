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
        $router->get('availability-zones', 'AvailabilityZoneController@index');
        $router->get('availability-zones/{zoneId}', 'AvailabilityZoneController@show');
        $router->post('availability-zones', 'AvailabilityZoneController@create');
        $router->patch('availability-zones/{zoneId}', 'AvailabilityZoneController@update');
        $router->delete('availability-zones/{zoneId}', 'AvailabilityZoneController@destroy');

        /** Availability Zones Routers */
        $router->group([], function () use ($router) {
            $router->put(
                'availability-zones/{zoneId}/routers/{routerUuid}',
                'AvailabilityZoneController@routersCreate'
            );
            $router->delete(
                'availability-zones/{zoneId}/routers/{routerUuid}',
                'AvailabilityZoneController@routersDestroy'
            );
        });
    });

    /** Virtual Data Centres */
    $router->group([], function () use ($router) {
        $router->get('vpcs', 'VpcController@index');
        $router->get('vpcs/{vdcUuid}', 'VpcController@show');
        $router->post('vpcs', 'VpcController@create');
        $router->patch('vpcs/{vdcUuid}', 'VpcController@update');
        $router->delete('vpcs/{vdcUuid}', 'VpcController@destroy');
    });

    /** Dhcps */
    $router->group([], function () use ($router) {
        $router->get('dhcps', 'DhcpController@index');
        $router->get('dhcps/{dhcpId}', 'DhcpController@show');
        $router->post('dhcps', 'DhcpController@create');
        $router->patch('dhcps/{dhcpId}', 'DhcpController@update');
        $router->delete('dhcps/{dhcpId}', 'DhcpController@destroy');
    });

    /** Networks */
    $router->group([], function () use ($router) {
        $router->get('networks', 'NetworkController@index');
        $router->get('networks/{networkId}', 'NetworkController@show');
        $router->post('networks', 'NetworkController@create');
        $router->patch('networks/{networkId}', 'NetworkController@update');
        $router->delete('networks/{networkId}', 'NetworkController@destroy');
    });

    /** Vpns */
    $router->group([], function () use ($router) {
        $router->get('vpns', 'VpnController@index');
        $router->get('vpns/{vpnId}', 'VpnController@show');
        $router->post('vpns', 'VpnController@create');
        $router->patch('vpns/{vpnId}', 'VpnController@update');
        $router->delete('vpns/{vpnId}', 'VpnController@destroy');
    });

    /** Routers */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('routers', 'RouterController@index');
        $router->get('routers/{routerUuid}', 'RouterController@show');
        $router->post('routers', 'RouterController@create');
        $router->patch('routers/{routerUuid}', 'RouterController@update');
        $router->delete('routers/{routerUuid}', 'RouterController@destroy');

        /** Routers Gateways */
        $router->group([], function () use ($router) {
            $router->put(
                'routers/{routerUuid}/gateways/{gatewaysUuid}',
                'RouterController@gatewaysCreate'
            );
            $router->delete(
                'routers/{routerUuid}/gateways/{gatewaysUuid}',
                'RouterController@gatewaysDestroy'
            );
        });
    });

    /** Gateways */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('gateways', 'GatewayController@index');
        $router->get('gateways/{gatewayUuid}', 'GatewayController@show');
        $router->post('gateways', 'GatewayController@create');
        $router->patch('gateways/{gatewayUuid}', 'GatewayController@update');
        $router->delete('gateways/{gatewayUuid}', 'GatewayController@destroy');
    });
});
