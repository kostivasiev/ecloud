<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use App\Providers\EncryptionServiceProvider;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function testValidDataSucceeds()
    {
        $credential = factory(Credential::class)->create();

        $this->patch('/v2/credentials/' . $credential->id, [
            'resource_id' => 'abc-abc123',
            'host' => 'https://0.0.0.0',
            'username' => 'username',
            'password' => 'doesnt even matter', // See below
            'port' => 8080
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase(
            'credentials',
            [
                'id' => $credential->id,
                'resource_id' => 'abc-abc123',
                'host' => 'https://0.0.0.0',
                'username' => 'username',
                'port' => 8080
            ],
            'ecloud'
        )->assertResponseStatus(200);

        $resource = Credential::find($credential->id);

        // TODO - Improve this test
        // This is a bit pointless since we mock the response in setup() but it does prove it's been saved..
        $this->assertEquals($resource->password, 'somepassword');
    }
}
