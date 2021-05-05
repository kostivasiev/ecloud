<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\Nic;
use App\Rules\V2\IpAvailable;
use Faker\Factory as Faker;
use Illuminate\Database\QueryException;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IpAvailableTest extends TestCase
{
    protected $faker;
    protected $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->validator = new IpAvailable('net-abc123');
    }

    public function testAlreadyAssigned()
    {
        Nic::withoutEvents(function() {
            factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => $this->faker->macAddress,
                'instance_id' => 'i-abc123',
                'network_id' => 'net-abc123',
                'ip_address' => '10.0.0.2'
            ]);
        });

        $this->assertFalse($this->validator->passes('', '10.0.0.2'));
    }

    public function testAssignedDeleted()
    {
        Nic::withoutEvents(function() {
            $nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => $this->faker->macAddress,
                'instance_id' => 'i-abc123',
                'network_id' => 'net-abc123',
                'ip_address' => '10.0.0.2'
            ]);

            $nic->delete();
        });

        $this->assertTrue($this->validator->passes('', '10.0.0.2'));
    }

    public function testAssignedDifferentNetworkId()
    {
        Nic::withoutEvents(function() {
            factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => $this->faker->macAddress,
                'instance_id' => 'i-abc123',
                'network_id' => 'net-abc321',
                'ip_address' => '10.0.0.2'
            ]);
        });

        $this->assertTrue($this->validator->passes('', '10.0.0.2'));
    }

    public function testNotAssignedPasses()
    {
        $this->assertTrue($this->validator->passes('', '10.0.0.3'));
    }
}
