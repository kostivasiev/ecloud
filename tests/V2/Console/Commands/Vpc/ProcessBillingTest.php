<?php
namespace Tests\V2\Console\Commands\Vpc;

use Carbon\Carbon;
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

    public function testBuildAndBillSolution()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 11.13;
        $actualCost = $this->averageMonth()
            ->addVcpu(1)
            ->addRam(1)
            ->addVolume(50, 300)
            ->addSupport()
            ->runBilling()
            ->getCost();
        $this->assertEquals($expectedCost, number_format($actualCost, 2));
    }

    // no vpc | £100 discount plan (£90 charge)
    public function testNoVpc100DiscountPlan()
    {
        $this->setDebugRunExpectation(2, 0);
        $expectedCost = 90.00;
        $actualCost = $this->noVpc()
            ->averageMonth()
            ->addDiscountPlan(100)
            ->runBilling()
            ->getCost();
        $this->assertEquals($expectedCost, $actualCost);
    }

    // single vpc | no resources but £100 discount plan (£90 charge)
    public function testSingleVpc100DiscountPlanNoResources()
    {
        $this->setDebugRunExpectation(2, 0);
        $expectedCost = 90.00;
        $actualCost = $this->averageMonth()
            ->addDiscountPlan(100)
            ->runBilling()
            ->getCost();
        $this->assertEquals($expectedCost, $actualCost);
    }

    // single vpc | no resources (so no charges)
    public function testSingleVpcNoResources()
    {
        $this->setDebugRunExpectation(1, 0);
        $expectedCost = 0.00;
        $actualCost = $this->averageMonth()
            ->runBilling()
            ->getCost();
        $this->assertEquals($expectedCost, $actualCost);
    }

    // single vpc | one resource but below min payment (so uplift applied)
    public function testSingleVpcOneResourceButBelowMinPayment()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 1.00;
        $actualCost = $this->forHours(1)
            ->addVolume()
            ->runBilling()
            ->getCost();
        $this->assertEquals($expectedCost, $actualCost);
    }

    // single vpc | one resource for full period
    public function testSingleVpcOneResourceMonth()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 8.90;
        $actualCost = $this->averageMonth()
            ->addVcpu()
            ->addRam()
            ->addVolume(50, 300)
            ->runBilling()
            ->getCost();
        $this->assertEquals(number_format($expectedCost, 2), number_format($actualCost, 2));
    }

    // single vpc | one resource for partial period
    public function testSingleVpcOneResourceOneDay()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 1.00;
        $actualCost = $this->forDays(1)
            ->addVcpu()
            ->addRam()
            ->addVolume(50, 300)
            ->runBilling()
            ->getCost();
        $this->assertEquals(number_format($expectedCost, 2), number_format($actualCost, 2));
    }

    public function testSingleVpcOneResourceOneWeek()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 2.05;
        $actualCost = $this->forDays(7)
            ->addVcpu()
            ->addRam()
            ->addVolume(50, 300)
            ->runBilling()
            ->getCost();
        $this->assertEquals(number_format($expectedCost, 2), number_format($actualCost, 2));
    }

    public function testSingleVpcOneResourceTwoWeeks()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 4.10;
        $actualCost = $this->forDays(14)
            ->addVcpu()
            ->addRam()
            ->addVolume(50, 300)
            ->runBilling()
            ->getCost();
        $this->assertEquals(number_format($expectedCost, 2), number_format($actualCost, 2));
    }

    // single vpc | one instance (1cpu/1ram/20hdd/etc)
    public function testSingleVpcOneInstanceSimple()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 11;
        $actualCost = $this->forHours(365) // for half a month
            ->useSimplePrice()
            ->addVcpu(1) // 0.5
            ->addRam(1) // 0.5
            ->addVolume(20, 300) // 10
            ->runBilling()
            ->getCost();
        $this->assertEquals(number_format($expectedCost, 2), number_format($actualCost, 2));
    }

    // single vpc | one instance (1cpu/1ram/20hdd/etc) with ram upgrade to 2GB mid month
    public function testSingleVpcOneInstanceWith25GbRamUpgradeMidMonth()
    {
        $this->setDebugRunExpectation(3, 0);
        $expectedCost = 46.5;

        // Solution for first half of month
        $this->forHours(365) // for half a month (total 11)
            ->useSimplePrice() // price of 1 per month
            ->addVcpu(1) // 0.5
            ->addRam(1) // 0.5
            ->addVolume(20, 300); // 10

        // Solution for second half of month
        $actualCost = $this->endRam(1) // (total 35.5)
            ->forHours(365)
            ->addRam(25) // 25 (price set to be double when high ram)
            ->addVcpu(1) // 0.5
            ->addVolume(20, 300) // 10
            ->runBilling()
            ->getCost();

        $this->assertEquals($expectedCost, $actualCost);
    }

    public function testThroughputPricing()
    {
        $expectedCost = 1.00;
        $this->setDebugRunExpectation(3, 0);

        // Add throughput to solution
        $this->addThroughput(1, '1Gb');

        // calculate end date of 18 seconds
        $this->endDate = Carbon::createFromTimeString("First day of last month 00:00:00", new \DateTimeZone(config('app.timezone')));
        $this->endDate->addSeconds(18);

        // End throughput metric
        $actualCost = $this->endThroughput(1, '1Gb')
            ->runBilling()
            ->getCost();

        $this->assertEquals($expectedCost, $actualCost);
    }
}