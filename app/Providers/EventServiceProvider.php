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
            \App\Listeners\V2\AvailabilityZone\UKFastId::class,
        ],

        // Credential
        \App\Events\V2\Credential\Creating::class => [
            \App\Listeners\V2\Credential\UKFastId::class,
        ],

        // Dhcp
        \App\Events\V2\Dhcp\Creating::class => [
            \App\Listeners\V2\Dhcp\UKFastId::class,
        ],
        \App\Events\V2\Dhcp\Created::class => [
            \App\Listeners\V2\Dhcp\Deploy::class,
        ],

        // FirewallRule
        \App\Events\V2\FirewallRule\Creating::class => [
            \App\Listeners\V2\FirewallRule\UKFastId::class,
        ],
        \App\Events\V2\FirewallRule\Created::class => [
            \App\Listeners\V2\FirewallRule\Deploy::class,
        ],

        // FloatingIp
        \App\Events\V2\FloatingIp\Creating::class => [
            \App\Listeners\V2\FloatingIp\UKFastId::class,
        ],

        // Instance
        \App\Events\V2\Instance\Creating::class => [
            \App\Listeners\V2\Instance\UKFastId::class,
        ],
        \App\Events\V2\Instance\Created::class => [
            \App\Listeners\V2\Instance\DefaultPlatform::class,
        ],
        \App\Events\V2\Instance\Deploy::class => [
            \App\Listeners\V2\Instance\Deploy::class,
        ],

        // LoadBalancerCluster
        \App\Events\V2\LoadBalancerCluster\Creating::class => [
            \App\Listeners\V2\LoadBalancerCluster\UKFastId::class,
        ],

        // Network
        \App\Events\V2\Network\Creating::class => [
            \App\Listeners\V2\Network\UKFastId::class,
            \App\Listeners\V2\Network\DefaultSubnet::class,
        ],
        \App\Events\V2\Network\Created::class => [
            \App\Listeners\V2\Network\Deploy::class,
        ],

        // Nic
        \App\Events\V2\Nic\Creating::class => [
            \App\Listeners\V2\Nic\UKFastId::class,
        ],

        // Region
        \App\Events\V2\Region\Creating::class => [
            \App\Listeners\V2\Region\UKFastId::class,
        ],

        // Router
        \App\Events\V2\Router\Creating::class => [
            \App\Listeners\V2\Router\UKFastId::class,
            \App\Listeners\V2\Router\UKFastId::class,
        ],
        \App\Events\V2\Router\Created::class => [
            \App\Listeners\V2\Router\Deploy::class,
        ],

        // Volume
        \App\Events\V2\Volume\Creating::class => [
            \App\Listeners\V2\Volume\UKFastId::class,
        ],

        // Vpc
        \App\Events\V2\Vpc\Creating::class => [
            \App\Listeners\V2\Vpc\UKFastId::class,
        ],
        \App\Events\V2\Vpc\Created::class => [
            \App\Listeners\V2\Vpc\DhcpCreate::class,
        ],

        // Vpn
        \App\Events\V2\Vpn\Creating::class => [
            \App\Listeners\V2\Vpn\UKFastId::class,
        ],
    ];
}
