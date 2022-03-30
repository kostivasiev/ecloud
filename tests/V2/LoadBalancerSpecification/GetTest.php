<?php

namespace Tests\V2\LoadBalancerSpecification;

use App\Models\V2\Image;
use App\Models\V2\LoadBalancerSpecification;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $loadBalancerSpecification;

    public function setUp(): void
    {
        parent::setUp();
        $this->image = Image::factory()->create();

        $this->loadBalancerSpecification = LoadBalancerSpecification::factory()->create([
            'name' => 'medium',
            'node_count' => 2,
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'iops' => 300,
            'image_id' => $this->image->id
        ]);

        LoadBalancerSpecification::factory()->create([
            'name' => 'small',
            'node_count' => 1,
            'cpu' => 1,
            'ram' => 4,
            'hdd' => 10,
            "image_id" => 'img-test',
        ]);

        LoadBalancerSpecification::factory()->create([
            'name' => 'super-small',
            'node_count' => 1,
            'cpu' => 1,
            'ram' => 1,
            'hdd' => 10,
            "image_id" => 'img-test',
        ]);

        LoadBalancerSpecification::factory()->create([
            'name' => 'medium-large',
            'node_count' => 3,
            'cpu' => 2,
            'ram' => 4,
            'hdd' => 30,
            'iops' => 300,
            'image_id' => 'img-test',
        ]);

        LoadBalancerSpecification::factory()->create([
            'name' => 'large',
            'node_count' => 3,
            'cpu' => 2,
            'ram' => 4,
            'hdd' => 40,
            'iops' => 300,
            'image_id' => 'img-test',
        ]);
    }

    public function testGetItemCollection()
    {
        $this->get('/v2/load-balancer-specs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->loadBalancerSpecification->id,
            'name' => $this->loadBalancerSpecification->name,
            'description' => $this->loadBalancerSpecification->description,
            'node_count' => $this->loadBalancerSpecification->node_count,
            'cpu' => $this->loadBalancerSpecification->cpu,
            'ram' => $this->loadBalancerSpecification->ram,
            'hdd' => $this->loadBalancerSpecification->hdd,
            'iops' => $this->loadBalancerSpecification->iops,
            'image_id' => $this->loadBalancerSpecification->image_id,
        ])->assertStatus(200);
    }

    public function testGetItemCollectionOrdered()
    {
        $this->get('/v2/load-balancer-specs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertSeeTextInOrder([
            'super-small',
            'small',
            'medium',
            'medium-large',
            'large'
        ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/load-balancer-specs/' . $this->loadBalancerSpecification->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->loadBalancerSpecification->id,
            'name' => $this->loadBalancerSpecification->name,
            'description' => $this->loadBalancerSpecification->description,
            'node_count' => $this->loadBalancerSpecification->node_count,
            'cpu' => $this->loadBalancerSpecification->cpu,
            'ram' => $this->loadBalancerSpecification->ram,
            'hdd' => $this->loadBalancerSpecification->hdd,
            'iops' => $this->loadBalancerSpecification->iops,
            'image_id' => $this->loadBalancerSpecification->image_id,
        ])->assertStatus(200);
    }
}
