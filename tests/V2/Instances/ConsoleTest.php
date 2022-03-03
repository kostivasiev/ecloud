<?php

namespace Tests\V2\Instances;

use App\Models\V2\Credential;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class ConsoleTest extends TestCase
{
    public function testFailedSessionResponse()
    {
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs(
                '/api/v2/vpc/vpc-test/instance/i-test/console/session'
            )
            ->andReturnUsing(function () {
                return new Response(502);
            });
        $this->post(
            '/v2/instances/'.$this->instanceModel()->id.'/console-session',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Bad Gateway',
                'details' => 'Console access to this instance is not available',
                'status' => 502,
            ]
        )->assertResponseStatus(502);
    }

    public function testCredentialFailure()
    {
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs(
                '/api/v2/vpc/vpc-test/instance/i-test/console/session'
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'host' => 'http://myhost.com',
                    'ticket' => '1234567890',
                ]));
            });
        $this->post(
            '/v2/instances/'.$this->instanceModel()->id.'/console-session',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Upstream API Failure',
                'details' => 'Console access is not available due to an upstream api failure',
                'status' => 503,
            ]
        )->assertResponseStatus(503);
    }

    public function testCreateSessionFailure()
    {
        Credential::factory()->create([
            'name' => 'Envoy',
            'resource_id' => $this->availabilityZone()->id,
            'host' => 'https://127.0.0.1',
            'username' => 'envoyapi',
            'password' => 'envoyapikey',
            'is_hidden' => true,
        ]);
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs(
                '/api/v2/vpc/vpc-test/instance/i-test/console/session'
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'host' => 'http://myhost.com',
                    'ticket' => '1234567890',
                ]));
            });
        $this->post(
            '/v2/instances/'.$this->instanceModel()->id.'/console-session',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Upstream API Failure',
                'details' => 'Console session is not available due to an upstream api failure',
                'status' => 503,
            ]
        )->assertResponseStatus(503);
    }

    public function testValidClientResult()
    {
        // Create Credential
        Credential::factory()->create([
            'name' => 'Envoy',
            'resource_id' => $this->availabilityZone()->id,
            'host' => 'https://127.0.0.1',
            'username' => 'envoyapi',
            'password' => 'envoyapikey',
            'is_hidden' => true,
        ]);

        // Create Kingpin Mock
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs(
                '/api/v2/vpc/vpc-test/instance/i-test/console/session'
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'host' => 'http://myhost.com',
                    'ticket' => '1234567890',
                ]));
            });

        // Create Guzzle Client mock
        $uuid = '5a9df6ba-5933-44ca-a4d8-2fa7286a2af3';
        app()->bind(Client::class, function () use ($uuid) {
            $mock = new MockHandler(
                [
                    new Response(201, [], json_encode(['uuid' => $uuid])),
                ]
            );
            $stack = HandlerStack::create($mock);
            return new Client(['handler' => $stack]);
        });

        // run test
        $this->post(
            '/v2/instances/'.$this->instanceModel()->id.'/console-session',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'url' => 'https://127.0.0.1/console/?title='.$this->instanceModel()->id.'&session='.$uuid
            ]
        )->assertResponseStatus(200);
    }

    public function testRestrictedConsoleNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Model::withoutEvents(function () {
            $this->vpc()->console_enabled = false;
            $this->vpc()->save();
        });
        $this->post(
            '/v2/instances/'.$this->instanceModel()->id.'/console-session',
            []
        )->seeJson(
            [
                'title' => 'Forbidden',
                'details' => 'Console access has been disabled for this resource',
            ]
        )->assertResponseStatus(403);
    }

    public function testRestrictedConsoleAdmin()
    {
        // Create Credential
        Credential::factory()->create([
            'name' => 'Envoy',
            'resource_id' => $this->availabilityZone()->id,
            'host' => 'https://127.0.0.1',
            'username' => 'envoyapi',
            'password' => 'envoyapikey',
            'is_hidden' => true,
        ]);

        // Create Kingpin Mock
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withSomeOfArgs(
                '/api/v2/vpc/vpc-test/instance/i-test/console/session'
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'host' => 'http://myhost.com',
                    'ticket' => '1234567890',
                ]));
            });

        // Create Guzzle Client mock
        $uuid = '5a9df6ba-5933-44ca-a4d8-2fa7286a2af3';
        app()->bind(Client::class, function () use ($uuid) {
            $mock = new MockHandler(
                [
                    new Response(201, [], json_encode(['uuid' => $uuid])),
                ]
            );
            $stack = HandlerStack::create($mock);
            return new Client(['handler' => $stack]);
        });

        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);
        Model::withoutEvents(function () {
            $this->vpc()->console_enabled = false;
            $this->vpc()->save();
        });
        $this->post(
            '/v2/instances/'.$this->instanceModel()->id.'/console-session',
            []
        )->seeJson(
            [
                'url' => 'https://127.0.0.1/console/?title='.$this->instanceModel()->id.'&session='.$uuid
            ]
        )->assertResponseStatus(200);
    }

    public function testScreenshot()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->kingpinServiceMock()
            ->shouldReceive('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_CONSOLE_SCREENSHOT, $this->instanceModel()->vpc_id, $this->instanceModel()->id)
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode($this->loadData('Kingpin/GetConsoleScreenshot.json')));
            });

        $response = $this->get('/v2/instances/' . $this->instanceModel()->id . '/console-screenshot');

        $response->assertResponseStatus(200);

        $this->assertEquals($this->loadData('Kingpin/GetConsoleScreenshot.json'), $response->response->getContent());

        $this->assertEquals(
            'attachment; filename=' . $this->instanceModel()->vpc_id . '-' . $this->instanceModel()->id . '-' . date('d-m-Y') . '-screenshot',
            $response->response->headers->all()['content-disposition'][0]
        );
    }
}