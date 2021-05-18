<?php

namespace Tests\V2\ImageParameter;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testStoreAdminIsSuccess()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'name' => 'Test Image Parameter',
            'key' => 'Username',
            'type' => 'String',
            'description' => 'Lorem ipsum',
            'required' => true,
            'validation_rule' => '/\w+/',
            'image_id' => $this->image()->id
        ];

        $this->post('/v2/image-parameters', $data)
           ->seeInDatabase(
                'image_parameters',
                [
                    'name' => 'Test Image Parameter',
                    'image_id' => $this->image()->id,
                    'key' => 'Username',
                    'type' => 'String',
                    'description' => 'Lorem ipsum',
                    'required' => true,
                    'validation_rule' => '/\w+/',
                ],
                'ecloud')
            ->assertResponseStatus(201);
    }

    public function testStoreNotAdminFails()
    {
        $this->post('/v2/images', [])->assertResponseStatus(401);
    }
}
