<?php
namespace Tests\unit\Jobs\Tasks\Instance;

use App\Jobs\Instance\EndPublicBilling;
use App\Jobs\Instance\StartRamBilling;
use App\Jobs\Instance\StartVcpuBilling;
use App\Jobs\Instance\StartLicenseBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MigrateBillingTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testBillingEndsOnPublicInstance()
    {
        // compute metrics created on deploy
        $originalVcpuMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test1',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'category' => 'Compute',
            'key' => 'vcpu.count',
            'value' => 1,
            'start' => Carbon::now(),
        ]);

        $originalRamMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test2',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'category' => 'Compute',
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => Carbon::now(),
        ]);

        $originalLicenseMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test3',
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'license.windows',
            'value' => 1,
            'start' => Carbon::now(),
        ]);

        $job = new EndPublicBilling($this->instance(), 'hg-aaabbbccc');
        $job->handle();

        $originalVcpuMetric->refresh();
        $originalRamMetric->refresh();
        $originalLicenseMetric->refresh();

        $this->assertNotNull($originalVcpuMetric->end);
        $this->assertNotNull($originalRamMetric->end);
        $this->assertNotNull($originalLicenseMetric->end);
    }

    public function testBillingStartsOnAPublicInstance()
    {
        $this->instance()->host_group_id = '';
        $this->instance()->platform = 'Windows';
        $this->instance()->saveQuietly();

        (new StartRamBilling($this->instance()))->handle();
        (new StartVcpuBilling($this->instance()))->handle();
        (new StartLicenseBilling($this->instance()))->handle();

        $this->instance()->refresh();

        $metrics = $this->instance()->billingMetrics()->get()->toArray();
        $this->assertEquals('ram.capacity', $metrics[0]['key']);
        $this->assertNull($metrics[0]['end']);
        $this->assertEquals('vcpu.count', $metrics[1]['key']);
        $this->assertNull($metrics[1]['end']);
        $this->assertEquals('license.windows', $metrics[2]['key']);
        $this->assertNull($metrics[2]['end']);
    }
}