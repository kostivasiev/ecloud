<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    private Consumer $consumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $this->consumer->setIsAdmin(true);
    }

    public function testSuccessfulDelete()
    {
        $this->be($this->consumer);

        $this->delete('/v2/router-throughputs/' . $this->routerThroughput()->id)
            ->assertResponseStatus(204);

        $this->routerThroughput()->refresh();
        $this->assertNotNull($this->routerThroughput()->deleted_at);
    }

    public function testFailDeleteIfRouterAttached()
    {
        $this->be($this->consumer);
        $this->router();

        $this->delete('/v2/router-throughputs/' . $this->routerThroughput()->id)
            ->seeJson(
                [
                    'title' => 'Precondition Failed',
                    'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->router()->id,
                ]
            )->assertResponseStatus(412);
    }
}
