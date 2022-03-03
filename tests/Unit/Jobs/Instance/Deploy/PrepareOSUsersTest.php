<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\PrepareOsUsers;
use App\Models\V2\SshKeyPair;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PrepareOSUsersTest extends TestCase
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

        $this->instanceModel()->credentials()->create([
            'id' => 'cred-test',
            'username' => 'root',
        ]);
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
            ->withSomeOfArgs('/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/admingroup');

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'graphiterack';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'ukfastsupport';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetPublicKeys'][0] == 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PrepareOsUsers($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntSetSSHKeyWhenNotSpecifiedInDeployData()
    {
        $this->kingpinServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/admingroup');

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'graphiterack';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'ukfastsupport';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PrepareOsUsers($this->instanceModel()));

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

        $this->kingpinServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/admingroup');

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'graphiterack';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'ukfastsupport';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PrepareOsUsers($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
