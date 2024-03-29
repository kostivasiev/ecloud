<?php

namespace Tests\V2\LoadBalancerSpecification;

use App\Models\V2\Image;
use App\Models\V2\LoadBalancerSpecification;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected $image;
    protected $loadBalancerSpecification;

    public function setUp(): void
    {
        parent::setUp();

        $this->image = Image::factory()->create();
        $this->loadBalancerSpecification = LoadBalancerSpecification::factory()->create([
            "image_id" => $this->image->id
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->delete(
            '/v2/load-balancer-specs/' . $this->loadBalancerSpecification->id,
            [],
            []
        )
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/load-balancer-specs/NOT_FOUND',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Not found',
                'detail' => 'No Load Balancer Specification with that ID was found',
                'status' => 404,
            ])
            ->assertStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/load-balancer-specs/' . $this->loadBalancerSpecification->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertStatus(204);
    }
}
