<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
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

        Event::fake([
            // V1 hack
            \App\Events\V1\DatastoreCreatedEvent::class,

            // Created
            \App\Events\V2\AvailabilityZone\Created::class,
            \App\Events\V2\Dhcp\Created::class,
            \App\Events\V2\Instance\Created::class,
            \App\Events\V2\Network\Created::class,
            \App\Events\V2\Router\Created::class,
            \App\Events\V2\Vpc\Created::class,
            \App\Events\V2\FloatingIp\Created::class,
            \App\Events\V2\Nat\Created::class,
            \App\Events\V2\FirewallPolicy\Created::class,
            \App\Events\V2\Nic\Created::class,

            // Deleting
            \App\Events\V2\Nat\Deleting::class,

            // Deleted
            \App\Events\V2\Nat\Deleted::class,
            \App\Events\V2\FirewallRule\Deleted::class,
            \App\Events\V2\FirewallPolicy\Deleted::class,
            \App\Events\V2\Vpc\Deleted::class,
            \App\Events\V2\Dhcp\Deleted::class,
            \App\Events\V2\Nic\Deleted::class,
            \App\Events\V2\FirewallRulePort\Deleted::class,
            \App\Events\V2\FloatingIp\Deleted::class,
            \App\Events\V2\Volume\Deleted::class,
            \App\Events\V2\Network\Deleted::class,
            \App\Events\V2\Router\Deleted::class,

            // Saved
            \App\Events\V2\FirewallRule\Saved::class,
            \App\Events\V2\FirewallPolicy\Saved::class,
            \App\Events\V2\Router\Saved::class,
            \App\Events\V2\Network\Saved::class,
            \App\Events\V2\Nat\Saved::class,
            \App\Events\V2\FirewallRulePort\Saved::class,
            \App\Events\V2\AvailabilityZoneCapacity\Saved::class,
            \App\Events\V2\Volume\Saved::class,

            // Updated
            \App\Events\V2\Sync\Updated::class,

            // Saving
            \App\Events\V2\Router\Saving::class,
            \App\Events\V2\Network\Saving::class,
            \App\Events\V2\FirewallRule\Saving::class,
            \App\Events\V2\FirewallPolicy\Saving::class,
            \App\Events\V2\FirewallRulePort\Saving::class,
            \App\Events\V2\Nic\Saving::class,
            \App\Events\V2\Nat\Saving::class,

            // Deleting
            \App\Events\V2\FirewallRule\Deleting::class,
            \App\Events\V2\FirewallPolicy\Deleting::class,
            \App\Events\V2\FirewallRulePort\Deleting::class,
            \App\Events\V2\Volume\Saving::class,

            // Deploy
            \App\Events\V2\Instance\Deploy::class,
        ]);
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
