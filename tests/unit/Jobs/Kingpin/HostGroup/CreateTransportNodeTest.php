<?php

namespace Tests\unit\Jobs\Kingpin\HostGroup;

use App\Jobs\Nsx\HostGroup\CreateTransportNodeProfile;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateTransportNodeTest extends TestCase
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
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->hostGroup);
            $this->task->save();
        });
    }

    public function testNoTransportNodeProfiles()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateTransportNodeProfile($this->task));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to get TransportNodeProfiles';
        });
    }

    public function testTransportNodeProfileNameExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'display_name' => 'tnp-' . $this->hostGroup->id,
                        ]
                    ]
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateTransportNodeProfile($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoNetworkSwitchDetails()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/network/switch')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateTransportNodeProfile($this->task));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to get NetworkSwitch';
        });
    }

    public function testNoTransportZones()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/network/switch')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'b4d001c8-f3a9-47b9-b904-78ce9fd6c4d6',
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateTransportNodeProfile($this->task));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to get TransportZones';
        });
    }

    public function testNoUplinkHostSwitchProfiles()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/network/switch')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'b4d001c8-f3a9-47b9-b904-78ce9fd6c4d6',
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '42d981bc-22a0-42fb-b815-42edaf06b5f9',
                            'transport_zone_profile_ids' => [
                                'ec94aaa1-a50d-4eb8-8fa9-128946efc76a',
                                '7ed25dd0-a403-44bd-95a8-95d71b266526',
                            ]
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateTransportNodeProfile($this->task));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to get UplinkHostSwitchProfiles';
        });
    }

    public function testNoVtepIpPools()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/network/switch')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'b4d001c8-f3a9-47b9-b904-78ce9fd6c4d6',
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '42d981bc-22a0-42fb-b815-42edaf06b5f9',
                            'transport_zone_profile_ids' => [
                                'ec94aaa1-a50d-4eb8-8fa9-128946efc76a',
                                '7ed25dd0-a403-44bd-95a8-95d71b266526',
                            ]
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '6b3c18e8-2c21-4dd3-bb13-8c589ce2fd85'
                        ]
                    ]
                ]));
            });


        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:IpPool%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-vtep-ip-pool')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateTransportNodeProfile($this->task));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to get VtepIpPools';
        });
    }

    public function testCreateSuccessful()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/network/switch')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'b4d001c8-f3a9-47b9-b904-78ce9fd6c4d6',
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '42d981bc-22a0-42fb-b815-42edaf06b5f9',
                            'transport_zone_profile_ids' => [
                                'ec94aaa1-a50d-4eb8-8fa9-128946efc76a',
                                '7ed25dd0-a403-44bd-95a8-95d71b266526',
                            ]
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '6b3c18e8-2c21-4dd3-bb13-8c589ce2fd85'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:IpPool%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-vtep-ip-pool')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'da1c8d8d-67ce-4e62-815a-0e940265682c'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new CreateTransportNodeProfile($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}