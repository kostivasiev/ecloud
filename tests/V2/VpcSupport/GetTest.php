<?php

namespace Tests\V2\VpcSupport;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
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

    public function testGetItemCollection()
    {
        $this->get('/v2/support', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vpcSupport->id,
            'vpc_id' => $this->vpc->getKey(),
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/support/' . $this->vpcSupport->getKey(), [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vpcSupport->id,
            'vpc_id' => $this->vpc->getKey(),
        ])->assertResponseStatus(200);
    }
}
