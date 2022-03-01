<?php

namespace Tests\V2\Instances;

use App\Models\V2\InstanceSoftware;
use App\Models\V2\Software;
use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class SoftwareTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        (new SoftwareSeeder())->run();
    }

    public function testGetInstanceSoftware()
    {
        $instanceSoftware = InstanceSoftware::factory()->make();
        $instanceSoftware->instance()->associate($this->instance());
        $instanceSoftware->software()->associate(Software::find('soft-aaaaaaaa'));
        $instanceSoftware->save();

        $this->get(
            '/v2/instances/' . $this->instance()->id . '/software')
            ->seeJson([
                'id' => 'soft-aaaaaaaa',
                'name' => 'Test Software',
                'platform' => 'Linux',
            ])
            ->assertResponseStatus(200);
    }
}
