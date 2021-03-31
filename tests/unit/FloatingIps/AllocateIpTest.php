<?php

namespace Tests\unit\FloatingIps;

use App\Events\V2\FloatingIp\Created;
use App\Events\V2\FloatingIp\Saving;
use App\Jobs\FloatingIp\AllocateIp;
use App\Models\V2\FloatingIp;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Networking\AdminClient;
use UKFast\Admin\Networking\IpRangeClient;

class AllocateIpTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;

    protected $instance;
    protected $floating_ip;
    protected $nic;
    protected $job;
    protected $event;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->vpc();
        $this->floating_ip = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => null,
                'vpc_id' => $this->vpc()->id
            ]);
        });

        $this->event = new Saving($this->floating_ip);

        $mockAdminNetworking = \Mockery::mock(\UKFast\Admin\Networking\AdminClient::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind(\UKFast\Admin\Networking\AdminClient::class, function () use ($mockAdminNetworking) {
            $mockAdminNetworking->shouldReceive('ipRanges->getPage->totalPages')->andReturn(1);

            $mockAdminNetworking->shouldReceive('ipRanges->getPage->getItems')->andReturn(
                new Collection([
                    new \UKFast\Admin\Networking\Entities\IpRange(
                        [
                            "id" => 9028,
                            "description" => "203.0.113.0\/24 TEST-NET-3",
                            "externalSubnet" => "255.255.255.0",
                            "internalSubnet" => "",
                            "dnsOne" => "",
                            "dnsTwo" => "",
                            "vlan" => "",
                            "ipv6" => null,
                            "ipv6Subnet" => "",
                            "ipv6Gateway" => "",
                            "ipv6DnsOne" => "",
                            "ipv6DnsTwo" => "",
                            "autoDeployEnvironment" => "ecloud nsx",
                            "autoDeployFirewall_Id" => 0,
                            "autoDeployDatacentreId" => 8,
                            "resellerId" => 0,
                            "parentRangeId" => 0,
                            "networkAddress" => 3405803776,
                            "cidr" => 24,
                            "type" => "External",
                            "vrfNumber" => 0
                        ]
                    )
                ])
            );
            return $mockAdminNetworking;
        });

        $this->job = \Mockery::mock(AllocateIp::class)->makePartial();
        $this->job->model = $this->floating_ip;
    }

    public function testAllocateIp()
    {
        $this->job->handle();
        $this->floating_ip->refresh();
        $this->assertEquals($this->floating_ip->ip_address, '203.0.113.1');
    }

    public function testAllocateNextIp()
    {
        // Assign the first available IP, 203.0.113.1
        $this->job->handle();

        // Check assigning the next
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class, 1)->create([
                'id' => 'fip-nextip',
                'ip_address' => null,
            ])->each(function ($fip) {
                $vpc = factory(Vpc::class)->create([
                    'id' => 'vpc-another',
                    'region_id' => $this->region()->id
                ]);
                $fip->vpc_id = $vpc->id;
                $fip->save();
            })->first();
        });

        $this->job->model = $floatingIp;
        $this->job->handle();

        $floatingIp->refresh();

        $this->assertEquals($floatingIp->ip_address, '203.0.113.2');
    }

    public function testDeletedIpAvailableAgain()
    {
        // Assign the first available IP, 203.0.113.1
        $this->job->handle();
        $this->floating_ip->refresh();
        $this->assertEquals($this->floating_ip->ip_address, '203.0.113.1');
        $this->floating_ip->delete();

        // Check assigning the IP again
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class, 1)->create([
                'id' => 'fip-nextip',
                'ip_address' => null,
            ])->each(function ($fip) {
                $vpc = factory(Vpc::class)->create([
                    'id' => 'vpc-another',
                    'region_id' => $this->region()->id
                ]);
                $fip->vpc_id = $vpc->id;
                $fip->save();
            })->first();
        });

        $this->job->model = $floatingIp;
        $this->job->handle();
        $floatingIp->refresh();

        $this->assertEquals($floatingIp->ip_address, '203.0.113.1');
    }
}
