<?php

namespace Tests\unit\Jobs\Network;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\PrepareOsDisk;
use App\Jobs\Instance\Deploy\PrepareOsUsers;
use App\Jobs\Network\Deploy;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Models\V2\Instance;
use App\Models\V2\Router;
use App\Models\V2\SshKeyPair;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
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

        $this->instance()->credentials()->create([
            'id' => 'cred-test',
            'username' => 'root',
        ]);
    }

    public function testsSetsSSHKeysWhenSpecifiedInDeployData()
    {
        $this->instance()->deploy_data =[
            'ssh_key_pair_ids' => [
                $this->keypair
            ]
        ];
        $this->instance()->saveQuietly();

        $this->kingpinServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/admingroup');

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'graphiterack';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'ukfastsupport';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/user' &&
                    $args['json']['targetPublicKeys'][0] == 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PrepareOsUsers($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntSetSSHKeyWhenNotSpecifiedInDeployData()
    {
        $this->kingpinServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/admingroup');

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'graphiterack';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'ukfastsupport';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PrepareOsUsers($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntFailWhenSSHKeyDoesntExist()
    {
        $this->instance()->deploy_data =[
            'ssh_key_pair_ids' => [
                'invalid'
            ]
        ];
        $this->instance()->saveQuietly();

        $this->kingpinServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/admingroup');

        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'graphiterack';
            });
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(function($uri, $args) {
                return $uri == '/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/guest/linux/user' &&
                    $args['json']['targetUsername'] == 'ukfastsupport';
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new PrepareOsUsers($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
