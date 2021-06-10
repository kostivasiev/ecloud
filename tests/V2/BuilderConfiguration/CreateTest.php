<?php

namespace Tests\V2\BuilderConfiguration;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function testStoreAdminIsSuccess()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'reseller_id' => 1,
            'employee_id' => 1,
            'data' => '{"test": "test"}'
        ];

        $this->post('/v2/builder-configurations', $data)
            ->seeInDatabase('builder_configurations', $data, 'ecloud')
            ->assertResponseStatus(201);
    }


    public function testInvalidJsonFails()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'reseller_id' => 1,
            'employee_id' => 1,
            'data' => "INVALID JSON"
        ];

        $this->post('/v2/builder-configurations', $data)
            ->notSeeInDatabase('builder_configurations', $data, 'ecloud')
            ->assertResponseStatus(422);
    }

    public function testStoreNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/images', [])->assertResponseStatus(401);
    }
}
