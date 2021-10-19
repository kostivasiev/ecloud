<?php

namespace Tests\V2\Router;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class ManagementRouterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->router()->setAttribute('is_management', true)->save();
    }

    public function testGetManagedRouterNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->get('/v2/routers/' . $this->router()->id)
            ->assertResponseStatus(404);
    }

    public function testGetManagedRouterAdminPasses()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/routers/' . $this->router()->id)
            ->seeJson([
                'is_management' => true,
                'is_hidden' => true
            ])
            ->assertResponseStatus(200);
    }
}
