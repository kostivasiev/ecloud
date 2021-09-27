<?php

namespace Tests\V2\LoadBalancerSpecification;

use Tests\TestCase;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
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
                'image_id' => 'img-aaaaaaaa'
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
                'iops' => 5,
                'image_id' => 'img-aaaaaaaa'
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
