<?php
namespace Tests\V2\VpnProfiles;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected array $data;

    public function setUp(): void
    {
        parent::setUp();
        $this->data = [
            'name' => 'Vpn Test Profile',
            'ike_version' => 'ike_v2',
            'encryption_algorithm' => [
                'aes 128',
                'aes 256',
            ],
            'digest_algorithm' => [
                'sha2 256',
            ],
            'diffie_hellman' => [
                'group 14',
            ],
        ];
    }

    public function testCreateResourceNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->post('/v2/vpn-profiles', $this->data)
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ])->assertStatus(401);
    }

    public function testCreateResource()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $transformed = $this->data;
        $transformed['encryption_algorithm'] = implode(',', $this->data['encryption_algorithm']);
        $transformed['digest_algorithm'] = implode(',', $this->data['digest_algorithm']);
        $transformed['diffie_hellman'] = implode(',', $this->data['diffie_hellman']);

        $this->post('/v2/vpn-profiles', $this->data)
            ->assertStatus(201);
        $this->assertDatabaseHas('vpn_profiles', $transformed, 'ecloud');
    }

    public function testCreateResourceInvalidData()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);

        $this->post(
            '/v2/vpn-profiles',
            [
                'name' => 'Vpn Test Profile',
                'ike_version' => 'INVALID',
                'encryption_algorithm' => [
                    'INVALID',
                ],
                'digest_algorithm' => [
                    'INVALID',
                ],
                'diffie_hellman' => [
                    'INVALID',
                ],
            ]
        )
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The selected ike version is invalid',
                'source' => 'ike_version',
            ])
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The selected encryption_algorithm.0 is invalid',
                'source' => 'encryption_algorithm.0',
            ])
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The selected digest_algorithm.0 is invalid',
                'source' => 'digest_algorithm.0',
            ])
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The selected diffie_hellman.0 is invalid',
                'source' => 'diffie_hellman.0',
            ])
            ->assertStatus(422);
    }

}
