<?php

namespace Tests\unit\Models;

use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class NatTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected $floating_ip;
    protected $nat;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        Model::withoutEvents(function () {
            $this->floating_ip = factory(FloatingIp::class)->create([
                'id' => 'fip-test2',
                'ip_address' => $this->faker->ipv4,
            ]);
            $this->nat = factory(Nat::class)->create([
                'id' => 'nat-1234562',
                'destination_id' => $this->floating_ip->id,
                'destinationable_type' => FloatingIp::class,
                'translated_id' => $this->nic()->id,
                'translatedable_type' => Nic::class,
            ]);
        });
    }

    public function testDestinationResourceReturnsFloatingIp()
    {
        $this->assertTrue($this->nat->destination instanceof FloatingIp);
    }

    public function testTranslatedResourceReturnsNic()
    {
        $this->assertTrue($this->nat->translated instanceof Nic);
    }
}
