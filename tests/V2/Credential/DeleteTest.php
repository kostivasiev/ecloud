<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use App\Providers\EncryptionServiceProvider;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Credential */
    private $credential;

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function() use ($mockEncryptionServiceProvider) {
            return $mockEncryptionServiceProvider;
        });
        $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');

        $this->credential = factory(Credential::class)->create();
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/credentials/' . $this->credential->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $resource = Credential::withTrashed()->findOrFail($this->credential->getKey());
        $this->assertNotNull($resource->deleted_at);
    }
}
