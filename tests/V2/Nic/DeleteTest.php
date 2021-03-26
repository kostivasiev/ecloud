<?php

namespace Tests\V2\Nic;

use App\Events\V2\Nic\Deleted;
use App\Events\V2\Nic\Deleting;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availabilityZone;
    protected $instance;
    protected $macAddress;
    protected $network;
    protected $nic;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->macAddress = $this->faker->macAddress;
    }

    public function testValidNicSucceeds()
    {
        Event::fake();

        $nic = factory(Nic::class)->create([
            'id' => 'nic-test',
            'mac_address' => $this->macAddress,
            'ip_address' => '10.0.0.1',
        ]);

        $this->delete('/v2/nics/' . $nic->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);

        Event::assertDispatched(Deleted::class);
    }
}
