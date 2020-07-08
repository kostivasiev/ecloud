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
//        'App\Events\V2\AvailabilityZones\AfterCreateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\AvailabilityZones\AfterDeleteEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\AvailabilityZones\AfterUpdateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\AvailabilityZones\BeforeCreateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\AvailabilityZones\BeforeDeleteEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\AvailabilityZones\BeforeUpdateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\VirtualDataCentres\AfterCreateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\VirtualDataCentres\AfterDeleteEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\VirtualDataCentres\AfterUpdateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\VirtualDataCentres\BeforeCreateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\VirtualDataCentres\BeforeDeleteEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
//        'App\Events\V2\VirtualDataCentres\BeforeUpdateEvent' => [
//            'App\Listeners\V2\ListenerClass',
//        ],
    ];
}
