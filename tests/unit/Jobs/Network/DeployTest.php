<?php

namespace Tests\unit\Jobs\Network;

use App\Jobs\Network\Deploy;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->dhcp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->network());
            $this->task->save();
        });
    }

    public function testSucceeds()
    {
        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id,
                [
                    'json' => [
                        'resource_type' => 'Segment',
                        'subnets' => [
                            [
                                'gateway_address' => '10.0.0.1/24',
                                'dhcp_config' => [
                                    'resource_type' => 'SegmentDhcpV4Config',
                                    'server_address' => '10.0.0.2/24',
                                    'lease_time' => config('defaults.network.subnets.dhcp_config.lease_time'),
                                    'dns_servers' => config('defaults.network.subnets.dhcp_config.dns_servers')
                                ]
                            ]
                        ],
                        'domain_name' => config('defaults.network.domain_name'),
                        'dhcp_config_path' => '/infra/dhcp-server-configs/' . $this->dhcp()->id,
                        'advanced_config' => [
                            'connectivity' => 'ON'
                        ],
                        'tags' => [
                            [
                                'scope' => config('defaults.tag.scope'),
                                'tag' => $this->network()->router->vpc->id
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoDhcpServerFails()
    {
        $this->network()->router->availability_zone_id = 'az-invalid';
        $this->network()->router->saveQuietly();

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Unable to locate VPC DHCP server for router availability zone';
        });
    }
}
