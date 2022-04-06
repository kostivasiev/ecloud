<?php
/**
 * v2 Routes
 */

Route::group([
    'prefix' => 'v2',
    'namespace' => 'App\Http\Controllers\V2',
    'middleware' => [
        'auth',
        'paginator-limit:' . env('PAGINATION_LIMIT')
    ]
], function () {
    /** Availability Zones */
    Route::get('availability-zones', 'AvailabilityZoneController@index');
    Route::get('availability-zones/{zoneId}', 'AvailabilityZoneController@show');
    Route::get('availability-zones/{zoneId}/prices', 'AvailabilityZoneController@prices');
    Route::get('availability-zones/{zoneId}/router-throughputs', 'AvailabilityZoneController@routerThroughputs');
    Route::get('availability-zones/{zoneId}/host-specs', 'AvailabilityZoneController@hostSpecs');
    Route::get('availability-zones/{zoneId}/images', 'AvailabilityZoneController@images');


    Route::group(['middleware' => 'is-admin'], function () {
        Route::post('availability-zones', 'AvailabilityZoneController@create');
        Route::patch('availability-zones/{zoneId}', 'AvailabilityZoneController@update');
        Route::delete('availability-zones/{zoneId}', [
            'middleware' => 'can-be-deleted:' . \App\Models\V2\AvailabilityZone::class   . ',zoneId',
            'uses' => 'AvailabilityZoneController@destroy'
        ]);
        Route::get('availability-zones/{zoneId}/routers', 'AvailabilityZoneController@routers');
        Route::get('availability-zones/{zoneId}/dhcps', 'AvailabilityZoneController@dhcps');
        Route::get('availability-zones/{zoneId}/credentials', 'AvailabilityZoneController@credentials');
        Route::get('availability-zones/{zoneId}/instances', 'AvailabilityZoneController@instances');
        Route::get('availability-zones/{zoneId}/load-balancers', 'AvailabilityZoneController@loadBalancers');
        Route::get('availability-zones/{zoneId}/capacities', 'AvailabilityZoneController@capacities');
    });

    /** Availability Zone Capacities */
    Route::group(['middleware' => 'is-admin'], function () {
        Route::get('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@index');
        Route::get('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@show');
        Route::post('availability-zone-capacities', 'AvailabilityZoneCapacitiesController@create');
        Route::patch('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@update');
        Route::delete('availability-zone-capacities/{capacityId}', 'AvailabilityZoneCapacitiesController@destroy');
    });

    /** Virtual Private Clouds */
    Route::group([], function () {
        Route::group(['middleware' => 'has-reseller-id'], function () {
            Route::group(['middleware' => ['customer-max-vpc', 'can-enable-support']], function () {
                Route::post('vpcs', 'VpcController@create');
            });
            Route::post('vpcs/{vpcId}/deploy-defaults', 'VpcController@deployDefaults');
        });
        Route::group(['middleware' => 'can-enable-support'], function () {
            Route::patch('vpcs/{vpcId}', 'VpcController@update');
        });
        Route::get('vpcs', 'VpcController@index');
        Route::get('vpcs/{vpcId}', 'VpcController@show');

        Route::group(['middleware' => 'vpc-can-delete'], function () {
            Route::delete('vpcs/{vpcId}', 'VpcController@destroy');
        });

        Route::get('vpcs/{vpcId}/volumes', 'VpcController@volumes');
        Route::get('vpcs/{vpcId}/instances', 'VpcController@instances');
        Route::get('vpcs/{vpcId}/tasks', 'VpcController@tasks');
        Route::group(['middleware' => 'is-admin'], function () {
            Route::get('vpcs/{vpcId}/load-balancers', 'VpcController@loadBalancers');
        });
    });

    /** Dhcps */
    Route::group([], function () {
        Route::get('dhcps', 'DhcpController@index');
        Route::get('dhcps/{dhcpId}', 'DhcpController@show');
        Route::get('dhcps/{dhcpId}/tasks', 'DhcpController@tasks');
        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('dhcps', 'DhcpController@create');
            Route::patch('dhcps/{dhcpId}', 'DhcpController@update');
            Route::delete('dhcps/{dhcpId}', 'DhcpController@destroy');
        });
    });

    /** Networks */
    Route::group([], function () {
        Route::get('networks', 'NetworkController@index');
        Route::get('networks/{networkId}', 'NetworkController@show');
        Route::get('networks/{networkId}/nics', 'NetworkController@nics');
        Route::get('networks/{networkId}/tasks', 'NetworkController@tasks');
        Route::post('networks', 'NetworkController@create');
        Route::patch('networks/{networkId}', 'NetworkController@update');
        Route::delete('networks/{networkId}', 'NetworkController@destroy');
        Route::delete('networks/{networkId}', [
            'middleware' => 'can-be-deleted:' . \App\Models\V2\Network::class   . ',networkId',
            'uses' => 'NetworkController@destroy'
        ]);
    });

    /** Network Policy */
    Route::group([], function () {
        Route::get('network-policies', 'NetworkPolicyController@index');
        Route::get('network-policies/{networkPolicyId}', 'NetworkPolicyController@show');
        Route::get('network-policies/{networkPolicyId}/network-rules', 'NetworkPolicyController@networkRules');
        Route::get('network-policies/{networkPolicyId}/tasks', 'NetworkPolicyController@tasks');
        Route::post('network-policies', 'NetworkPolicyController@store');
        Route::patch('network-policies/{networkPolicyId}', 'NetworkPolicyController@update');
        Route::delete('network-policies/{networkPolicyId}', 'NetworkPolicyController@destroy');
    });

    /** Network Rules */
    Route::group([], function () {
        Route::get('network-rules', 'NetworkRuleController@index');
        Route::get('network-rules/{networkRuleId}', 'NetworkRuleController@show');
        Route::post('network-rules', 'NetworkRuleController@store');
        Route::group(['middleware' => 'network-rule-can-edit'], function () {
            Route::patch('network-rules/{networkRuleId}', 'NetworkRuleController@update');
        });
        Route::group(['middleware' => 'network-rule-can-delete'], function () {
            Route::delete('network-rules/{networkRuleId}', 'NetworkRuleController@destroy');
        });
    });

    /** Network Rule Ports */
    Route::group([], function () {
        Route::get('network-rule-ports', 'NetworkRulePortController@index');
        Route::get('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@show');
        Route::post('network-rule-ports', 'NetworkRulePortController@store');
        Route::group(['middleware' => 'network-rule-port-can-edit'], function () {
            Route::patch('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@update');
        });
        Route::group(['middleware' => 'network-rule-port-can-delete'], function () {
            Route::delete('network-rule-ports/{networkRulePortId}', 'NetworkRulePortController@destroy');
        });
    });

    /** Vpn Services */
    Route::group([], function () {
        Route::get('vpn-services', 'VpnServiceController@index');
        Route::get('vpn-services/{vpnServiceId}', 'VpnServiceController@show');
        Route::get('vpn-services/{vpnServiceId}/endpoints', 'VpnServiceController@endpoints');
        Route::post('vpn-services', 'VpnServiceController@create');
        Route::patch('vpn-services/{vpnServiceId}', 'VpnServiceController@update');
        Route::delete('vpn-services/{vpnServiceId}', [
            'middleware' => 'can-be-deleted:' . \App\Models\V2\VpnService::class   . ',vpnServiceId',
            'uses' => 'VpnServiceController@destroy'
        ]);
    });

    /** VPN Endpoints */
    Route::group([], function () {
        Route::get('vpn-endpoints', 'VpnEndpointController@index');
        Route::get('vpn-endpoints/{vpnEndpointId}', 'VpnEndpointController@show');
        Route::get('vpn-endpoints/{vpnEndpointId}/services', 'VpnEndpointController@services');
        Route::post('vpn-endpoints', 'VpnEndpointController@store');
        Route::patch('vpn-endpoints/{vpnEndpointId}', 'VpnEndpointController@update');
        Route::group(['middleware' => 'vpn-endpoint-can-delete'], function () {
            Route::delete('vpn-endpoints/{vpnEndpointId}', 'VpnEndpointController@destroy');
        });
    });

    /** Vpn Sessions */
    Route::group([], function () {
        Route::get('vpn-sessions', 'VpnSessionController@index');
        Route::get('vpn-sessions/{vpnSessionId}', 'VpnSessionController@show');
        Route::get('vpn-sessions/{vpnSessionId}/pre-shared-key', 'VpnSessionController@preSharedKey');
        Route::post('vpn-sessions', 'VpnSessionController@create');
        Route::patch('vpn-sessions/{vpnSessionId}', 'VpnSessionController@update');
        Route::delete('vpn-sessions/{vpnSessionId}', 'VpnSessionController@destroy');
    });

    /** Vpn Profiles */
    Route::group([], function () {
        Route::get('vpn-profiles', 'VpnProfileController@index');
        Route::get('vpn-profiles/{vpnProfileId}', 'VpnProfileController@show');
        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('vpn-profiles', 'VpnProfileController@create');
            Route::patch('vpn-profiles/{vpnProfileId}', 'VpnProfileController@update');
            Route::delete('vpn-profiles/{vpnProfileId}', 'VpnProfileController@destroy');
        });
    });

    /** Vpn Profile Groups */
    Route::group([], function () {
        Route::get('vpn-profile-groups', 'VpnProfileGroupController@index');
        Route::get('vpn-profile-groups/{vpnProfileGroupId}', 'VpnProfileGroupController@show');
        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('vpn-profile-groups', 'VpnProfileGroupController@create');
            Route::patch('vpn-profile-groups/{vpnProfileGroupId}', 'VpnProfileGroupController@update');
            Route::delete('vpn-profile-groups/{vpnProfileGroupId}', 'VpnProfileGroupController@destroy');
        });
    });

    /** Routers */
    Route::group([], function () {
        Route::get('routers', 'RouterController@index');
        Route::get('routers/{routerId}', 'RouterController@show');
        Route::get('routers/{routerId}/networks', 'RouterController@networks');
        Route::get('routers/{routerId}/vpns', 'RouterController@vpns');
        Route::get('routers/{routerId}/firewall-policies', 'RouterController@firewallPolicies');
        Route::get('routers/{routerId}/tasks', 'RouterController@tasks');
        Route::post('routers', 'RouterController@create');
        Route::patch('routers/{routerId}', 'RouterController@update');
        Route::delete('routers/{routerId}', [
            'middleware' => 'can-be-deleted:' . \App\Models\V2\Router::class   . ',routerId',
            'uses' => 'RouterController@destroy'
        ]);
        Route::post('routers/{routerId}/configure-default-policies', 'RouterController@configureDefaultPolicies');
    });

    /** Instances */
    Route::group([], function () {
        Route::group(['middleware' => ['customer-max-instance', 'instance-requires-floating-ip']], function () {
            Route::post('instances', 'InstanceController@store');
        });
        Route::group(['middleware' => ['instance-console-enabled']], function () {
            Route::get('instances/{instanceId}/console-screenshot', 'InstanceController@consoleScreenshot');
            Route::post('instances/{instanceId}/console-session', 'InstanceController@consoleSession');
        });
        Route::get('instances', 'InstanceController@index');
        Route::get('instances/{instanceId}', 'InstanceController@show');
        Route::get('instances/{instanceId}/credentials', 'InstanceController@credentials');
        Route::get('instances/{instanceId}/volumes', 'InstanceController@volumes');
        Route::get('instances/{instanceId}/nics', 'InstanceController@nics');
        Route::get('instances/{instanceId}/tasks', 'InstanceController@tasks');
        Route::get('instances/{instanceId}/floating-ips', 'InstanceController@floatingIps');
        Route::get('instances/{instanceId}/software', 'InstanceController@software');
        Route::put('instances/{instanceId}/lock', 'InstanceController@lock');
        Route::put('instances/{instanceId}/unlock', 'InstanceController@unlock');
        Route::post('instances/{instanceId}/create-image', 'InstanceController@createImage');
        Route::post('instances/{instanceId}/migrate', 'InstanceController@migrate');

        Route::group(['middleware' => 'instance-is-locked'], function () {
            Route::patch('instances/{instanceId}', 'InstanceController@update');
            Route::delete('instances/{instanceId}', 'InstanceController@destroy');
            Route::put('instances/{instanceId}/power-on', 'InstanceController@powerOn');
            Route::put('instances/{instanceId}/power-off', 'InstanceController@powerOff');
            Route::put('instances/{instanceId}/power-reset', 'InstanceController@powerReset');
            Route::put('instances/{instanceId}/power-restart', 'InstanceController@guestRestart');
            Route::put('instances/{instanceId}/power-shutdown', 'InstanceController@guestShutdown');
            Route::group(['middleware' => 'can-attach-instance-volume'], function () {
                Route::post('instances/{instanceId}/volume-attach', 'InstanceController@volumeAttach');
            });
            Route::post('instances/{instanceId}/volume-detach', 'InstanceController@volumeDetach');
        });
    });

    /** Floating Ips */
    Route::group([], function () {
        Route::get('floating-ips', 'FloatingIpController@index');
        Route::get('floating-ips/{fipId}', 'FloatingIpController@show');
        Route::get('floating-ips/{fipId}/tasks', 'FloatingIpController@tasks');
        Route::post('floating-ips', 'FloatingIpController@store');

        Route::group(['middleware' => 'floating-ip-can-be-assigned'], function () {
            Route::post('floating-ips/{fipId}/assign', 'FloatingIpController@assign');
        });

        Route::group(['middleware' => 'floating-ip-can-be-unassigned'], function () {
            Route::post('floating-ips/{fipId}/unassign', 'FloatingIpController@unassign');
        });

        Route::patch('floating-ips/{fipId}', 'FloatingIpController@update');

        Route::group(['middleware' => 'floating-ip-can-be-deleted'], function () {
            Route::delete('floating-ips/{fipId}', 'FloatingIpController@destroy');
        });
    });

    /** Firewall Policy */
    Route::group([], function () {
        Route::get('firewall-policies', 'FirewallPolicyController@index');
        Route::get('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@show');
        Route::get('firewall-policies/{firewallPolicyId}/firewall-rules', 'FirewallPolicyController@firewallRules');
        Route::get('firewall-policies/{firewallPolicyId}/tasks', 'FirewallPolicyController@tasks');
        Route::post('firewall-policies', 'FirewallPolicyController@store');
        Route::patch('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@update');
        Route::delete('firewall-policies/{firewallPolicyId}', 'FirewallPolicyController@destroy');
    });

    /** Firewall Rules */
    Route::group([], function () {
        Route::get('firewall-rules', 'FirewallRuleController@index');
        Route::get('firewall-rules/{firewallRuleId}', 'FirewallRuleController@show');
        Route::get('firewall-rules/{firewallRuleId}/ports', 'FirewallRuleController@ports');
        Route::post('firewall-rules', 'FirewallRuleController@store');
        Route::patch('firewall-rules/{firewallRuleId}', 'FirewallRuleController@update');
        Route::delete('firewall-rules/{firewallRuleId}', 'FirewallRuleController@destroy');
    });

    /** Firewall Rule Ports */
    Route::group([], function () {
        Route::get('firewall-rule-ports', 'FirewallRulePortController@index');
        Route::get('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@show');
        Route::post('firewall-rule-ports', 'FirewallRulePortController@store');
        Route::patch('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@update');
        Route::delete('firewall-rule-ports/{firewallRulePortId}', 'FirewallRulePortController@destroy');
    });

    /** Regions */
    Route::group([], function () {
        Route::get('regions', 'RegionController@index');
        Route::get('regions/{regionId}', 'RegionController@show');
        Route::get('regions/{regionId}/availability-zones', 'RegionController@availabilityZones');
        Route::get('regions/{regionId}/vpcs', 'RegionController@vpcs');
        Route::get('regions/{regionId}/prices', 'RegionController@prices');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('regions', 'RegionController@create');
            Route::patch('regions/{regionId}', 'RegionController@update');
            Route::delete('regions/{regionId}', [
                'middleware' => 'can-be-deleted:' . \App\Models\V2\Region::class   . ',regionId',
                'uses' => 'RegionController@destroy'
            ]);
        });
    });

    /** Load balancers */
    Route::group([], function () {
        Route::get('load-balancers', 'LoadBalancerController@index');
        Route::get('load-balancers/{loadBalancerId}', 'LoadBalancerController@show');
        Route::get('load-balancers/{loadBalancerId}/available-targets', 'LoadBalancerController@instances');
        Route::patch('load-balancers/{loadBalancerId}', 'LoadBalancerController@update');
        Route::delete('load-balancers/{loadBalancerId}', 'LoadBalancerController@destroy');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::get('load-balancers/{loadBalancerId}/networks', 'LoadBalancerController@networks');
            Route::get('load-balancers/{loadBalancerId}/nodes', 'LoadBalancerController@nodes');
        });

        Route::group(['middleware' => 'load-balancer-is-max-for-customer'], function () {
            Route::post('load-balancers', 'LoadBalancerController@store');
        });
    });

    /** VIPS */
    Route::group([], function () {
        Route::get('vips', 'VipController@index');
        Route::get('vips/{vipId}', 'VipController@show');
        Route::post('vips', [
            'middleware' => 'loadbalancer-max-vip',
            'uses' => 'VipController@create'
        ]);
        Route::patch('vips/{vipId}', 'VipController@update');
        Route::delete('vips/{vipId}', 'VipController@destroy');
    });

    /** Load balancer specifications */
    Route::get('load-balancer-specs', 'LoadBalancerSpecificationsController@index');
    Route::get('load-balancer-specs/{lbsId}', 'LoadBalancerSpecificationsController@show');

    Route::group(['middleware' => 'is-admin'], function () {
        Route::post('load-balancer-specs', 'LoadBalancerSpecificationsController@create');
        Route::patch('load-balancer-specs/{lbsId}', 'LoadBalancerSpecificationsController@update');
        Route::delete('load-balancer-specs/{lbsId}', 'LoadBalancerSpecificationsController@destroy');
    });

    /** Volumes */
    Route::group([], function () {
        Route::group(['middleware' => 'can-detach'], function () {
            Route::post('volumes/{volumeId}/detach', 'VolumeController@detach');
        });
        Route::get('volumes', 'VolumeController@index');
        Route::get('volumes/{volumeId}', 'VolumeController@show');
        Route::get('volumes/{volumeId}/instances', 'VolumeController@instances');
        Route::get('volumes/{volumeId}/tasks', 'VolumeController@tasks');
        Route::post('volumes', 'VolumeController@store');
        Route::patch('volumes/{volumeId}', 'VolumeController@update');
        Route::group(['middleware' => 'volume-can-be-deleted'], function () {
            Route::delete('volumes/{volumeId}', 'VolumeController@destroy');
        });
        Route::post('volumes/{volumeId}/attach', 'VolumeController@attach');
    });

    /** Volume Groups */
    Route::group([], function () {
        Route::get('volume-groups', 'VolumeGroupController@index');
        Route::get('volume-groups/{volumeGroupId}', 'VolumeGroupController@show');
        Route::get('volume-groups/{volumeGroupId}/volumes', 'VolumeGroupController@volumes');
        Route::post('volume-groups', 'VolumeGroupController@store');
        Route::patch('volume-groups/{volumeGroupId}', 'VolumeGroupController@update');
        Route::delete('volume-groups/{volumeGroupId}', [
            'middleware' => 'can-be-deleted:' . \App\Models\V2\VolumeGroup::class   . ',volumeGroupId',
            'uses' => 'VolumeGroupController@destroy'
        ]);
    });

    /** Nics */
    Route::group([], function () {
        Route::get('nics', 'NicController@index');
        Route::get('nics/{nicId}', 'NicController@show');
        Route::get('nics/{nicId}/tasks', 'NicController@tasks');
        Route::get('nics/{nicId}/ip-addresses', 'NicController@ipAddresses');
        Route::post('nics/{nicId}/ip-addresses', 'NicController@associateIpAddress');
        Route::delete('nics/{nicId}/ip-addresses/{ipAddressId}', 'NicController@disassociateIpAddress');
        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('nics', 'NicController@create');
            Route::patch('nics/{nicId}', 'NicController@update');
            Route::delete('nics/{nicId}', 'NicController@destroy');
        });
    });

    /** Credentials */
    Route::group(['middleware' => 'is-admin'], function () {
        Route::get('credentials', 'CredentialsController@index');
        Route::get('credentials/{credentialsId}', 'CredentialsController@show');
        Route::post('credentials', 'CredentialsController@store');
        Route::patch('credentials/{credentialsId}', 'CredentialsController@update');
        Route::delete('credentials/{credentialsId}', 'CredentialsController@destroy');
    });

    /** Discount Plans */
    Route::group([], function () {
        Route::get('discount-plans', 'DiscountPlanController@index');
        Route::get('discount-plans/{discountPlanId}', 'DiscountPlanController@show');
        Route::post('discount-plans', 'DiscountPlanController@store');

        Route::group(['middleware' => 'is-pending'], function () {
            Route::post('discount-plans/{discountPlanId}/approve', 'DiscountPlanController@approve');
            Route::post('discount-plans/{discountPlanId}/reject', 'DiscountPlanController@reject');
        });

        Route::group(['middleware' => 'is-admin'], function () {
            Route::patch('discount-plans/{discountPlanId}', 'DiscountPlanController@update');
            Route::delete('discount-plans/{discountPlanId}', 'DiscountPlanController@destroy');
        });
    });

    /** Billing Metrics */
    Route::group([], function () {
        Route::get('billing-metrics', 'BillingMetricController@index');
        Route::get('billing-metrics/{billingMetricId}', 'BillingMetricController@show');
        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('billing-metrics', 'BillingMetricController@create');
            Route::patch('billing-metrics/{billingMetricId}', 'BillingMetricController@update');
            Route::delete('billing-metrics/{billingMetricId}', 'BillingMetricController@destroy');
        });
    });

    /** Router Throughput */
    Route::group([], function () {
        Route::get('router-throughputs', 'RouterThroughputController@index');
        Route::get('router-throughputs/{routerThroughputId}', 'RouterThroughputController@show');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('router-throughputs', 'RouterThroughputController@store');
            Route::patch('router-throughputs/{routerThroughputId}', 'RouterThroughputController@update');
            Route::delete('router-throughputs/{routerThroughputId}', [
                'middleware' => 'can-be-deleted:' . \App\Models\V2\RouterThroughput::class   . ',routerThroughputId',
                'uses' => 'RouterThroughputController@destroy'
            ]);
        });
    });

    /** Host */
    Route::group([], function () {
        Route::get('hosts', 'HostController@index');
        Route::get('hosts/{id}', 'HostController@show');
        Route::get('hosts/{id}/tasks', 'HostController@tasks');
        Route::post('hosts', 'HostController@store');
        Route::patch('hosts/{id}', 'HostController@update');
        Route::delete('hosts/{id}', 'HostController@destroy');
    });

    /** Host Spec */
    Route::group([], function () {
        Route::get('host-specs', 'HostSpecController@index');
        Route::get('host-specs/{hostSpecId}', 'HostSpecController@show');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('host-specs', 'HostSpecController@store');
            Route::patch('host-specs/{hostSpecId}', 'HostSpecController@update');
            Route::delete('host-specs/{hostSpecId}', 'HostSpecController@destroy');
        });
    });

    /** Host Group */
    Route::group([], function () {
        Route::get('host-groups', 'HostGroupController@index');
        Route::get('host-groups/{id}', 'HostGroupController@show');
        Route::get('host-groups/{id}/tasks', 'HostGroupController@tasks');
        Route::post('host-groups', 'HostGroupController@store');
        Route::patch('host-groups/{id}', 'HostGroupController@update');
        Route::delete('host-groups/{id}', 'HostGroupController@destroy');
    });

    /** Images */
    Route::group([], function () {
        Route::get('images', 'ImageController@index');
        Route::get('images/{imageId}', 'ImageController@show');
        Route::get('images/{imageId}/parameters', 'ImageController@parameters');
        Route::get('images/{imageId}/metadata', 'ImageController@metadata');
        Route::get('images/{imageId}/software', 'ImageController@software');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('images', 'ImageController@store');
        });
        Route::group(['middleware' => 'can-update-image'], function () {
            Route::patch('images/{imageId}', 'ImageController@update');
        });
        Route::group(['middleware' => 'can-delete-image'], function () {
            Route::delete('images/{imageId}', 'ImageController@destroy');
        });
    });

    /** Image Parameters */
    Route::group(['middleware' => 'is-admin'], function () {
        Route::get('image-parameters', 'ImageParameterController@index');
        Route::get('image-parameters/{imageParameterId}', 'ImageParameterController@show');
        Route::post('image-parameters', 'ImageParameterController@store');
        Route::patch('image-parameters/{imageParameterId}', 'ImageParameterController@update');
        Route::delete('image-parameters/{imageParameterId}', 'ImageParameterController@destroy');
    });

    /** Image metadata */
    Route::get('image-metadata', 'ImageMetadataController@index');
    Route::get('image-metadata/{imageMetadataId}', 'ImageMetadataController@show');
    Route::group(['middleware' => 'is-admin'], function () {
        Route::post('image-metadata', 'ImageMetadataController@store');
        Route::patch('image-metadata/{imageMetadataId}', 'ImageMetadataController@update');
        Route::delete('image-metadata/{imageMetadataId}', 'ImageMetadataController@destroy');
    });

    /** SSH Key Pairs */
    Route::group([], function () {
        Route::group(['middleware' => ['has-reseller-id', 'customer-max-ssh-key-pairs']], function () {
            Route::post('ssh-key-pairs', 'SshKeyPairController@create');
        });
        Route::patch('ssh-key-pairs/{keypairId}', 'SshKeyPairController@update');
        Route::get('ssh-key-pairs', 'SshKeyPairController@index');
        Route::get('ssh-key-pairs/{keypairId}', 'SshKeyPairController@show');
        Route::delete('ssh-key-pairs/{keypairId}', 'SshKeyPairController@destroy');
    });

    /** Task */
    Route::group([], function () {
        Route::get('tasks', 'TaskController@index');
        Route::get('tasks/{taskId}', 'TaskController@show');
    });

    /** Orchestrator Configurations */
    Route::group(['middleware' => 'is-admin'], function () {
        Route::get('orchestrator-configs', 'OrchestratorConfigController@index');
        Route::get('orchestrator-configs/{orchestratorConfigId}', 'OrchestratorConfigController@show');
        Route::post('orchestrator-configs', 'OrchestratorConfigController@store');
        Route::patch('orchestrator-configs/{orchestratorConfigId}', 'OrchestratorConfigController@update');
        Route::delete('orchestrator-configs/{orchestratorConfigId}', 'OrchestratorConfigController@destroy');
        Route::get('orchestrator-configs/{orchestratorConfigId}/data', 'OrchestratorConfigController@showData');
        Route::get('orchestrator-configs/{orchestratorConfigId}/builds', 'OrchestratorConfigController@builds');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::put('orchestrator-configs/{orchestratorConfigId}/lock', 'OrchestratorConfigController@lock');
            Route::put('orchestrator-configs/{orchestratorConfigId}/unlock', 'OrchestratorConfigController@unlock');
        });

        Route::group(['middleware' => ['orchestrator-config-is-locked', 'orchestrator-config-is-valid']], function () {
            Route::post('orchestrator-configs/{orchestratorConfigId}/data', 'OrchestratorConfigController@storeData');
        });

        Route::group(['middleware' => 'orchestrator-config-has-reseller-id'], function () {
            Route::post('orchestrator-configs/{orchestratorConfigId}/deploy', 'OrchestratorConfigController@deploy');
        });
    });

    /** Orchestrator Builds */
    Route::group(['middleware' => 'is-admin'], function () {
        Route::get('orchestrator-builds', 'OrchestratorBuildController@index');
        Route::get('orchestrator-builds/{orchestratorBuildId}', 'OrchestratorBuildController@show');
    });

    /** IP Addresses */
    Route::group([], function () {
        Route::get('ip-addresses', 'IpAddressController@index');
        Route::get('ip-addresses/{ipAddressId}', 'IpAddressController@show');
        Route::post('ip-addresses', 'IpAddressController@store');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::get('ip-addresses/{ipAddressId}/nics', 'IpAddressController@nics');
        });

        Route::patch('ip-addresses/{ipAddressId}', 'IpAddressController@update');
        Route::delete('ip-addresses/{ipAddressId}', [
            'middleware' => 'can-be-deleted:' . \App\Models\V2\IpAddress::class   . ',ipAddressId',
            'uses' => 'IpAddressController@destroy'
        ]);
    });

    /** Software */
    Route::group([], function () {
        Route::get('software', 'SoftwareController@index');
        Route::get('software/{softwareId}', 'SoftwareController@show');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('software', 'SoftwareController@store');
            Route::patch('software/{softwareId}', 'SoftwareController@update');
            Route::delete('software/{softwareId}', 'SoftwareController@destroy');
        });

        Route::get('software/{softwareId}/scripts', 'SoftwareController@scripts');
        Route::get('software/{softwareId}/images', 'SoftwareController@images');
    });

    /** Scripts */
    Route::group([], function () {
        Route::get('scripts', 'ScriptController@index');
        Route::get('scripts/{scriptId}', 'ScriptController@show');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('scripts', 'ScriptController@store');
            Route::patch('scripts/{scriptId}', 'ScriptController@update');
            Route::delete('scripts/{scriptId}', 'ScriptController@destroy');
        });
    });

    /** Instance Software */
    Route::group([], function () {
        Route::get('instance-software', 'InstanceSoftwareController@index');
        Route::get('instance-software/{instanceSoftwareId}', 'InstanceSoftwareController@show');

        Route::group(['middleware' => 'is-admin'], function () {
            Route::post('instance-software', 'InstanceSoftwareController@store');
            Route::patch('instance-software/{instanceSoftwareId}', 'InstanceSoftwareController@update');
            Route::delete('instance-software/{instanceSoftwareId}', 'InstanceSoftwareController@destroy');
        });
    });

    /** Load Balancer Network */
    Route::group(['middleware' => 'is-admin'], function () {
        Route::get('load-balancer-networks', 'LoadBalancerNetworkController@index');
        Route::get('load-balancer-networks/{loadBalancerNetworkId}', 'LoadBalancerNetworkController@show');
        Route::post('load-balancer-networks', 'LoadBalancerNetworkController@store');
        Route::patch('load-balancer-networks/{loadBalancerNetworkId}', 'LoadBalancerNetworkController@update');

        Route::delete('load-balancer-networks/{loadBalancerNetworkId}', [
            'middleware' => 'can-be-deleted:' . \App\Models\V2\LoadBalancerNetwork::class   . ',loadBalancerNetworkId',
            'uses' => 'LoadBalancerNetworkController@destroy'
        ]);
    });
});
