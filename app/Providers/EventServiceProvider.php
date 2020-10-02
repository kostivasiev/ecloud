<?php

namespace App\Providers;

use App\Events\V2\FirewallRuleCreated;
use App\Events\V2\InstanceDeployEvent;
use App\Events\V2\NetworkCreated;
use App\Events\V2\RouterCreated;
use App\Listeners\V2\FirewallRuleDeploy;
use App\Listeners\V2\InstanceDeploy;
use App\Listeners\V2\NetworkDeploy;
use App\Events\V2\DhcpCreated;
use App\Events\V2\VpcCreated;
use App\Listeners\V2\DhcpCreate;
use App\Listeners\V2\DhcpDeploy;
use App\Listeners\V2\RouterDeploy;
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
        RouterCreated::class => [
            RouterDeploy::class,
        ],
        VpcCreated::class => [
            DhcpCreate::class,
        ],
        DhcpCreated::class => [
            DhcpDeploy::class,
        ],
        NetworkCreated::class => [
            NetworkDeploy::class
        ],
        FirewallRuleCreated::class => [
            FirewallRuleDeploy::class
        ],
        InstanceDeployEvent::class => [
            InstanceDeploy::class
        ],
    ];
}
