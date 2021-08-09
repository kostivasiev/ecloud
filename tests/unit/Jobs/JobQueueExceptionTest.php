<?php
namespace Tests\unit\Jobs;

use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Container\Container;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobQueueExceptionTest extends TestCase
{
    public function testMessageIsNotTruncated()
    {
        Queue::fake();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Queue::exceptionOccurred was fired');

        $message = json_encode([
            'httpStatus' => 'BAD_REQUEST',
            'error_code' => 500060,
            'module_name' => 'Policy',
            'error_message' => 'Found errors in the request. Please refer to the related Errors for details.',
            'related_errors' => [
                [
                    'httpStatus' => 'BAD_REQUEST',
                    'error_code' => 501322,
                    'module_name' => 'Policy',
                    'error_message' => 'VPN Service is already configured on LocaleService=[/infra/tier-1s/rtr-aaaaaaaa/locale-services/rtr-aaaaaaaa].',
                ]
            ]
        ]);

        Log::shouldReceive('error')
            ->withSomeOfArgs(
                'App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity : Job exception occurred',
                [
                    'exception' => $message,
                ]
            )->andThrow(new \Exception('Queue::exceptionOccurred was fired'));

        $payload = json_encode([
            'job' => UpdateFloatingIpCapacity::class,
        ]);
        $job = new SyncJob(new Container(), $payload, 'ecloud', 'testqueue');
        $exception = RequestException::create(
            new Request('POST', '/', []),
            new Response(422, [], $message)
        );
        $event = new JobExceptionOccurred('ecloud', $job, $exception);
        event($event);
    }
}