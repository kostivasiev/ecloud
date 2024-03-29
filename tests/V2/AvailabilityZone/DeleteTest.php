<?php

namespace Tests\V2\AvailabilityZone;

use Faker\Factory as Faker;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected $faker;
    private Consumer $consumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $this->consumer->setIsAdmin(true);
    }

    public function testNonAdminIsDenied()
    {
        $this->consumer->setIsAdmin(false);
        $this->be($this->consumer);
        $this->delete(
            '/v2/availability-zones/' . $this->availabilityZone()->id
        )->assertJsonFragment([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->be($this->consumer);
        $this->delete('/v2/availability-zones/' . $this->faker->uuid)
            ->assertJsonFragment([
                'title' => 'Not found',
                'detail' => 'No Availability Zone with that ID was found',
                'status' => 404,
            ])
            ->assertStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->be($this->consumer);
        $this->delete('/v2/availability-zones/' . $this->availabilityZone()->id)
            ->assertStatus(204);
        $this->availabilityZone()->refresh();
        $this->assertNotNull($this->availabilityZone()->deleted_at);
    }

    public function testDeleteFailsIfChildPresent()
    {
        $this->router();
        $this->be($this->consumer);
        $this->delete('/v2/availability-zones/' . $this->availabilityZone()->id)
            ->assertJsonFragment(
                [
                    'title' => 'Precondition Failed',
                    'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->router()->id,
                ]
            )->assertStatus(412);
    }

}
