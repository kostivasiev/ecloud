<?php
namespace Tests\V2\Console\Commands\Vpc;

use App\Models\V2\DiscountPlan;
use Mockery\Exception\InvalidCountException;
use Tests\TestCase;
use Tests\V2\Console\BillingMetricTrait;

class ProcessBillingTest extends TestCase
{
    use BillingMetricTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->billingMetricSetup();
    }

    public function testVcpuCountBilling()
    {
        $this->markTestSkipped('Test to be refactored');
        $this->setDebugRunExpectation(2, 1);
        $code = 'vcpu.count';
        $price = 0.01;
        $quantity = 1;
        $totalCost = $this->createBillingMetricAndCost($code, $price, $quantity);

        $this->command->handle();

        $metricPrice = $this->getLineArgumentPrice($code);

        $this->assertNotNull($metricPrice);
        $this->assertEquals($totalCost, $metricPrice);
    }

    public function testIfThereIsAPriceBillIt()
    {
        $this->markTestSkipped('Test to be refactored');
        $code = 'mynew.metric';
        $price = 0.01;
        $quantity = 1;
        $totalCost = $this->createBillingMetricAndCost($code, $price, $quantity);

        $this->command->handle();

        $metricPrice = $this->getLineArgumentPrice($code);

        $this->assertNotNull($metricPrice);
        $this->assertEquals($totalCost, $metricPrice);
    }
}