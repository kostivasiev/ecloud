<?php
namespace Tests\unit\Jobs\Nsx\Host;

use App\Jobs\Nsx\Host\RemoveFromNsGroups;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RemoveFromNsGroupsTest extends TestCase
{
    public function testRemoveFromNsGroups()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v2/compute/' . $this->availabilityZone()->ucs_compute_name .
                '/vpc/' . $this->vpc()->id . '/host/h-test'
            )->andReturnUsing(function () {
                return new Response('200', [], json_encode([
                    'specification' => 'DUAL-4208--32GB',
                    'name' => 'DUAL-4208--32GB',
                    'interfaces' => [
                        [
                            'name' => 'eth0',
                            'address' => '00:25:B5:C0:A0:1B',
                            'type' => 'vNIC'
                        ]
                    ]
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/hostgroup/hg-test/host/00:25:B5:C0:A0:1B'])
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
                        'effective_member_count' =>  9,
                        'member_count' =>  9
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });


        Event::fake([JobFailed::class]);

        dispatch(new RemoveFromNsGroups($this->host()));

        Event::assertNotDispatched(JobFailed::class);
    }
}