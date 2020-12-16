<?php

/**
 * v2 Routes
 */

$middleware = [
    'auth',
    'paginator-limit:' . env('PAGINATION_LIMIT')
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
    $router->get('availability-zones/{zoneId}/prices', 'AvailabilityZoneController@prices');

    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->post('availability-zones', 'AvailabilityZoneController@create');
        $router->patch('availability-zones/{zoneId}', 'AvailabilityZoneController@update');
        $router->delete('availability-zones/{zoneId}', 'AvailabilityZoneController@destroy');
        $router->get('availability-zones/{zoneId}/routers', 'AvailabilityZoneController@routers');
        $router->get('availability-zones/{zoneId}/dhcps', 'AvailabilityZoneController@dhcps');
        $router->get('availability-zones/{zoneId}/credentials', 'AvailabilityZoneController@credentials');
        $router->get('availability-zones/{zoneId}/instances', 'AvailabilityZoneController@instances');
        $router->get('availability-zones/{zoneId}/lbcs', 'AvailabilityZoneController@lbcs');
        $router->get('availability-zones/{zoneId}/capacities', 'AvailabilityZoneController@capacities');
    });

    /** Availability Zone Capacities */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@index');
        $router->get('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@show');
        $router->post('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@create');
        $router->patch('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@update');
        $router->delete('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@destroy');
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

        $router->get('vpcs/{vpcId}/volumes', 'VpcController@volumes');
        $router->get('vpcs/{vpcId}/instances', 'VpcController@instances');
        $router->group(['middleware' => 'is-administrator'], function () use ($router) {
            $router->get('vpcs/{vpcId}/lbcs', 'VpcController@lbcs');
        });
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
        $router->get('networks/{networkId}/nics', 'NetworkController@nics');
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
    $router->group([], function () use ($router) {
        $router->get('routers', 'RouterController@index');
        $router->get('routers/{routerId}', 'RouterController@show');
        $router->get('routers/{routerId}/networks', 'RouterController@networks');
        $router->get('routers/{routerId}/vpns', 'RouterController@vpns');
        $router->get('routers/{routerId}/firewall-rules', 'RouterController@firewallRules');
        $router->post('routers', 'RouterController@create');
        $router->patch('routers/{routerId}', 'RouterController@update');
        $router->delete('routers/{routerId}', 'RouterController@destroy');
        $router->post('routers/{routerId}/configure-default-policies', 'RouterController@configureDefaultPolicies');
    });

    /** Instances */
    $router->group([], function () use ($router) {
        $router->get('instances', 'InstanceController@index');
        $router->get('instances/{instanceId}', 'InstanceController@show');
        $router->get('instances/{instanceId}/credentials', 'InstanceController@credentials');
        $router->get('instances/{instanceId}/volumes', 'InstanceController@volumes');
        $router->get('instances/{instanceId}/nics', 'InstanceController@nics');
        $router->post('instances', 'InstanceController@store');
        $router->put('instances/{instanceId}/lock', 'InstanceController@lock');
        $router->put('instances/{instanceId}/unlock', 'InstanceController@unlock');

        $router->group(['middleware' => 'is-locked'], function () use ($router) {
            $router->patch('instances/{instanceId}', 'InstanceController@update');
            $router->delete('instances/{instanceId}', 'InstanceController@destroy');
            $router->put('instances/{instanceId}/power-on', 'InstanceController@powerOn');
            $router->put('instances/{instanceId}/power-off', 'InstanceController@powerOff');
            $router->put('instances/{instanceId}/power-reset', 'InstanceController@powerReset');
            $router->put('instances/{instanceId}/power-restart', 'InstanceController@guestRestart');
            $router->put('instances/{instanceId}/power-shutdown', 'InstanceController@guestShutdown');
        });
    });

    /** Floating Ips */
    $router->group([], function () use ($router) {
        $router->get('floating-ips', 'FloatingIpController@index');
        $router->get('floating-ips/{fipId}', 'FloatingIpController@show');
        $router->post('floating-ips', 'FloatingIpController@store');
        $router->post('floating-ips/{fipId}/assign', 'FloatingIpController@assign');
        $router->post('floating-ips/{fipId}/unassign', 'FloatingIpController@unassign');
        $router->patch('floating-ips/{fipId}', 'FloatingIpController@update');
        $router->delete('floating-ips/{fipId}', 'FloatingIpController@destroy');
    });

    /** Firewall Policy */
    $router->group([], function () use ($router) {
        $router->get('firewall-policies', 'FirewallPolicyController@index');
        $router->get('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@show');
        $router->post('firewall-policies', 'FirewallPolicyController@store');
        $router->patch('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@update');
        $router->delete('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@destroy');
    });

    /** Firewall Rules */
    $router->group([], function () use ($router) {
        $router->get('firewall-rules', 'FirewallRuleController@index');
        $router->get('firewall-rules/{firewallRuleId}', 'FirewallRuleController@show');
        $router->get('firewall-rules/{firewallRuleId}/ports', 'FirewallRuleController@ports');
        $router->post('firewall-rules', 'FirewallRuleController@store');
        $router->patch('firewall-rules/{firewallRuleId}', 'FirewallRuleController@update');
        $router->delete('firewall-rules/{firewallRuleId}', 'FirewallRuleController@destroy');
    });

    /** Firewall Rule Ports */
    $router->group([], function () use ($router) {
        $router->get('firewall-rule-ports', 'FirewallRulePortController@index');
        $router->get('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@show');
        $router->post('firewall-rule-ports', 'FirewallRulePortController@store');
        $router->patch('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@update');
        $router->delete('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@destroy');
    });

    /** Regions */
    $router->group([], function () use ($router) {
        $router->get('regions', 'RegionController@index');
        $router->get('regions/{regionId}', 'RegionController@show');
        $router->get('regions/{regionId}/availability-zones', 'RegionController@availabilityZones');
        $router->get('regions/{regionId}/vpcs', 'RegionController@vpcs');
        $router->get('regions/{regionId}/prices', 'RegionController@prices');

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
        $router->get('volumes/{volumeId}/instances', 'VolumeController@instances');
        //$router->post('volumes', 'VolumeController@store');
        $router->patch('volumes/{volumeId}', 'VolumeController@update');
        $router->delete('volumes/{volumeId}', 'VolumeController@destroy');
    });

    /** Nics */
    $router->group([], function () use ($router) {
        $router->get('nics', 'NicController@index');
        $router->get('nics/{nicId}', 'NicController@show');
        $router->group(['middleware' => 'is-administrator'], function () use ($router) {
            //$router->post('nics', 'NicController@create');
            $router->patch('nics/{nicId}', 'NicController@update');
            $router->delete('nics/{nicId}', 'NicController@destroy');
        });
    });

    /** Credentials */
    $router->group(['middleware' => 'is-administrator'], function () use ($router) {
        $router->get('credentials', 'CredentialsController@index');
        $router->get('credentials/{credentialsId}', 'CredentialsController@show');
        $router->post('credentials', 'CredentialsController@store');
        $router->patch('credentials/{credentialsId}', 'CredentialsController@update');
        $router->delete('credentials/{credentialsId}', 'CredentialsController@destroy');
    });

    /** Support */
    $router->group([], function () use ($router) {
        $router->get('support', 'VpcSupportController@index');
        $router->get('support/{vpcSupportId}', 'VpcSupportController@show');
        $router->group(['middleware' => 'can-enable-support'], function () use ($router) {
            $router->post('support', 'VpcSupportController@create');
            $router->patch('support/{vpcSupportId}', 'VpcSupportController@update');
        });
        $router->delete('support/{vpcSupportId}', 'VpcSupportController@destroy');
    });

    /** Discount Plans */
    $router->group([], function () use ($router) {
        $router->get('discount-plans', 'DiscountPlanController@index');
        $router->get('discount-plans/{discountPlanId}', 'DiscountPlanController@show');
        $router->group(['middleware' => 'is-administrator'], function () use ($router) {
            $router->post('discount-plans', 'DiscountPlanController@store');
            $router->patch('discount-plans/{discountPlanId}', 'DiscountPlanController@update');
            $router->delete('discount-plans/{discountPlanId}', 'DiscountPlanController@destroy');
        });
    });
    
    /** Billing Metrics */
    $router->group([], function () use ($router) {
        $router->get('billing-metrics', 'BillingMetricController@index');
        $router->get('billing-metrics/{billingMetricControllerId}', 'BillingMetricController@show');
        $router->group(['middleware' => 'is-administrator'], function () use ($router) {
            $router->post('billing-metrics', 'BillingMetricController@create');
            $router->patch('billing-metrics/{billingMetricControllerId}', 'BillingMetricController@update');
            $router->delete('billing-metrics/{billingMetricControllerId}', 'BillingMetricController@destroy');
        });
    });
});
