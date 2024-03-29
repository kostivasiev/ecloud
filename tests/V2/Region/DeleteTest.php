<?php

namespace Tests\V2\Region;

use App\Models\V2\Region;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected $faker;
    private Consumer $consumer;

    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $this->consumer->setIsAdmin(true);
    }

    public function testNotAdminIsDenied()
    {
        $this->delete('/v2/regions/' . $this->region()->id)
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->be($this->consumer);
        $this->delete('/v2/regions/' . $this->faker->uuid)
            ->assertJsonFragment([
                'title' => 'Not found',
                'detail' => 'No Region with that ID was found',
                'status' => 404,
            ])
            ->assertStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->availabilityZone()->delete();
        $this->be($this->consumer);
        $this->delete('/v2/regions/' . $this->region()->id)
            ->assertStatus(204);
        $this->region()->refresh();
        $this->assertNotNull($this->region()->deleted_at);
    }

    public function testDeleteFailsWhenChildPresent()
    {
        $this->be($this->consumer);
        $this->delete('/v2/regions/' . $this->region()->id)
            ->assertJsonFragment(
                [
                    'title' => 'Precondition Failed',
                    'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->availabilityZone()->id,
                ]
            )->assertStatus(412);
    }

}
