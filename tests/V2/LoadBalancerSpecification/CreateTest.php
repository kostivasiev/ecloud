<?php

namespace Tests\V2\LoadBalancerSpecification;

use App\Models\V2\Image;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $image;

    public function setUp(): void
    {
        parent::setUp();
        $this->image = Image::factory()->create();
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/load-balancer-specs',
            [
                'name' => 'small-test',
                'description' => 'Description Test',
                'node_count' => 5,
                'cpu' => 5,
                'ram' => 5,
                'hdd' => 5,
                'iops' => 5,
                'image_id' => $this->image->id
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(201);
        $this->assertDatabaseHas(
            'load_balancer_specifications',
            [
                'name' => 'small-test',
                'description' => 'Description Test',
                'node_count' => 5,
                'cpu' => 5,
                'ram' => 5,
                'hdd' => 5,
                'iops' => 5,
                'image_id' => $this->image->id
            ],
            'ecloud'
        );
    }

    public function testNonAdminIsDenied()
    {
        $data = [
            'name' => 'small-test',
            'node_count' => 5,
            'cpu' => 5,
            'ram' => 5,
            'hdd' => 5,
            'iops' => 5,
            'image_id' => 'img-aaaaaaaa'
        ];
        $this->post(
            '/v2/load-balancer-specs',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }
}
