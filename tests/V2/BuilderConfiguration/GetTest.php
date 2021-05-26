<?php

namespace Tests\V2\BuilderConfiguration;

use App\Models\V2\BuilderConfiguration;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected BuilderConfiguration $builderConfiguration;

    public function setUp(): void
    {
        parent::setUp();
        $this->builderConfiguration = factory(BuilderConfiguration::class)->create();
    }

    public function testIndexAdminSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/builder-configurations')
            ->seeJson([
                'reseller_id' => 1,
                'employee_id' => 1,
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/builder-configurations')->assertResponseStatus(401);
    }

    public function testShowAdminSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/builder-configurations/' . $this->builderConfiguration->id)
            ->seeJson([
                'reseller_id' => 1,
                'employee_id' => 1,
            ])
            ->assertResponseStatus(200);
    }

    public function testShowNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/builder-configurations/' . $this->builderConfiguration->id)->assertResponseStatus(401);
    }

    public function testGetDataAdminSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/builder-configurations/' . $this->builderConfiguration->id. '/data')
            ->seeJson([
                'foo' => 'bar'
            ])
            ->assertResponseStatus(200);
    }

    public function testGetDataNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/builder-configurations/' . $this->builderConfiguration->id. '/data')->assertResponseStatus(401);
    }
}
