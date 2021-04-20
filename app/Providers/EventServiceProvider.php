<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ExampleEvent' => [
            'App\Listeners\ExampleListener',
        ],
        ///////////////////////////////////////////////////////////////////////////////////////////////
        // V1
        ///////////////////////////////////////////////////////////////////////////////////////////////
        'App\Events\V1\ApplianceParameterDeletedEvent' => [
            'App\Listeners\V1\ApplianceParameterDeletedListener',
        ],
        'App\Events\V1\ApplianceVersionDeletedEvent' => [
            'App\Listeners\V1\ApplianceVersionDeletedListener',
        ],
        'App\Events\V1\ApplianceDeletedEvent' => [
            'App\Listeners\V1\ApplianceDeletedListener',
        ],
        'App\Events\V1\AppliancePodAvailabilityDeletedEvent' => [
            'App\Listeners\V1\AppliancePodAvailabilityDeletedListener',
        ],
        'App\Events\V1\EncryptionEnabledOnSolutionEvent' => [
            'App\Listeners\V1\EncryptionEnabledOnSolutionListener',
        ],
        'App\Events\V1\ApplianceLaunchedEvent' => [
            'App\Listeners\V1\ApplianceLaunchedListener',
        ],
        'App\Events\V1\DatastoreCreatedEvent' => [
            'App\Listeners\V1\DatastoreCreatedListener',
        ],
        'App\Events\V1\DatastoreExpandEvent' => [
            'App\Listeners\V1\DatastoreExpandListener',
        ],
        'App\Events\V1\VolumeSetIopsUpdatedEvent' => [
            'App\Listeners\V1\VolumeSetIopsUpdatedListener',
        ],
        ///////////////////////////////////////////////////////////////////////////////////////////////
        // V2
        ///////////////////////////////////////////////////////////////////////////////////////////////

        // AvailabilityZone
        \App\Events\V2\AvailabilityZone\Creating::class => [
        ],

        // AvailabilityZoneCapacity
        \App\Events\V2\AvailabilityZoneCapacity\Saved::class => [
            \App\Listeners\V2\AvailabilityZoneCapacity\SendAlert::class,
        ],

        // Credential
        \App\Events\V2\Credential\Creating::class => [
        ],

        // Dhcp
        \App\Events\V2\Dhcp\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Dhcp\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Dhcp\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\Dhcp\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // FirewallPolicy
        \App\Events\V2\FirewallPolicy\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\FirewallPolicy\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\FirewallPolicy\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\FirewallPolicy\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // FirewallRule
        \App\Events\V2\FirewallRule\Saving::class => [
            \App\Listeners\V2\FirewallRule\CheckFirewallPolicy::class,
        ],
        \App\Events\V2\FirewallRule\Deleting::class => [
            \App\Listeners\V2\FirewallRule\CheckFirewallPolicy::class,
        ],
        \App\Events\V2\FirewallRule\Deleted::class => [
            \App\Listeners\V2\FirewallRule\Undeploy::class,
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // FirewallRulePort
        \App\Events\V2\FirewallRulePort\Saving::class => [
            \App\Listeners\V2\FirewallRulePort\CheckFirewallPolicy::class,
        ],
        \App\Events\V2\FirewallRulePort\Deleting::class => [
            \App\Listeners\V2\FirewallRulePort\CheckFirewallPolicy::class,
        ],
        \App\Events\V2\FirewallRulePort\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // FloatingIp
        \App\Events\V2\FloatingIp\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\FloatingIp\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\FloatingIp\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\FloatingIp\Deleted::class => [
            \App\Listeners\V2\AvailabilityZoneCapacity\UpdateFloatingIpCapacity::class,
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Host
        \App\Events\V2\Host\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Host\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Host\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],

        // Instance
        \App\Events\V2\Instance\Creating::class => [
            \App\Listeners\V2\Instance\DefaultPlatform::class,
        ],
        \App\Events\V2\Instance\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Instance\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Instance\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\Instance\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // InstanceVolume
        \App\Events\V2\InstanceVolume\Creating::class => [
            \App\Listeners\V2\InstanceVolume\MarkSyncing::class,
        ],
        \App\Events\V2\InstanceVolume\Created::class => [
            \App\Listeners\V2\InstanceVolume\Attach::class,
        ],
        \App\Events\V2\InstanceVolume\Deleting::class => [
            \App\Listeners\V2\InstanceVolume\MarkSyncing::class,
        ],
        \App\Events\V2\InstanceVolume\Deleted::class => [
            \App\Listeners\V2\InstanceVolume\Detach::class,
        ],

        // LoadBalancerCluster
        \App\Events\V2\LoadBalancerCluster\Creating::class => [
        ],

        // Network
        \App\Events\V2\Network\Creating::class => [
            \App\Listeners\V2\Network\DefaultSubnet::class,
        ],
        \App\Events\V2\Network\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Network\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Network\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\Network\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // NetworkPolicy
        \App\Events\V2\NetworkPolicy\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // NetworkRule
        \App\Events\V2\NetworkRule\Deleted::class => [
            \App\Listeners\V2\NetworkRule\Undeploy::class,
            \App\Listeners\V2\BillingMetric\End::class,
            // TODO: not convinced we need to re-deploy the policy here. Undeploy will delete the rule, this just pointlessly redeploys the policy.
            \App\Listeners\V2\NetworkRule\UpdateNetworkPolicy::class,
        ],
        \App\Events\V2\NetworkRule\Saved::class => [
            \App\Listeners\V2\NetworkRule\UpdateNetworkPolicy::class,
        ],

        // NetworkRulePort
        \App\Events\V2\NetworkRulePort\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
            \App\Listeners\V2\NetworkRulePort\UpdateNetworkPolicy::class,
        ],
        \App\Events\V2\NetworkRulePort\Saved::class => [
            \App\Listeners\V2\NetworkRulePort\UpdateNetworkPolicy::class,
        ],

        // Nat
        \App\Events\V2\Nat\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Nat\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Nat\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\Nat\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Nic
        \App\Events\V2\Nic\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Nic\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Nic\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\Nic\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Region
        \App\Events\V2\Region\Creating::class => [
        ],

        // Router
        \App\Events\V2\Router\Creating::class => [
            \App\Listeners\V2\Router\DefaultRouterThroughput::class
        ],
        \App\Events\V2\Router\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Router\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Router\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\Router\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Volume
        \App\Events\V2\Volume\Creating::class => [
            \App\Listeners\V2\Volume\DefaultIops::class,
        ],
        \App\Events\V2\Volume\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Volume\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Volume\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],
        \App\Events\V2\Volume\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Vpc
        \App\Events\V2\Vpc\Saving::class => [
            \App\Listeners\V2\ResourceSyncSaving::class,
        ],
        \App\Events\V2\Vpc\Saved::class => [
            \App\Listeners\V2\ResourceSyncSaved::class,
        ],
        \App\Events\V2\Vpc\Deleting::class => [
            \App\Listeners\V2\ResourceSyncDeleting::class,
        ],

        // Vpn
        \App\Events\V2\Vpn\Creating::class => [
        ],

        // HostGroup
        \App\Events\V2\HostGroup\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Sync
        \App\Events\V2\Sync\Created::class => [
            \App\Listeners\V2\SyncCreated::class
        ],
        \App\Events\V2\Sync\Updated::class => [
            \App\Listeners\V2\Volume\UpdateBilling::class,
            \App\Listeners\V2\Router\UpdateBilling::class,
            \App\Listeners\V2\HostGroup\UpdateBilling::class,
            \App\Listeners\V2\Instance\UpdateRamBilling::class,
            \App\Listeners\V2\Instance\UpdateVcpuBilling::class,
            \App\Listeners\V2\Instance\UpdateLicenseBilling::class,
            \App\Listeners\V2\Instance\UpdateBackupBilling::class,
        ],
    ];
}
