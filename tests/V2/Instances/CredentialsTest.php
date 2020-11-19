<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialsTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $vpc;

    protected $instance;

    protected $credential;

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            return $mockEncryptionServiceProvider;
        });
        $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
        $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');

        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester VPC',
            'region_id' => $region->getKey(),
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $availabilityZone->getKey(),
        ]);

        $this->credential = factory(Credential::class)->create([
            'resource_id' => $this->instance->getKey()
        ]);
    }

    public function testGetCredentials()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey() . '/credentials',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson(
                collect($this->credential)
                    ->except(['created_at', 'updated_at'])
                    ->toArray()
            )
            ->assertResponseStatus(200);
    }
}
