<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use App\Providers\EncryptionServiceProvider;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Credential */
    private $credential;

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            return $mockEncryptionServiceProvider;
        });
        $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
        $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('newPass');

        $this->credential = factory(Credential::class)->create();

    }

    public function testValidDataSucceeds()
    {
        $this->patch(
            '/v2/credentials/' . $this->credential->getKey(),
            [
                'resource_id' => 'abc-abc123',
                'host' => 'https://0.0.0.0',
                'user' => 'username',
                'password' => 'newPass',
                'port' => 8080
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'credentials',
                [
                    'id' => $this->credential->getKey(),
                    'resource_id' => 'abc-abc123',
                    'host' => 'https://0.0.0.0',
                    'user' => 'username',
                    'port' => 8080
                ],
                'ecloud'
            )->assertResponseStatus(200);

        $resource = Credential::find($this->credential->getKey());
        $this->assertEquals($resource->password, 'newPass');
    }
}
