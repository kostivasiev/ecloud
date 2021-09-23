<?php
namespace Tests\unit\Jobs\Vpc;

use App\Events\V2\Task\Created;
use App\Jobs\Conjurer\Host\PowerOff;
use App\Jobs\Vpc\RemoveLanPolicies;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RemoveLanPoliciesTest extends TestCase
{
    public function testSkipsWhenLANPolicyDoesntExist()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new RemoveLanPolicies($this->vpc()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testRemovesLABPolicyWhenExists()
    {
        $this->conjurerServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test');

        $this->conjurerServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-test');

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new RemoveLanPolicies($this->vpc()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}