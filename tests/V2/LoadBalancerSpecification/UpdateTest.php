<?php

namespace Tests\V2\LoadBalancerSpecification;

use App\Models\V2\Image;
use App\Models\V2\LoadBalancerSpecification;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $image;
    protected $loadBalancerSpecification;

    public function setUp(): void
    {
        parent::setUp();
        $this->image = factory(Image::class)->create();
        $this->loadBalancerSpecification = LoadBalancerSpecification::factory()->create([
            "image_id" => $this->image->id
        ]);
    }

    public function testValidDataIsSuccessful()
    {
        $data = [
            "name" => "small-1",
            "node_count" => 5,
            "cpu" => 5,
            "ram" => 5,
            "hdd" => 5,
            "iops" => 5,
            "image_id" => $this->image->id
        ];

        $this->patch('/v2/load-balancer-specs/' . $this->loadBalancerSpecification->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read,ecloud.write',
            ]
        )->seeInDatabase(
            'load_balancer_specifications',
            $data,
            'ecloud'
        )->assertResponseStatus(200);
    }
}
