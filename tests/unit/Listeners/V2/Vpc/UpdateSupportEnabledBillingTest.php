<?php
namespace Tests\unit\Listeners\V2\Vpc;

use App\Events\V2\Task\Created;
use App\Jobs\Vpc\UpdateSupportEnabledBilling;
use App\Models\V2\BillingMetric;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateSupportEnabledBillingTest extends TestCase
{
    public function testStartsBillingMetricForSupportEnabled()
    {
        Event::fake(Created::class);

        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName()));

        dispatch(new UpdateSupportEnabledBilling($this->vpc(), true));

        $metric = BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName());
        $this->assertNotNull($metric);
        $this->assertEquals(1, $metric->value);
    }

    public function testEndsBillingMetricForSupportEnabled()
    {
        $originalMetric = factory(BillingMetric::class)->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => UpdateSupportEnabledBilling::getKeyName(),
            'value' => 1,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->assertNotNull(BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName()));

        dispatch(new UpdateSupportEnabledBilling($this->vpc(), false));

        $originalMetric->refresh();
        $this->assertNotNull($originalMetric->end);

        $this->assertNull(BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName()));
    }
}
