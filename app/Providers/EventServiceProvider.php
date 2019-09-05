<?php

namespace App\Providers;

use App\Events\V1\DatastoreCreatedEvent;
use App\Events\V1\DatastoreExpandEvent;
use App\Events\V1\VolumeSetIopsUpdatedEvent;
use App\Listeners\V1\DatastoreCreatedListener;
use App\Listeners\V1\DatastoreExpandListener;
use App\Listeners\V1\VolumeSetIopsUpdatedListener;
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
        DatastoreCreatedEvent::class => [
            DatastoreCreatedListener::class,
        ],
        DatastoreExpandEvent::class => [
            DatastoreExpandListener::class,
        ],
        VolumeSetIopsUpdatedEvent::class => [
            VolumeSetIopsUpdatedListener::class,
        ]
    ];
}
