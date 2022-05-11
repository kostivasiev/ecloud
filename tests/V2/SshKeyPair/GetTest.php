<?php

namespace Tests\V2\SshKeyPair;

use App\Models\V2\SshKeyPair;
use Tests\TestCase;

class GetTest extends TestCase
{
    /** @var SshKeyPair */
    private $keypair;

    public function setUp(): void
    {
        parent::setUp();
        $this->keypair = new SshKeyPair([
            'reseller_id' => 1,
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==',
        ]);
        $this->keypair->save();
    }

    public function testNoPermsIsDenied()
    {
        $this->get('/v2/ssh-key-pairs')
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])->assertStatus(401);
    }

    public function testGetCollectionAdmin()
    {
        $this->get('/v2/ssh-key-pairs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->keypair->id,
            'name' => $this->keypair->name,
        ])->assertStatus(200);
    }

    public function testGetCollectionResellerScopeCanSeeSshKeyPair()
    {
        $this->get('/v2/ssh-key-pairs', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->keypair->id,
        ])->assertStatus(200);
    }

    public function testGetCollectionResellerScopeCanNotSeeSshKeyPair()
    {
        $this->get('/v2/ssh-key-pairs', [
            'X-consumer-custom-id' => '2-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonMissing([
            'id' => $this->keypair->id,
        ])->assertStatus(200);
    }


    public function testGetCollectionAdminResellerScope()
    {
        $keypair2 = new SshKeyPair([
            'reseller_id' => 2,
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==',
        ]);
        $keypair2->save();

        $this->get('/v2/ssh-key-pairs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
            'X-Reseller-Id' => 1
        ])->assertJsonMissing([
            'id' => $keypair2->id,
        ])->assertJsonFragment([
            'id' => $this->keypair->id,
        ])->assertStatus(200);
    }

    public function testNonMatchingResellerIdFails()
    {
        $this->keypair->reseller_id = 3;
        $this->keypair->save();

        $this->get('/v2/ssh-key-pairs/' . $this->keypair->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Not found',
            'detail' => 'No Ssh Key Pair with that ID was found',
            'status' => 404,
        ])->assertStatus(404);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/ssh-key-pairs/' . $this->keypair->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->keypair->id,
            'name' => $this->keypair->name,
        ])->assertStatus(200);
    }
}
