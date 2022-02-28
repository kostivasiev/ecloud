<?php

namespace Tests\unit\Console\Commands\Router;

use App\Console\Commands\Router\AdvertiseSegmentsService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AdvertiseSegmentsServiceTest extends TestCase
{
    protected $mock;
    protected array $advertisementTypesWithout = [
        "TIER1_STATIC_ROUTES",
        "TIER1_NAT",
        "TIER1_IPSEC_LOCAL_ENDPOINT"
    ];
    protected array $advertisementTypesWith = [
        "TIER1_STATIC_ROUTES",
        "TIER1_NAT",
        "TIER1_IPSEC_LOCAL_ENDPOINT",
        "TIER1_CONNECTED"
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->mock = \Mockery::mock(AdvertiseSegmentsService::class)->makePartial();
    }

    public function testGetAdvertisedTypesNoAvailabilityZone()
    {
        Model::withoutEvents(function () {
            $this->availabilityZone()->delete();
        });
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->getAdvertisedTypes($this->router());

        $this->assertEquals(
            $this->router()->id . ' : Availability Zone `' . $this->router()->availability_zone_id . '` not found',
            $message
        );
    }

    public function testGetAdvertisedTypesNoTier1()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id)
            ->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->getAdvertisedTypes($this->router());
        $this->assertEquals($this->router()->id . ' : Not Found', $message);
    }

    public function testGetAdvertisedTypesNoRouter()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'id' => 'rtr-wrongid',
                ]));
            });
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->getAdvertisedTypes($this->router());
        $this->assertEquals($this->router()->id . ' : No results found.', $message);
    }

    public function testGetAdvertisedTypesNoTier0()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'id' => $this->router()->id,
                ]));
            });
        $this->mock->allows('checkT0Connection')
            ->withAnyArgs()
            ->andReturnFalse();
        $this->assertFalse($this->mock->getAdvertisedTypes($this->router()));
    }

    public function testGetAdvertisedTypes()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'id' => $this->router()->id,
                    'tier0_path' => '/infra/tier-0s/T0',
                    'route_advertisement_types' => $this->advertisementTypesWithout,
                ]));
            });
        $this->mock->allows('checkT0Connection')
            ->withAnyArgs()
            ->andReturnTrue();
        $response = $this->mock->getAdvertisedTypes($this->router());
        $this->assertEquals(Arr::flatten($this->advertisementTypesWithout), Arr::flatten($response));
    }

    public function testCheckT0ConnectionNoVpc()
    {
        Model::withoutEvents(function () {
            $this->vpc()->delete();
        });
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->assertFalse($this->mock->checkT0Connection($this->router(), []));

        $this->assertEquals($this->router()->id . ' : VPC `' . $this->router()->vpc_id . '` not found', $message);
    }

    public function testCheckT0ConnectionNoResponse()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20'.
                'tags.scope:ukfast%20AND%20tags.tag:az-admin'
            )->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->assertFalse($this->mock->checkT0Connection($this->router(), []));

        $this->assertEquals($this->router()->id . ' : Not Found', $message);
    }

    public function testCheckT0ConnectionNoResults()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20'.
                'tags.scope:ukfast%20AND%20tags.tag:az-admin'
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0,
                ]));
            });
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->assertFalse($this->mock->checkT0Connection($this->router(), []));

        $this->assertEquals($this->router()->id . ' : No tagged T0 could be found', $message);
    }

    public function testCheckT0ConnectionPathMismatch()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20'.
                'tags.scope:ukfast%20AND%20tags.tag:az-admin'
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'path' => '/infra/tier-0s/T9999',
                        ]
                    ]
                ]));
            });
        $response = json_decode(json_encode([
            'tier0_path' => '/infra/tier-0s/T0',
        ]));
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->assertFalse($this->mock->checkT0Connection($this->router(), $response));

        $this->assertEquals($this->router()->id . ' : is not connected to the correct T0 path', $message);
    }

    public function testCheckT0Connection()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                '/policy/api/v1/search/query?query=resource_type:Tier0%20AND%20'.
                'tags.scope:ukfast%20AND%20tags.tag:az-admin'
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'path' => '/infra/tier-0s/T0',
                        ]
                    ]
                ]));
            });
        $response = json_decode(json_encode([
            'tier0_path' => '/infra/tier-0s/T0',
        ]));
        $this->assertTrue($this->mock->checkT0Connection($this->router(), $response));
    }

    public function testUpdateRouteAdvertisementTypesTestRun()
    {
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('option')->with('test-run')->andReturnTrue();
        $this->assertTrue($this->mock->updateRouteAdvertisementTypes($this->router(), $this->advertisementTypesWith));

        $this->assertEquals('Updating ' . $this->router()->id . ', adding TIER1_CONNECTED type', $message);
    }

    public function testUpdateRouteAdvertisementTypesNotFound()
    {
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('option')->with('test-run')->andReturnFalse();
        $this->nsxServiceMock()
            ->allows('patch')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id)
            ->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );
        $this->mock->allows('error')->with(\Mockery::capture($errorMessage));

        $this->assertFalse($this->mock->updateRouteAdvertisementTypes($this->router(), $this->advertisementTypesWith));

        $this->assertEquals('Updating ' . $this->router()->id . ', adding TIER1_CONNECTED type', $message);
        $this->assertEquals($this->router()->id . ' : Not Found', $errorMessage);
    }

    public function testUpdateRouteAdvertisement()
    {
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('option')->with('test-run')->andReturnFalse();
        $this->nsxServiceMock()
            ->allows('patch')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id)
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->assertTrue($this->mock->updateRouteAdvertisementTypes($this->router(), $this->advertisementTypesWith));
        $this->assertEquals('Updating ' . $this->router()->id . ', adding TIER1_CONNECTED type', $message);
    }

    public function testHandleNoAdvertisedTypes()
    {
        $this->mock->allows('getAdvertisedTypes')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : route_advertisement_types not found', $message);
    }

    public function testHandleTier1ConnnectedPresent()
    {
        $this->mock->allows('getAdvertisedTypes')->andReturn($this->advertisementTypesWith);
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : already contains TIER1_CONNECTED type.', $message);
    }

    public function testHandleUpdateRouteAdvertisementTypesFailure()
    {
        $this->mock->allows('getAdvertisedTypes')->andReturn($this->advertisementTypesWithout);
        $this->mock->allows('updateRouteAdvertisementTypes')->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : Failed to update route_advertisement_types', $message);
    }
}
