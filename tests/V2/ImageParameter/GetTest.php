<?php

namespace Tests\V2\ImageParameter;

use App\Models\V2\ImageParameter;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected ImageParameter $imageParameter;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageParameter();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testIndexAdminSucceeds()
    {
        $this->get('/v2/image-parameters')
            ->assertJsonFragment([
                'id' => 'iparam-test',
                'name' => 'Test Image Parameter',
                'key' => 'Username',
                'type' => 'String',
                'description' => 'Lorem ipsum',
                'required' => true,
                'validation_rule' => '/\w+/',
            ])
            ->assertStatus(200);
    }

    public function testIndexNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/image-parameters')->assertStatus(401);
    }

    public function testShowAdminSucceeds()
    {
        $this->get('/v2/image-parameters/' . $this->imageParameter()->id)
            ->assertJsonFragment([
                'id' => 'iparam-test',
                'name' => 'Test Image Parameter',
                'key' => 'Username',
                'type' => 'String',
                'description' => 'Lorem ipsum',
                'required' => true,
                'validation_rule' => '/\w+/',
            ])
            ->assertStatus(200);
    }

    public function testShowNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/image-parameters/' . $this->imageParameter()->id)->assertStatus(401);
    }
}