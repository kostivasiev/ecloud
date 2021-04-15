<?php
namespace Tests\unit\Jobs\Kingpin\Host;

use App\Jobs\Kingpin\Host\DeleteInVmware;
use App\Jobs\Sync\Host\Delete;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CheckExistsTest extends TestCase
{
    use DatabaseMigrations;

    protected Host $host;
    protected $job;

    public function setUp(): void
    {
        parent::setUp();
        app()->bind(Sync::class, function () {
            return new Sync([
                'id' => 'sync-test',
            ]);
        });
        $this->host = Host::withoutEvents(function () {
            return factory(Host::class)->create([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });
        $this->job = \Mockery::mock(Delete::class)->makePartial();
    }

    public function testCheckExistsFail()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));
        Log::shouldReceive('warning')
            ->withSomeOfArgs(get_class($this->job) . ' : Host does not exist, skipping.');

        $this->assertFalse($this->job->checkExists($this->host));
    }

    public function testCheckExistsPasses()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/san/MCS-E-G0-3PAR-01/host/h-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->assertTrue($this->job->checkExists($this->host));
    }
}