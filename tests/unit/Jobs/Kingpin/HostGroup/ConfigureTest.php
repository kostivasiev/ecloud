<?php
namespace Tests\unit\Jobs\Kingpin\HostGroup;

use App\Jobs\Kingpin\HostGroup\Configure;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConfigureTest extends TestCase
{
    protected $job;
    protected $hostGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->hostGroup = HostGroup::factory()->create([
            'id' => 'hg-test',
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => true,
        ]);
    }

    public function testConfigureSuccess()
    {
        $this->kingpinServiceMock()
            ->expects('put')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id . '/configure')
            ->andReturnUsing(function () {
                return new Response(200);
            });


        Event::fake([JobFailed::class]);

        dispatch(new Configure($this->hostGroup));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testConfigureFail()
    {
        $this->expectException(RequestException::class);
        $this->kingpinServiceMock()
            ->expects('put')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->hostGroup->vpc->id . '/hostgroup/' . $this->hostGroup->id . '/configure')
            ->andThrow(new ServerException('Server Error', new Request('put', '', []), new Response(500)));


        Event::fake([JobFailed::class]);

        dispatch(new Configure($this->hostGroup));

        Event::assertDispatched(JobFailed::class);
    }
}