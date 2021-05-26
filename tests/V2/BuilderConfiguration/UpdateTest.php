<?php
namespace Tests\V2\BuilderConfiguration;

use App\Models\V2\BuilderConfiguration;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected BuilderConfiguration $builderConfiguration;

    public function setUp(): void
    {
        parent::setUp();
        $this->builderConfiguration = factory(BuilderConfiguration::class)->create();
    }

    public function testUpdateAdminSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->patch(
            '/v2/builder-configurations/' . $this->builderConfiguration->id,
            [
                'reseller_id' => 2,
                'employee_id' => 2,
                'data' => '{"foo": "bar"}'
            ]
        )->seeInDatabase(
            'builder_configurations',
            [
                'reseller_id' => 2,
                'employee_id' => 2,
                'data' => '{"foo": "bar"}'
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testUpdateNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->patch('/v2/builder-configurations/' . $this->image()->id, [])->assertResponseStatus(401);
    }
}
