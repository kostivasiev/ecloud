<?php
namespace Tests\unit\Jobs\Nsx\Host;

use App\Jobs\Nsx\Host\RemoveFromNsGroups;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RemoveFromNsGroupsTest extends TestCase
{
    protected $host;

    // TODO: Better test coverage

    public function setUp(): void
    {
        parent::setUp();

        $this->host = Host::withoutEvents(function () {
            $hostGroup = factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
            ]);
            return factory(Host::class)->create([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $hostGroup->id,
                'mac_address' => 'aa:bb:cc:dd:ee:ff',
            ]);
        });
    }

    public function testEmptyMacAddressSkips()
    {
        $this->host->mac_address = '';
        $this->host->saveQuietly();

        Event::fake([JobFailed::class]);

        dispatch(new RemoveFromNsGroups($this->host));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testRemoveFromNsGroups()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/hostgroup/hg-test/host/aa:bb:cc:dd:ee:ff'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'name' => '172.19.0.38',
                    'connectionState' => 'connected',
                    'powerState' => 'poweredOn',
                    'macAddress' => '00:25:B5:C0:A0:1B',
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->with('/api/v1/search/query?query=resource_type:TransportNode%20AND%20display_name:172.19.0.38')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'ebe3adf5-c920-4442-b7fb-573e28d543c1'
                        ],
                    ],
                    'result_count' => 1,
                    'cursor' => 1
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->with('/api/v1/search/query?query=resource_type:NSGroup%20AND%20members.value:ebe3adf5-c920-4442-b7fb-573e28d543c1')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'fc23f2fc-6a45-4dc7-90d2-64db975c1527',
                            'members' => [
                                [
                                    "op" => "EQUALS",
                                    "target_resource"=> [
                                        "is_valid" => true
                                    ],
                                    "target_type"=> "TransportNode",
                                    "resource_type"=> "NSGroupSimpleExpression",
                                    "value"=> "d3db3ca8-6e24-45d6-be93-36591adbf9c4",
                                    "target_property"=> "id"
                                ],
                                [
                                    "op" => "EQUALS",
                                    "target_resource"=> [
                                            "is_valid" => true
                                    ],
                                    "target_type"=> "TransportNode",
                                    "resource_type"=> "NSGroupSimpleExpression",
                                    "value"=> "ebe3adf5-c920-4442-b7fb-573e28d543c1",
                                    "target_property"=> "id"
                                ]
                            ],
                            '_revision' => 9,
                            'effective_member_count' =>  10,
                            'member_count' =>  10
                        ],
                    ],
                    "result_count" => 1,
                    "cursor" => 1
                ]));
            });

       $this->nsxServiceMock()->expects('put')
            ->withArgs([
                '/api/v1/ns-groups/fc23f2fc-6a45-4dc7-90d2-64db975c1527',
                [
                    'json' => (object)[
                        'id' => 'fc23f2fc-6a45-4dc7-90d2-64db975c1527',
                        'members' => [
                            (object)[
                                "op" => "EQUALS",
                                "target_resource"=> (object)[
                                    "is_valid" => true
                                ],
                                "target_type"=> "TransportNode",
                                "resource_type"=> "NSGroupSimpleExpression",
                                "value"=> "d3db3ca8-6e24-45d6-be93-36591adbf9c4",
                                "target_property"=> "id"
                            ]
                        ],
                        '_revision' => 9,
                        'member_count' =>  9
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new RemoveFromNsGroups($this->host));

        Event::assertNotDispatched(JobFailed::class);
    }
}