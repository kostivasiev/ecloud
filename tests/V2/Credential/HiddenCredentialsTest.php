<?php

namespace Tests\V2\Credential;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Tests\TestCase;

class HiddenCredentialsTest extends TestCase
{
    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected AvailabilityZone $availabilityZone;
    protected Credential $credentials;
    protected Instance $instance;
    protected Region $region;
    protected Vpc $vpc;
    protected Image $image;


    public function setUp(): void
    {
        parent::setUp();

        Instance::withoutEvents(function() {
            $this->instance = new Instance(['id' => 'abc-abc132']);
        });

        $this->credentials = factory(Credential::class)->create([
            'resource_id' => 'abc-abc132',
            'host' => 'https://127.0.0.1',
            'username' => 'someuser',
            'password' => 'somepassword',
            'port' => 8080,
            'is_hidden' => true,
        ]);
    }

    public function testAdminCanSetHiddenFlag()
    {
        $instance = null;
        $this->post(
            '/v2/credentials',
            [
                'resource_id' => $this->instance()->id,
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'password' => 'somepassword',
                'port' => 8080,
                'is_hidden' => true,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'credentials',
            [
                'resource_id' => $this->instance()->id,
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'port' => 8080,
                'is_hidden' => 1,
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testAdminCanSeeHiddenCredentials()
    {
        $this->get(
            '/v2/credentials/' . $this->credentials->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'is_hidden' => true,
        ])->assertResponseStatus(200);
    }

    public function testUserCannotSeeHiddenFlag()
    {
        $this->instance()->deployed = true;
        $this->instance()->saveQuietly();
        $this->get(
            '/v2/instances/' . $this->instance()->id . '/credentials',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->dontSeeJson([
            'is_hidden' => true,
        ])->assertResponseStatus(200);
    }
}
