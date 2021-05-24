<?php

/**
 * v2 Routes
 */

use Laravel\Lumen\Routing\Router;

$middleware = [
    'auth',
    'paginator-limit:' . env('PAGINATION_LIMIT')
];

$baseRouteParameters = [
    'prefix' => 'v2',
    'namespace' => 'V2',
    'middleware' => $middleware
];

/** @var Router $router */
$router->group($baseRouteParameters, function () use ($router) {
    /** Availability Zones */
    $router->get('availability-zones', 'AvailabilityZoneController@index');
    $router->get('availability-zones/{zoneId}', 'AvailabilityZoneController@show');
    $router->get('availability-zones/{zoneId}/prices', 'AvailabilityZoneController@prices');
    $router->get('availability-zones/{zoneId}/router-throughputs', 'AvailabilityZoneController@routerThroughputs');
    $router->get('availability-zones/{zoneId}/host-specs', 'AvailabilityZoneController@hostSpecs');

    $router->group(['middleware' => 'is-admin'], function () use ($router) {
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
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->get('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@index');
        $router->get('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@show');
        $router->post('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@create');
        $router->patch('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@update');
        $router->delete('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@destroy');
    });

    /** Virtual Private Clouds */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => 'has-reseller-id'], function () use ($router) {
            $router->group(['middleware' => 'customer-max-vpc'], function () use ($router) {
                $router->post('vpcs', 'VpcController@create');
            });
            $router->post('vpcs/{vpcId}/deploy-defaults', 'VpcController@deployDefaults');
        });
        $router->patch('vpcs/{vpcId}', 'VpcController@update');
        $router->get('vpcs', 'VpcController@index');
        $router->get('vpcs/{vpcId}', 'VpcController@show');
        $router->delete('vpcs/{vpcId}', 'VpcController@destroy');

        $router->get('vpcs/{vpcId}/volumes', 'VpcController@volumes');
        $router->get('vpcs/{vpcId}/instances', 'VpcController@instances');
        $router->get('vpcs/{vpcId}/tasks', 'VpcController@tasks');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->get('vpcs/{vpcId}/lbcs', 'VpcController@lbcs');
        });
    });

    /** Dhcps */
    $router->group([], function () use ($router) {
        $router->get('dhcps', 'DhcpController@index');
        $router->get('dhcps/{dhcpId}', 'DhcpController@show');
        $router->get('dhcps/{dhcpId}/tasks', 'DhcpController@tasks');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('dhcps', 'DhcpController@create');
            $router->patch('dhcps/{dhcpId}', 'DhcpController@update');
            $router->delete('dhcps/{dhcpId}', 'DhcpController@destroy');
        });
    });

    /** Networks */
    $router->group([], function () use ($router) {
        $router->get('networks', 'NetworkController@index');
        $router->get('networks/{networkId}', 'NetworkController@show');
        $router->get('networks/{networkId}/nics', 'NetworkController@nics');
        $router->get('networks/{networkId}/tasks', 'NetworkController@tasks');
        $router->post('networks', 'NetworkController@create');
        $router->patch('networks/{networkId}', 'NetworkController@update');
        $router->delete('networks/{networkId}', 'NetworkController@destroy');
    });

    /** Network Policy */
    $router->group([], function () use ($router) {
        $router->get('network-policies', 'NetworkPolicyController@index');
        $router->get('network-policies/{networkPolicyId}', 'NetworkPolicyController@show');
        $router->get('network-policies/{networkPolicyId}/network-rules', 'NetworkPolicyController@networkRules');
        $router->get('network-policies/{networkPolicyId}/tasks', 'NetworkPolicyController@tasks');
        $router->post('network-policies', 'NetworkPolicyController@store');
        $router->patch('network-policies/{networkPolicyId}', 'NetworkPolicyController@update');
        $router->delete('network-policies/{networkPolicyId}', 'NetworkPolicyController@destroy');
    });

    /** Network Rules */
    $router->group([], function () use ($router) {
        $router->get('network-rules', 'NetworkRuleController@index');
        $router->get('network-rules/{networkRuleId}', 'NetworkRuleController@show');
        $router->post('network-rules', 'NetworkRuleController@store');
        $router->group(['middleware' => 'can-edit-rule'], function () use ($router) {
            $router->patch('network-rules/{networkRuleId}', 'NetworkRuleController@update');
        });
        $router->group(['middleware' => 'can-delete-rule'], function () use ($router) {
            $router->delete('network-rules/{networkRuleId}', 'NetworkRuleController@destroy');
        });
    });

    /** Network Rule Ports */
    $router->group([], function () use ($router) {
        $router->get('network-rule-ports', 'NetworkRulePortController@index');
        $router->get('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@show');
        $router->post('network-rule-ports', 'NetworkRulePortController@store');
        $router->patch('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@update');
        $router->delete('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@destroy');
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
        $router->get('routers/{routerId}/firewall-policies', 'RouterController@firewallPolicies');
        $router->get('routers/{routerId}/tasks', 'RouterController@tasks');
        $router->post('routers', 'RouterController@create');
        $router->patch('routers/{routerId}', 'RouterController@update');
        $router->delete('routers/{routerId}', 'RouterController@destroy');
        $router->post('routers/{routerId}/configure-default-policies', 'RouterController@configureDefaultPolicies');
    });

    /** Instances */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => 'customer-max-instance'], function () use ($router) {
            $router->post('instances', 'InstanceController@store');
        });
        $router->get('instances', 'InstanceController@index');
        $router->get('instances/{instanceId}', 'InstanceController@show');
        $router->get('instances/{instanceId}/credentials', 'InstanceController@credentials');
        $router->get('instances/{instanceId}/volumes', 'InstanceController@volumes');
        $router->get('instances/{instanceId}/nics', 'InstanceController@nics');
        $router->get('instances/{instanceId}/tasks', 'InstanceController@tasks');
        $router->put('instances/{instanceId}/lock', 'InstanceController@lock');
        $router->put('instances/{instanceId}/unlock', 'InstanceController@unlock');
        $router->post('instances/{instanceId}/console-session', 'InstanceController@consoleSession');

        $router->group(['middleware' => 'is-locked'], function () use ($router) {
            $router->patch('instances/{instanceId}', 'InstanceController@update');
            $router->delete('instances/{instanceId}', 'InstanceController@destroy');
            $router->put('instances/{instanceId}/power-on', 'InstanceController@powerOn');
            $router->put('instances/{instanceId}/power-off', 'InstanceController@powerOff');
            $router->put('instances/{instanceId}/power-reset', 'InstanceController@powerReset');
            $router->put('instances/{instanceId}/power-restart', 'InstanceController@guestRestart');
            $router->put('instances/{instanceId}/power-shutdown', 'InstanceController@guestShutdown');
            $router->group(['middleware' => 'can-attach-instance-volume'], function () use ($router) {
                $router->post('instances/{instanceId}/volume-attach', 'InstanceController@volumeAttach');
            });
            $router->post('instances/{instanceId}/volume-detach', 'InstanceController@volumeDetach');
        });
    });

    /** Floating Ips */
    $router->group([], function () use ($router) {
        $router->get('floating-ips', 'FloatingIpController@index');
        $router->get('floating-ips/{fipId}', 'FloatingIpController@show');
        $router->get('floating-ips/{fipId}/tasks', 'FloatingIpController@tasks');
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
        $router->get('firewall-policies/{firewallPolicyId}/firewall-rules', 'FirewallPolicyController@firewallRules');
        $router->get('firewall-policies/{firewallPolicyId}/tasks', 'FirewallPolicyController@tasks');
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

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
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
        $router->group(['middleware' => 'can-detach'], function () use ($router) {
            $router->post('volumes/{volumeId}/detach', 'VolumeController@detach');
        });
        $router->get('volumes', 'VolumeController@index');
        $router->get('volumes/{volumeId}', 'VolumeController@show');
        $router->get('volumes/{volumeId}/instances', 'VolumeController@instances');
        $router->get('volumes/{volumeId}/tasks', 'VolumeController@tasks');
        $router->post('volumes', 'VolumeController@store');
        $router->patch('volumes/{volumeId}', 'VolumeController@update');
        $router->delete('volumes/{volumeId}', 'VolumeController@destroy');
        $router->post('volumes/{volumeId}/attach', 'VolumeController@attach');
    });

    /** Nics */
    $router->group([], function () use ($router) {
        $router->get('nics', 'NicController@index');
        $router->get('nics/{nicId}', 'NicController@show');
        $router->get('nics/{nicId}/tasks', 'NicController@tasks');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            //$router->post('nics', 'NicController@create');
            $router->patch('nics/{nicId}', 'NicController@update');
            $router->delete('nics/{nicId}', 'NicController@destroy');
        });
    });

    /** Credentials */
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
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
        $router->post('discount-plans', 'DiscountPlanController@store');

        $router->group(['middleware' => 'is-pending'], function () use ($router) {
            $router->post('discount-plans/{discountPlanId}/approve', 'DiscountPlanController@approve');
            $router->post('discount-plans/{discountPlanId}/reject', 'DiscountPlanController@reject');
        });

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->patch('discount-plans/{discountPlanId}', 'DiscountPlanController@update');
            $router->delete('discount-plans/{discountPlanId}', 'DiscountPlanController@destroy');
        });
    });
    
    /** Billing Metrics */
    $router->group([], function () use ($router) {
        $router->get('billing-metrics', 'BillingMetricController@index');
        $router->get('billing-metrics/{billingMetricId}', 'BillingMetricController@show');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('billing-metrics', 'BillingMetricController@create');
            $router->patch('billing-metrics/{billingMetricId}', 'BillingMetricController@update');
            $router->delete('billing-metrics/{billingMetricId}', 'BillingMetricController@destroy');
        });
    });

    /** Router Throughput */
    $router->group([], function () use ($router) {
        $router->get('router-throughputs', 'RouterThroughputController@index');
        $router->get('router-throughputs/{routerThroughputId}', 'RouterThroughputController@show');

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('router-throughputs', 'RouterThroughputController@store');
            $router->patch('router-throughputs/{routerThroughputId}', 'RouterThroughputController@update');
            $router->delete('router-throughputs/{routerThroughputId}', 'RouterThroughputController@destroy');
        });
    });

    /** Host */
    $router->group([], function () use ($router) {
        $router->get('hosts', 'HostController@index');
        $router->get('hosts/{id}', 'HostController@show');
        $router->get('hosts/{id}/tasks', 'HostController@tasks');
        $router->post('hosts', 'HostController@store');
        $router->patch('hosts/{id}', 'HostController@update');
        $router->delete('hosts/{id}', 'HostController@destroy');
    });

    /** Host Spec */
    $router->group([], function () use ($router) {
        $router->get('host-specs', 'HostSpecController@index');
        $router->get('host-specs/{hostSpecId}', 'HostSpecController@show');

        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('host-specs', 'HostSpecController@store');
            $router->patch('host-specs/{hostSpecId}', 'HostSpecController@update');
            $router->delete('host-specs/{hostSpecId}', 'HostSpecController@destroy');
        });
    });

    /** Host Group */
    $router->group([], function () use ($router) {
        $router->get('host-groups', 'HostGroupController@index');
        $router->get('host-groups/{id}', 'HostGroupController@show');
        $router->get('host-groups/{id}/tasks', 'HostGroupController@tasks');
        $router->post('host-groups', 'HostGroupController@store');
        $router->patch('host-groups/{id}', 'HostGroupController@update');
        $router->delete('host-groups/{id}', 'HostGroupController@destroy');
    });

    /** Image */
    $router->group([], function () use ($router) {
        $router->get('images', 'ImageController@index');
        $router->get('images/{imageId}', 'ImageController@show');
        $router->get('images/{imageId}/parameters', 'ImageController@parameters');
        $router->get('images/{imageId}/metadata', 'ImageController@metadata');
        $router->group(['middleware' => 'is-admin'], function () use ($router) {
            $router->post('images', 'ImageController@store');
            $router->delete('images/{imageId}', 'ImageController@destroy');
        });
    });

    /** SSH Key Pairs */
    $router->group([], function () use ($router) {
        $router->group(['middleware' => ['has-reseller-id', 'customer-max-ssh-key-pairs']], function () use ($router) {
            $router->post('ssh-key-pairs', 'SshKeyPairController@create');
        });
        $router->patch('ssh-key-pairs/{keypairId}', 'SshKeyPairController@update');
        $router->get('ssh-key-pairs', 'SshKeyPairController@index');
        $router->get('ssh-key-pairs/{keypairId}', 'SshKeyPairController@show');
        $router->delete('ssh-key-pairs/{keypairId}', 'SshKeyPairController@destroy');
    });

    /** Task */
    $router->group(['middleware' => 'is-admin'], function () use ($router) {
        $router->get('tasks', 'TaskController@index');
        $router->get('tasks/{taskId}', 'TaskController@show');
    });
});
