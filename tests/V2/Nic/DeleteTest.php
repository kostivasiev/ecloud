<?php

namespace Tests\V2\Nic;

use App\Events\V2\Nic\Deleted;
use App\Models\V2\Nic;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
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
        ])->assertResponseStatus(202);

        Event::assertDispatched(Deleted::class);
    }
}
