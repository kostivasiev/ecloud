<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\FloatingIp;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\Mocks\Traits\NetworkingApio;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations, NetworkingApio;

    protected \Faker\Generator $faker;
    protected $floatingIp;

    public function setUp(): void
    {
        parent::setUp();
        $this->networkingApioSetup();
        $this->faker = Faker::create();
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-test',
            ]);
        });
    }

    public function testNoPermsIsDenied()
    {
        $this->delete(
            '/v2/floating-ips/' . $this->floatingIp->id,
            [],
            []
        )
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/floating-ips/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Floating Ip with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/floating-ips/' . $this->floatingIp->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(204);
        $resource = FloatingIp::withTrashed()->findOrFail($this->floatingIp->id);
        $this->assertNotNull($resource->deleted_at);
    }
}
