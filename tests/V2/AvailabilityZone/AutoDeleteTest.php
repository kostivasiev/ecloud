<?php

namespace Tests\V2\AvailabilityZone;

use App\Events\V2\AvailabilityZone\Deleted;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AutoDeleteTest extends TestCase
{
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

        Model::withoutEvents(function() {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
            $this->credential = factory(Credential::class)->create([
                'id' => 'cred-test',
                'resource_id' => $this->availabilityZone()->id,
            ]);
        });
    }

    public function testDeleteCredentialAndDhcp()
    {
        $this->delete(
            '/v2/availability-zones/' . $this->availabilityZone()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(204);

        Event::assertDispatched(Deleted::class, function ($event) {
            return $event->model->id == $this->availabilityZone()->id;
        });
    }
}