<?php
namespace Tests\unit\Jobs\Nsx\HostGroup;

use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTransportNodeProfileTest extends TestCase
{
    protected $hostGroup;
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->hostGroup = factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->hostGroup);
            $this->task->save();
        });
    }

    public function testNoTransportNodeProfileFoundSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $this->hostGroup->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0,
                    'results' => []
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeleteTransportNodeProfile($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testTransportNodeCollectionNotFoundSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $this->hostGroup->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'id' => 'testtransportnodeprofile'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/api/v1/search/query?query=resource_type:TransportNodeCollection%20AND%20transport_node_profile_id:testtransportnodeprofile'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0,
                    'results' => []
                ]));
            });


        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/api/v1/transport-node-profiles/testtransportnodeprofile'
            ]);

        Event::fake([JobFailed::class]);

        dispatch(new DeleteTransportNodeProfile($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testTransportNodeCollectionFoundSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-' . $this->hostGroup->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'id' => 'testtransportnodeprofile'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/api/v1/search/query?query=resource_type:TransportNodeCollection%20AND%20transport_node_profile_id:testtransportnodeprofile'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'id' => 'testtransportnodecollection'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/api/v1/transport-node-collections/testtransportnodecollection'
            ]);

        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/api/v1/transport-node-profiles/testtransportnodeprofile'
            ]);

        Event::fake([JobFailed::class]);

        dispatch(new DeleteTransportNodeProfile($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}