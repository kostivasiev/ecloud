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
        $this->image = factory(Image::class)->create();
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/load-balancer-specs',
            [
                'name' => 'small-test',
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
        )  ->seeInDatabase(
            'load_balancer_specifications',
            [
                'name' => 'small-test',
                'node_count' => 5,
                'cpu' => 5,
                'ram' => 5,
                'hdd' => 5,
                'iops' => 5
            ],
            'ecloud'
        )
            ->assertResponseStatus(201);
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
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }
}
