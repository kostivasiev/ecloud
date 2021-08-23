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

    public function testDiscountPlanNoVpcBilling()
    {
        $this->setDebugRunExpectation(1);
        $discountPlan = factory(DiscountPlan::class)->create([
            'reseller_id' => 4151,
            'contact_id' => 1,
            'name' => 'no-vpc-test',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d 00:00:00', strtotime('now')),
            'term_end_date' => date('Y-m-d 00:00:00', strtotime('2 days')),
        ]);

        // Pro-Rata Calculations
        $hoursInBillingPeriod = $this->startDate->diffInHours($this->endDate);
        $hoursRemainingInBillingPeriodFromTermStart = $discountPlan->term_start_date->diffInHours($this->endDate);
        $percentHoursRemaining = ($hoursRemainingInBillingPeriodFromTermStart / $hoursInBillingPeriod) * 100;
        $proRataCommitmentAmount = ($discountPlan->commitment_amount / 100) * $percentHoursRemaining;

        $this->command->handle();

        $total = $this->command->calculateDiscounts(collect([$discountPlan]), 0);
        $this->assertNotNull($total);
        $this->assertEquals($proRataCommitmentAmount, $total);
    }
}