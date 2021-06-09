<?php

namespace Tests\V2\VpcSupport;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $region;
    protected $vpc;
    protected $vpcSupport;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = Vpc::withoutEvents(function () {
            return factory(Vpc::class)->create([
                'id' => 'vpc-test',
                'region_id' => $this->region->id
            ]);
        });
        $this->vpcSupport = factory(VpcSupport::class)->create([
            'vpc_id' => $this->vpc->id
        ]);
    }

    public function testGetItemCollection()
    {
        $this->get('/v2/support', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vpcSupport->id,
            'vpc_id' => $this->vpc->id,
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/support/' . $this->vpcSupport->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vpcSupport->id,
            'vpc_id' => $this->vpc->id,
        ])->assertResponseStatus(200);
    }
}
