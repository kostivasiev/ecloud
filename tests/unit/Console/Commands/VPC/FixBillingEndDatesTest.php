<?php

namespace Tests\unit\Console\Commands\VPC;

use App\Console\Commands\VPC\FixBillingEndDates;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FixBillingEndDatesTest extends TestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->populateData();
        $this->command = \Mockery::mock(FixBillingEndDates::class)
            ->makePartial();
        $this->command->allows('info')
            ->withArgs(function ($argument) {
                Log::info($argument);
            })->andReturnTrue();
        $this->command->allows('line')
            ->withArgs(function ($argument) {
                Log::info($argument);
            })->andReturnTrue();
        $this->command->allows('option')
            ->withAnyArgs()
            ->andReturnFalse();

        $reflectionClass = new \ReflectionClass($this->command);
        $property = $reflectionClass->getProperty('output');
        $property->setAccessible(true);
        $property->setValue($this->command, new class {
            public function writeln($argument)
            {
                Log::info($argument);
            }
        });
        $property->setAccessible(false);
    }

    public function testCommand()
    {
        $this->command->handle();

        $vcpuReference = BillingMetric::findOrFail('bm-dcf09a8b')->created_at->format('Y-m-d H:i:s');
        $ramReference = BillingMetric::findOrFail('bm-ed3d1aef')->created_at->format('Y-m-d H:i:s');

        $this->assertEquals($vcpuReference, BillingMetric::find('bm-85693516')->end);
        $this->assertEquals($ramReference, BillingMetric::find('bm-0d934e7d')->end);
    }

    public function populateData()
    {
        $billing_metrics = [
            ['id' => 'bm-dcf09a8b','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2022-01-15 23:18:59','end' => null,'category' => 'Compute','price' => '0.006944440000','created_at' => '2022-01-15 23:19:00','updated_at' => '2022-01-15 23:19:00','deleted_at' => null],
            ['id' => 'bm-ed3d1aef','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2022-01-15 23:18:58','end' => null,'category' => 'Compute','price' => '0.000008160000','created_at' => '2022-01-15 23:18:58','updated_at' => '2022-01-15 23:18:58','deleted_at' => null],
            ['id' => 'bm-ece6058a','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2022-01-06 21:29:11','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2022-01-06 21:29:11','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-f47ccd89','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2022-01-06 21:29:10','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2022-01-06 21:29:10','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-2c9081d2','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2022-01-05 01:03:25','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2022-01-05 01:03:26','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-6b988d18','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2022-01-05 01:03:24','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2022-01-05 01:03:24','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-5587ced0','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '3','start' => '2022-01-04 23:15:39','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2022-01-04 23:15:40','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-6be45cf7','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2022-01-04 23:15:38','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2022-01-04 23:15:38','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-d8edff16','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2022-01-01 22:14:39','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2022-01-01 22:14:39','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-4bd8fa30','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2022-01-01 22:14:38','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2022-01-01 22:14:38','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-2060a4aa','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '4','start' => '2021-12-30 17:18:30','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-12-30 17:18:30','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-ea0ecd0c','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '8192','start' => '2021-12-30 17:18:29','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-12-30 17:18:29','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-8a4a9801','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-12-09 09:17:30','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-12-09 09:17:30','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-ad8f38c8','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-12-09 09:17:29','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-12-09 09:17:29','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-c3fc6790','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '3','start' => '2021-12-08 18:41:25','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-12-08 18:41:25','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-fedc7e26','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-12-08 18:41:24','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-12-08 18:41:24','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-b8921151','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-11-25 11:28:41','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-25 11:28:41','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-b20236c9','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-11-25 11:28:41','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-25 11:28:41','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-7cf65e79','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '3','start' => '2021-11-24 22:58:34','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-24 22:58:34','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-ef44e42c','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '8192','start' => '2021-11-24 22:58:34','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-24 22:58:34','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-85693516','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-11-21 01:30:01','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-21 01:30:01','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-0d934e7d','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-11-21 01:30:01','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-21 01:30:01','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-fcbeae69','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '4','start' => '2021-11-20 01:27:58','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-20 01:27:58','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-00da210c','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '8192','start' => '2021-11-20 01:27:58','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-20 01:27:58','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-291a0cb6','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-11-16 09:31:56','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-16 09:31:56','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-66e2fa8e','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-11-16 09:31:56','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-16 09:31:56','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-d348ed2b','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'Backup Capacity','key' => 'backup.quota','value' => '30','start' => '2021-11-16 09:29:39','end' => null,'category' => 'Storage','price' => '0.000273973000','created_at' => '2021-11-16 09:29:40','updated_at' => '2021-11-16 09:29:40','deleted_at' => null],
            ['id' => 'bm-1e77ab32','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '3','start' => '2021-11-16 09:02:32','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-16 09:02:32','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-dbddd80b','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-11-16 09:00:22','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-16 09:00:22','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-9d46524d','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'Backup Capacity','key' => 'backup.quota','value' => '20','start' => '2021-11-01 00:00:00','end' => '2021-11-16 09:29:39','category' => 'Storage','price' => '0.000273973000','created_at' => '2021-11-11 08:58:42','updated_at' => '2021-11-16 09:29:39','deleted_at' => null],
            ['id' => 'bm-f806b5e6','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-11-09 01:40:49','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-09 01:40:49','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-b33d82c4','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-11-09 01:40:48','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-09 01:40:49','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-bbe57bfd','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '4','start' => '2021-11-08 19:07:50','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-11-08 19:07:50','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-ec385a12','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-11-08 19:07:50','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000008160000','created_at' => '2021-11-08 19:07:50','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-12dfcc7a','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-10-27 03:01:39','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-27 03:01:39','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-72885b34','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-10-26 10:47:50','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.006944440000','created_at' => '2021-10-26 10:47:51','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-40ba93ee','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-10-26 10:47:50','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-26 10:47:50','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-c563939c','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '4','start' => '2021-10-25 14:42:05','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.010000000000','created_at' => '2021-10-25 14:42:05','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-ec0abda7','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '8192','start' => '2021-10-25 14:42:05','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-25 14:42:05','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-4c23bee1','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-10-20 00:33:55','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-20 00:33:55','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-e88ca218','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-10-19 05:34:51','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-19 05:34:51','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-ac581507','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-10-19 00:33:03','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-19 00:33:03','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-37e078c5','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-10-17 04:58:32','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-17 04:58:33','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-56e9d4c9','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-10-13 10:17:13','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-13 10:17:13','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-ab6a217e','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-10-13 02:10:02','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-13 02:10:02','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-1555394e','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-10-11 10:15:18','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-11 10:15:18','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-935329d6','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-10-09 23:56:49','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-09 23:56:49','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-3b468b96','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-10-05 05:05:20','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-05 05:05:20','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-3a29178f','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-10-04 10:36:46','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.010000000000','created_at' => '2021-10-04 10:36:46','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-78d6396f','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '3','start' => '2021-10-04 04:41:03','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.010000000000','created_at' => '2021-10-04 04:41:03','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-b4e7b698','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-10-04 04:41:03','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-10-04 04:41:03','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-41ffeeb9','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-09-29 19:09:22','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-29 19:09:22','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-88ba2e7e','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '6144','start' => '2021-09-28 23:45:06','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-28 23:45:06','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-0488b514','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-09-28 08:51:39','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-28 08:51:39','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-28286c13','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-09-28 02:02:08','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-28 02:02:08','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-216e381e','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-09-24 23:56:43','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-24 23:56:43','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-df011865','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '4096','start' => '2021-09-24 23:32:50','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-24 23:32:50','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-cd3481c0','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '3072','start' => '2021-09-20 18:20:17','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-20 18:20:17','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-17af224c','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'RAM','key' => 'ram.capacity','value' => '2048','start' => '2021-09-02 13:04:18','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.000000000000','created_at' => '2021-09-02 13:04:18','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-51980652','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'VCPU Count','key' => 'vcpu.count','value' => '2','start' => '2021-09-02 13:04:18','end' => '2022-01-15 23:18:41','category' => 'Compute','price' => '0.010000000000','created_at' => '2021-09-02 13:04:18','updated_at' => '2022-01-15 23:18:41','deleted_at' => null],
            ['id' => 'bm-37b7183c','resource_id' => 'i-90a128ac','vpc_id' => 'vpc-27f207ed','reseller_id' => '30345','name' => 'Backup Capacity','key' => 'backup.quota','value' => '20','start' => '2021-09-02 13:03:01','end' => '2021-10-31 23:59:59','category' => 'Storage','price' => '0.000000000000','created_at' => '2021-09-02 13:03:01','updated_at' => '2021-11-11 08:58:42','deleted_at' => null],
        ];
        foreach ($billing_metrics as $metric) {
            factory(BillingMetric::class)->create($metric);
            $instance = Instance::find($metric['resource_id']);
            if (!$instance) {
                factory(Instance::class)->create(['id' => $metric['resource_id']]);
            }
        }
    }
}
