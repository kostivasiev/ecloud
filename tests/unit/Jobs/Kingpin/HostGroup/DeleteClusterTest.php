<?php
namespace Tests\unit\Jobs\Kingpin\HostGroup;

use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteClusterTest extends TestCase
{
    protected $job;
    protected $hostGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->hostGroup = HostGroup::withoutEvents(function () {
            return factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });
        $this->job = \Mockery::mock(DeleteCluster::class, [$this->hostGroup])->makePartial();
    }

    /**
     * @test Delete cluster fails after a 404 response is received. Logging should reflect this, but the job should
     * continue, skipping the failed component. This means that other tasks (including Database deletion) continue
     * uninterrupted.
     */
    public function testDeleteClusterFails404()
    {
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andThrow(new RequestException('Not Found', new Request('delete', '', []), new Response(404)));
        // The job should not fail
        $this->job->shouldNotReceive('fail');

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->with(\Mockery::on(function ($arg) {
            return stripos($arg, 'Failed to delete Host Group hg-test, skipping') !== false;
        }));
        $this->assertNull($this->job->handle());
    }

    /**
     * @test Server exceptions should make the job fail
     */
    public function testDeleteClusterFails500()
    {
        $exception = null;
        $message = 'Server Error';
        $code = 500;
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andThrow(new ServerException($message, new Request('delete', '', []), new Response($code)));
        // Fail should be called
        $this->job->expects('fail')->with(\Mockery::capture($exception));

        // null return from running the job
        $this->assertNull($this->job->handle());

        // but we do expect a fail to be called with exception info
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    /**
     * @test Delete Cluster is successful after a 200 response received
     */
    public function testDeleteClusterSuccess()
    {
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andReturnUsing(function () {
                return new Response(200);
            });
        // The job should not fail
        $this->job->shouldNotReceive('fail');
        $this->assertNull($this->job->handle());
    }
}