<?php

namespace Tests\V2\LoadBalancerSpecification;

use App\Models\V2\Image;
use App\Models\V2\LoadBalancerSpecification;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $loadBalancerSpecification;

    public function setUp(): void
    {
        parent::setUp();
        $this->image = factory(Image::class)->create();
        $this->loadBalancerSpecification = factory(LoadBalancerSpecification::class)->create([
            "image_id" => $this->image->id
        ]);
    }

    public function testGetItemCollection()
    {
        $this->get('/v2/load-balancer-specs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->loadBalancerSpecification->id,
            'name' => $this->loadBalancerSpecification->name,
            'description' => $this->loadBalancerSpecification->description,
            'node_count' => $this->loadBalancerSpecification->node_count,
            'cpu' => $this->loadBalancerSpecification->cpu,
            'ram' => $this->loadBalancerSpecification->ram,
            'hdd' => $this->loadBalancerSpecification->hdd,
            'iops' => $this->loadBalancerSpecification->iops,
            'image_id' => $this->loadBalancerSpecification->image_id,
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/load-balancer-specs/' . $this->loadBalancerSpecification->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->loadBalancerSpecification->id,
            'name' => $this->loadBalancerSpecification->name,
            'description' => $this->loadBalancerSpecification->description,
            'node_count' => $this->loadBalancerSpecification->node_count,
            'cpu' => $this->loadBalancerSpecification->cpu,
            'ram' => $this->loadBalancerSpecification->ram,
            'hdd' => $this->loadBalancerSpecification->hdd,
            'iops' => $this->loadBalancerSpecification->iops,
            'image_id' => $this->loadBalancerSpecification->image_id,
        ])->assertResponseStatus(200);
    }
}
