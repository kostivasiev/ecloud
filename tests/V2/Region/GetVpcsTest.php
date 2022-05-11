<?php

namespace Tests\V2\Region;

use Faker\Factory as Faker;
use Tests\TestCase;

class GetVpcsTest extends TestCase
{
    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->vpc();
    }

    public function testGetVpcCollection()
    {
        $this->get(
            '/v2/regions/'.$this->region()->id.'/vpcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id'        => $this->vpc()->id,
                'name'      => $this->vpc()->name,
                'region_id' => $this->vpc()->region_id,
            ])
            ->assertStatus(200);
    }
}
