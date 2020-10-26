<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Application;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{

    public $validReadHeaders = [
        'X-consumer-custom-id' => '1-1',
        'X-consumer-groups' => 'ecloud.read',
    ];

    public $validWriteHeaders = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.write',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Do not dispatch non-ORM events for the following models
        $dispatcher = Model::getEventDispatcher();

        // V1 hack
        $dispatcher->forget(\App\Events\V1\DatastoreCreatedEvent::class);

        // Created
        $dispatcher->forget(\App\Events\V2\AvailabilityZone\Created::class);
        $dispatcher->forget(\App\Events\V2\Dhcp\Created::class);
        $dispatcher->forget(\App\Events\V2\FirewallRule\Created::class);
        $dispatcher->forget(\App\Events\V2\Instance\Created::class);
        $dispatcher->forget(\App\Events\V2\Network\Created::class);
        $dispatcher->forget(\App\Events\V2\Router\Created::class);
        $dispatcher->forget(\App\Events\V2\Vpc\Created::class);
        $dispatcher->forget(\App\Events\V2\FloatingIp\Created::class);
        $dispatcher->forget(\App\Events\V2\Nat\Created::class);

        // Updated
        $dispatcher->forget(\App\Events\V2\Volume\Updated::class);

        // Deploy
        $dispatcher->forget(\App\Events\V2\Instance\Deploy::class);
    }

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }
}
