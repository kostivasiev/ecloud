<?php

namespace Tests\V2\VpcSupport;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $vpc;
    protected $vpcSupport;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpcSupport = factory(VpcSupport::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testValidDataIsSuccessful()
    {
        $vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $data = [
            'vpc_id' => $vpc->getKey(),
        ];

        $this->patch('/v2/support/' . $this->vpcSupport->getKey(), $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase(
            'vpc_support',
            $data,
            'ecloud'
        )
            ->assertResponseStatus(200);

    }
}