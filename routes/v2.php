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
    $router->get('availability-zones', 'AvailabilityZoneController@index');
    $router->get('availability-zones/{zoneId}', 'AvailabilityZoneController@show');

    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->post('availability-zones', 'AvailabilityZoneController@create');
        $router->patch('availability-zones/{zoneId}', 'AvailabilityZoneController@update');
        $router->delete('availability-zones/{zoneId}', 'AvailabilityZoneController@destroy');
    });

    /** Virtual Private Clouds */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => 'has-reseller-id'], function () use ($router) {
            $router->post('vpcs', 'VpcController@create');
            $router->post('vpcs/{vpcId}/deploy-defaults', 'VpcController@deployDefaults');
        });
        $router->patch('vpcs/{vpcId}', 'VpcController@update');
        $router->get('vpcs', 'VpcController@index');
        $router->get('vpcs/{vpcId}', 'VpcController@show');
        $router->delete('vpcs/{vpcId}', 'VpcController@destroy');
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
    $router->get('routers', 'RouterController@index');
    $router->get('routers/{routerId}', 'RouterController@show');
    $router->post('routers', 'RouterController@create');
    $router->patch('routers/{routerId}', 'RouterController@update');
    $router->delete('routers/{routerId}', 'RouterController@destroy');

    /** Instances */
    $router->group([], function () use ($router) {
        $router->get('instances', 'InstanceController@index');
        $router->get('instances/{instanceId}', 'InstanceController@show');
        $router->post('instances', 'InstanceController@store');
        $router->patch('instances/{instanceId}', 'InstanceController@update');
        $router->delete('instances/{instanceId}', 'InstanceController@destroy');
    });

    /** Floating Ips */
    $router->group([], function () use ($router) {
        $router->get('floating-ips', 'FloatingIpController@index');
        $router->get('floating-ips/{fipId}', 'FloatingIpController@show');
        $router->post('floating-ips', 'FloatingIpController@store');
        $router->patch('floating-ips/{fipId}', 'FloatingIpController@update');
        $router->delete('floating-ips/{fipId}', 'FloatingIpController@destroy');
    });

    /** Firewall Rules */
    $router->group([], function () use ($router) {
        $router->get('firewall-rules', 'FirewallRuleController@index');
        $router->get('firewall-rules/{firewallRuleId}', 'FirewallRuleController@show');
        $router->post('firewall-rules', 'FirewallRuleController@store');
        $router->patch('firewall-rules/{firewallRuleId}', 'FirewallRuleController@update');
        $router->delete('firewall-rules/{firewallRuleId}', 'FirewallRuleController@destroy');
    });

    /** Regions */
    $router->group([], function () use ($router) {
        $router->get('regions', 'RegionController@index');
        $router->get('regions/{regionId}', 'RegionController@show');
        $router->get('regions/{regionId}/availability-zones', 'RegionController@availabilityZones');

        $router->group(['middleware' => 'is-administrator'], function () use ($router) {
            $router->post('regions', 'RegionController@create');
            $router->patch('regions/{regionId}', 'RegionController@update');
            $router->delete('regions/{regionId}', 'RegionController@destroy');
        });
    });

    /** Load balancer clusters */
    $router->group([], function () use ($router) {
        $router->get('lbcs', 'LoadBalancerClusterController@index');
        $router->get('lbcs/{lbcId}', 'LoadBalancerClusterController@show');
        $router->post('lbcs', 'LoadBalancerClusterController@store');
        $router->patch('lbcs/{lbcId}', 'LoadBalancerClusterController@update');
        $router->delete('lbcs/{lbcId}', 'LoadBalancerClusterController@destroy');
    });

    /** Volumes */
    $router->group([], function () use ($router) {
        $router->get('volumes', 'VolumeController@index');
        $router->get('volumes/{volumeId}', 'VolumeController@show');
        $router->post('volumes', 'VolumeController@store');
        $router->patch('volumes/{volumeId}', 'VolumeController@update');
        $router->delete('volumes/{volumeId}', 'VolumeController@destroy');
    });
    
    /** Nics */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('nics', 'NicController@index');
        $router->get('nics/{nicId}', 'NicController@show');
        $router->post('nics', 'NicController@create');
        $router->patch('nics/{nicId}', 'NicController@update');
        $router->delete('nics/{nicId}', 'NicController@destroy');
    });
});


