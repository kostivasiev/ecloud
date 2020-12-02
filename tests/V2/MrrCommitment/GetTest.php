<?php

namespace Tests\V2\MrrCommitment;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\MrrCommitment;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{

    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected MrrCommitment $commitment;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->commitment = factory(MrrCommitment::class)->create([
            'contact_id' => 1,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/mrr-commitments',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'name' => $this->commitment->name,
            'commitment_amount' => $this->commitment->commitment_amount,
            'commitment_before_discount' => $this->commitment->commitment_before_discount,
            'discount_rate' => $this->commitment->discount_rate,
            'term_length' => $this->commitment->term_length,
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/mrr-commitment/'.$this->commitment->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'name' => $this->commitment->name,
            'commitment_amount' => $this->commitment->commitment_amount,
            'commitment_before_discount' => $this->commitment->commitment_before_discount,
            'discount_rate' => $this->commitment->discount_rate,
            'term_length' => $this->commitment->term_length,
        ])->assertResponseStatus(200);
    }
}