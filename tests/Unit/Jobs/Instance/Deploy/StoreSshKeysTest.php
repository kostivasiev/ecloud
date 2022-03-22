<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\StoreSshKeys;
use App\Models\V2\Credential;
use App\Models\V2\SshKeyPair;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StoreSshKeysTest extends TestCase
{
    private $keypair;

    public function setUp(): void
    {
        parent::setUp();
        $this->keypair = new SshKeyPair([
            'reseller_id' => 1,
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==',
        ]);
        $this->keypair->save();

        $this->credential = $this->instanceModel()->credentials()->save(
            Credential::factory()->create([
                'username' => config('instance.guest_admin_username.linux'),
            ])
        );
    }

    public function testsSetsSSHKeysWhenSpecifiedInDeployData()
    {
        $this->instanceModel()->deploy_data =[
            'ssh_key_pair_ids' => [
                $this->keypair->id
            ]
        ];
        $this->instanceModel()->saveQuietly();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetPublicKeys'][0] == 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new StoreSshKeys($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntSetSSHKeyWhenNotSpecifiedInDeployData()
    {
        $this->kingpinServiceMock()->shouldNotReceive('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetPublicKeys'][0] == 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new StoreSshKeys($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntFailWhenSSHKeyDoesntExist()
    {
        $this->instanceModel()->deploy_data =[
            'ssh_key_pair_ids' => [
                'invalid'
            ]
        ];
        $this->instanceModel()->saveQuietly();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new StoreSshKeys($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
