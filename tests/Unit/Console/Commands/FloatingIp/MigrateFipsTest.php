<?php

namespace Tests\Unit\Console\Commands\FloatingIp;

use App\Console\Commands\FloatingIp\MigrateFips;
use App\Events\V2\Task\Created;
use App\Models\V2\FloatingIp;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MigrateFipsTest extends TestCase
{
    use WithFaker;

    protected $job;

    public function setUp(): void
    {
        parent::setUp();
        Event::fake(Created::class);
        $this->nic()->ipAddresses()->attach($this->ipAddress());
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();
        $this->job = \Mockery::mock(MigrateFips::class)->makePartial();
        $this->job->allows('option')->with('test-run')->andReturnFalse();
        $this->job->allows('info')->withAnyArgs()->andReturnTrue();
    }

    public function testSuccessfulChange()
    {
        $this->assertEquals('nic', $this->floatingIp()->resource_type);

        $this->job->handle();

        $this->assertDatabaseHas(
            Nic::class,
            [
                'id' => $this->nic()->id,
            ],
            'ecloud'
        );

        $this->floatingIp()->refresh();

        $this->assertNotEquals('nic', $this->floatingIp()->resource_type);
        $this->assertEquals('ip', $this->floatingIp()->resource_type);
    }

    public function testRecordUnchanged()
    {
//        $this->markTestSkipped('Command scheduled for removal');
        $nic = Nic::factory()
            ->forNetwork()
            ->create();
        $ip = IpAddress::factory()
            ->for($nic->network)
            ->create([
                'ip_address' => $this->faker->ipv4()
            ]);
        $floatingIp = FloatingIp::factory()
            ->forVpc()
            ->forAvailabilityZone()
            ->for($ip, 'resource')
            ->create([
                'ip_address' => $ip->ip_address,
            ]);
        $originalIp = $ip->ip_address;

        $this->job->handle();

        $this->assertNull($nic->ip_address);
        $this->assertEquals($originalIp, $ip->ip_address);
        $this->assertNotEquals('nic', $floatingIp->resource_type);
    }
}