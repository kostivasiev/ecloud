<?php

namespace Tests\V2\AvailabilityZone;

use App\Events\V2\AvailabilityZone\Deleted;
use App\Jobs\AvailabilityZone\DeleteCredentials;
use App\Jobs\AvailabilityZone\DeleteDhcps;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AutoDeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availabilityZone;
    protected Dhcp $dhcp;
    protected Credential $credential;

    protected $dispatcher;

    public function __construct()
    {
        $this->dispatcher = Event::getFacadeRoot();
        parent::__construct();
    }

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            return $mockEncryptionServiceProvider;
        });
        $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
        $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');

        $region = factory(Region::class)->create();
        $vpc = factory(Vpc::class)->create([
            'region_id' => $region->getKey(),
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey(),
        ]);
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->getKey(),
        ]);
        $this->credential = factory(Credential::class)->create([
            'resource_id' => $this->availabilityZone->getKey(),
        ]);
    }

    public function testDeleteCredentialAndDhcp()
    {
        // Delete the record
        $this->availabilityZone->delete();

        // Refresh the model
        $this->availabilityZone->refresh();

        // Simulate the event
        $fakeEvent = new Deleted($this->availabilityZone);
        event($fakeEvent);
        Event::assertDispatched(Deleted::class, function ($event) {
            return $event->availabilityZoneId == $this->availabilityZone->getKey();
        });

        // Simulate the listener for credentials
        (new DeleteCredentials([
            'availability_zone_id' => $fakeEvent->availabilityZoneId
        ]))->handle();
        $this->assertNotNull($this->availabilityZone->deleted_at);
        $this->assertNotNull($this->credential->fresh()->deleted_at);

        // Simulate the DHCP listener
        (new DeleteDhcps([
            'availability_zone_id' => $fakeEvent->availabilityZoneId
        ]))->handle();
        $this->assertNotNull($this->availabilityZone->deleted_at);
        $this->assertNotNull($this->dhcp->fresh()->deleted_at);
    }
}