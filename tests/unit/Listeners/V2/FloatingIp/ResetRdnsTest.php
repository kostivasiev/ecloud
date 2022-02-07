<?php
namespace Tests\unit\Listeners\V2\FloatingIp;

use Illuminate\Support\Facades\Event;
use App\Events\V2\FloatingIp\Deleted;
use App\Listeners\V2\FloatingIp\ResetRdnsHostname;
use App\Models\V2\FloatingIp;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Tests\TestCase;
use UKFast\SDK\SafeDNS\RecordClient;

class ResetRdnsTest extends TestCase
{
    protected Deleted $event;

    protected FloatingIp $floatingIp;

    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        Model::withoutEvents(function () {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-' . uniqid(),
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id
            ]);
        });

        $mockAccountRecordClient = \Mockery::mock(RecordClient::class);

        $mockAccountRecordClient->shouldReceive('getByName')->andReturn((object) [
            'id' => '',
            'name' => '',
            'zone' => '',
        ]);
        $mockAccountRecordClient->shouldReceive('update')->andReturnNull();

        app()->bind(RecordClient::class, function () use ($mockAccountRecordClient) {
            return $mockAccountRecordClient;
        });
    }

    public function testResetRdnsSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new ResetRdnsHostname($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);

        $this->floatingIp->refresh();

        $this->assertEquals($this->floatingIp->rdns_hostname, config('defaults.floating-ip.rdns.default_hostname'));
    }

}
