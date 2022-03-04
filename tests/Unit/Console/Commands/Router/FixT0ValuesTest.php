<?php

namespace Tests\Unit\Console\Commands\Router;

use App\Console\Commands\Router\FixT0Values;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class FixT0ValuesTest extends TestCase
{
    protected $mock;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock = \Mockery::mock(FixT0Values::class)->makePartial();
        $this->router()->setAttribute('is_management', true)->saveQuietly();
    }

    public function testT0Tag()
    {
        // Advanced Management tag
        $this->router()->vpc->setAttribute('advanced_networking', true)->saveQuietly();
        $this->assertEquals('az-adminadv', $this->mock->getT0Tag($this->router()));

        // Standard Management tag
        $this->router()->vpc->setAttribute('advanced_networking', false)->saveQuietly();
        $this->assertEquals('az-admin', $this->mock->getT0Tag($this->router()));
    }

    public function testGetTier0TagPathNoAvailabilityZone()
    {
        $router = $this->router();
        Model::withoutEvents(function () {
            $this->availabilityZone()->delete();
        });
        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->mock->getTier0TagPath($router, 'az-default');

        $this->assertEquals(
            $this->router()->id . ' : Availability Zone `' . $this->availabilityZone()->id . '` not found',
            $message
        );
    }

    public function testGetTier0TagPathGuzzleException()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                '/policy/api/v1/search/query?query=resource_type:'.
                'Tier0%20AND%20tags.scope:ukfast%20AND%20tags.tag:az-default'
            )->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );

        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->mock->getTier0TagPath($this->router(), 'az-default');

        $this->assertEquals($this->router()->id . ' : Not Found', $message);
    }

    public function testGetTier0TagPathZeroResultCount()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                '/policy/api/v1/search/query?query=resource_type:'.
                'Tier0%20AND%20tags.scope:ukfast%20AND%20tags.tag:az-default'
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0
                ]));
            });

        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->mock->getTier0TagPath($this->router(), 'az-default');

        $this->assertEquals($this->router()->id . ' : No results found.', $message);
    }

    public function testGetTier0TagPathPositiveResult()
    {
        $path = 'path/to/the/t0';
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                '/policy/api/v1/search/query?query=resource_type:'.
                'Tier0%20AND%20tags.scope:ukfast%20AND%20tags.tag:az-default'
            )->andReturnUsing(function () use ($path) {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'path' => $path,
                        ]
                    ]
                ]));
            });

        $this->assertEquals($path, $this->mock->getTier0TagPath($this->router(), 'az-default'));
    }

    public function testGetTier0ConfigNotFound()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                'policy/api/v1/infra/tier-1s/' . $this->router()->id
            )->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );

        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->mock->getTier0Config($this->router());

        $this->assertEquals($this->router()->id . ' : Not Found', $message);
    }

    public function testGetTier0ConfigMismatchedId()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                'policy/api/v1/infra/tier-1s/' . $this->router()->id
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'id' => $this->vpc()->id,
                ]));
            });

        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->mock->getTier0Config($this->router());

        $this->assertEquals($this->router()->id . ' : No results found.', $message);
    }

    public function testGetTier0Config()
    {
        $path = 'path/to/the/t0';
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                'policy/api/v1/infra/tier-1s/' . $this->router()->id
            )->andReturnUsing(function () use ($path) {
                return new Response(200, [], json_encode([
                    'id' => $this->router()->id,
                    'tier0_path' => $path,
                ]));
            });

        $this->assertEquals($path, $this->mock->getTier0Config($this->router()));
    }

    public function testUpdateTier0ConfigTestRun()
    {
        $this->mock->allows('option')->with('test-run')->andReturnTrue();
        $this->mock
            ->allows('info')
            ->with('Updating router ' . $this->router()->id . '(' . $this->router()->name . ')')
            ->andReturnTrue();
        $this->assertTrue($this->mock->updateTier0Config($this->router(), 'path/goes/here'));
    }

    public function testUpdateTier0ConfigUpdateException()
    {
        $this->mock->allows('option')->with('test-run')->andReturnFalse();
        $this->mock
            ->allows('info')
            ->with('Updating router ' . $this->router()->id . '(' . $this->router()->name . ')')
            ->andReturnTrue();
        $this->nsxServiceMock()
            ->allows('patch')
            ->withSomeOfArgs(
                'policy/api/v1/infra/tier-1s/' . $this->router()->id
            )->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );

        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->mock->updateTier0Config($this->router(), 'path/goes/here');

        $this->assertEquals($this->router()->id . ' : Not Found', $message);
    }

    public function testUpdateTier0Config()
    {
        $this->mock->allows('option')->with('test-run')->andReturnFalse();
        $this->mock
            ->allows('info')
            ->with('Updating router ' . $this->router()->id . '(' . $this->router()->name . ')')
            ->andReturnTrue();
        $this->nsxServiceMock()
            ->allows('patch')
            ->withSomeOfArgs(
                'policy/api/v1/infra/tier-1s/' . $this->router()->id
            )->andReturnUsing(function () {
                return new Response(200);
            });
        $this->assertTrue($this->mock->updateTier0Config($this->router(), 'path/goes/here'));
    }

    public function testHandleWhenGetT0TagFails()
    {
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->expects('getT0Tag')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : skipped, tier0Tag could not be determined.', $message);
    }

    public function testHandleWhenGetTier0TagPathFails()
    {
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->expects('getT0Tag')->withAnyArgs()->andReturn('az-default');
        $this->mock->expects('getTier0TagPath')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : skipped, tag `az-default` not found.', $message);
    }

    public function testHandleWhenGetTier0ConfigFails()
    {
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->expects('getT0Tag')->withAnyArgs()->andReturn('az-default');
        $this->mock->expects('getTier0TagPath')->withAnyArgs()->andReturn('path/to/t0');
        $this->mock->expects('getTier0Config')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : skipped, nsx config not found.', $message);
    }

    public function testHandleWhenPathsAreTheSame()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->expects('getT0Tag')->withAnyArgs()->andReturn('az-default');
        $this->mock->expects('getTier0TagPath')->withAnyArgs()->andReturn('path/to/t0');
        $this->mock->expects('getTier0Config')->withAnyArgs()->andReturn('path/to/t0');
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : skipped, tier0_path is correct.', $message);
    }

    public function testHandleWhenUpdateTier0ConfigFails()
    {
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->expects('getT0Tag')->withAnyArgs()->andReturn('az-default');
        $this->mock->expects('getTier0TagPath')->withAnyArgs()->andReturn('correct/t0/path');
        $this->mock->expects('getTier0Config')->withAnyArgs()->andReturn('path/to/t0');
        $this->mock->expects('updateTier0Config')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : tier0_path failed modification.', $message);
    }

    public function testHandleWhenUpdateTier0ConfigSucceeds()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->expects('getT0Tag')->withAnyArgs()->andReturn('az-default');
        $this->mock->expects('getTier0TagPath')->withAnyArgs()->andReturn('correct/t0/path');
        $this->mock->expects('getTier0Config')->withAnyArgs()->andReturn('path/to/t0');
        $this->mock->expects('updateTier0Config')->withAnyArgs()->andReturnTrue();
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : tier0_path successfully modified.', $message);
    }
}
