<?php

namespace App\Providers;

use App\Listeners\V2\HostGroup\HostGroupEventSubscriber;
use App\Listeners\V2\Nic\NicEventSubscriber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
        \App\Events\V2\Dhcp\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // FloatingIp
        \App\Events\V2\FloatingIp\Deleted::class => [
            \App\Listeners\V2\AvailabilityZoneCapacity\UpdateFloatingIpCapacity::class,
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Host
        \App\Events\V2\Host\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // HostGroup
        \App\Events\V2\HostGroup\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Image
        \App\Events\V2\Image\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Instance
        \App\Events\V2\Instance\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // LoadBalancer
        \App\Events\V2\LoadBalancer\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Network
        \App\Events\V2\Network\Creating::class => [
            \App\Listeners\V2\Network\DefaultSubnet::class,
        ],
        \App\Events\V2\Network\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Nat
        \App\Events\V2\Nat\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Nic
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
        \App\Events\V2\Router\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Volume
        \App\Events\V2\Volume\Creating::class => [
            \App\Listeners\V2\Volume\DefaultIops::class,
        ],
        \App\Events\V2\Volume\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Vpn
        \App\Events\V2\VpnSession\Deleted::class => [
            \App\Listeners\V2\BillingMetric\End::class,
        ],

        // Task
        \App\Events\V2\Task\Created::class => [
            \App\Listeners\V2\DispatchTaskJob::class,
        ],
        \App\Events\V2\Task\Updated::class => [
            \App\Listeners\V2\DeleteSyncTaskResource::class,
            \App\Listeners\V2\Volume\UpdateBilling::class,
            \App\Listeners\V2\Router\UpdateBilling::class,
            \App\Listeners\V2\Image\UpdateImageBilling::class,
            \App\Listeners\V2\Instance\UpdateRamBilling::class,
            \App\Listeners\V2\Instance\UpdateVcpuBilling::class,
            \App\Listeners\V2\Instance\UpdateWindowsLicenseBilling::class,
            \App\Listeners\V2\Instance\UpdateMsSqlLicenseBilling::class,
            \App\Listeners\V2\Instance\UpdateLicenseBilling::class,
            \App\Listeners\V2\Instance\UpdateBackupBilling::class,
            \App\Listeners\V2\Instance\UpdateResourceTierBilling::class,
            \App\Listeners\V2\Host\UpdateBilling::class,
            \App\Listeners\V2\Host\ToggleHostGroupBilling::class,
            \App\Listeners\V2\Host\UpdateLicenseBilling::class,
            \App\Listeners\V2\FloatingIp\UpdateBilling::class,
            \App\Listeners\V2\Vpc\UpdateAdvancedNetworkingBilling::class,
            \App\Listeners\V2\VpnSession\UpdateBilling::class,
            \App\Listeners\V2\LoadBalancer\UpdateBilling::class,
            \App\Listeners\V2\InstanceSoftware\UpdateBilling::class,
        ],
    ];

    protected $subscribe = [
        HostGroupEventSubscriber::class,
        NicEventSubscriber::class,
    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
