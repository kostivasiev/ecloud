<?php
namespace Tests\unit\Jobs\Kingpin\HostGroup;

use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteClusterTest extends TestCase
{
    use DatabaseMigrations;

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

    public function testDeleteClusterFails()
    {
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andThrow(new RequestException('Not Found', new Request('delete', '', []), new Response(404)));

        Log::shouldReceive('info')->withSomeOfArgs(get_class($this->job) . ' : Started');
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Not Found');

        $this->assertNull($this->job->handle());
    }

    public function testDeleteClusterSuccess()
    {
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id)
            ->andReturnUsing(function () {
                return new Response(200);
            });
        Log::shouldReceive('info')->withSomeOfArgs(get_class($this->job) . ' : Started');
        Log::shouldReceive('info')->withSomeOfArgs(get_class($this->job) . ' : Finished');

        $this->assertNull($this->job->handle());
    }
}