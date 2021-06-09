<?php
namespace Tests\V2\BuilderConfiguration;

use App\Models\V2\BuilderConfiguration;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected BuilderConfiguration $builderConfiguration;

    public function setUp(): void
    {
        parent::setUp();
        $this->builderConfiguration = factory(BuilderConfiguration::class)->create();
    }

    public function testAdminDeleteSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->delete('/v2/builder-configurations/' . $this->builderConfiguration->id)->assertResponseStatus(204);
    }

    public function testNotAdminDeleteFails()
    {
        $this->delete('/v2/builder-configurations/' . $this->builderConfiguration->id)->assertResponseStatus(401);
    }
}