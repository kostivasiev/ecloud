<?php

namespace Tests\Unit\Console\Commands\VPC;

use App\Console\Commands\VPC\ProcessBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\DiscountPlan;
use Carbon\Carbon;
use Tests\TestCase;
use UKFast\Admin\Account\AdminClient as AccountAdminClient;
use UKFast\Admin\Account\AdminCustomerClient;
use UKFast\Admin\Account\Entities\Customer;

class ResellerMetricsTest extends TestCase
{
    protected $command;
    protected $resellerId = 7052;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vpc()->setAttribute('reseller_id', $this->resellerId)->saveQuietly();
        DiscountPlan::factory()->create([
            'reseller_id' => $this->resellerId,
            'status' => 'approved',
            'term_start_date' => Carbon::now()->subtract('month', 1)->toString(),
        ]);
        BillingMetric::factory()->create([
            'resource_id' => $this->instanceModel()->id,
            'reseller_id' => $this->resellerId,
            'vpc_id' => $this->vpc()->id,
            'start' => Carbon::now()->subtract('month', 1)->toString(),
        ]);

        app()->bind(AccountAdminClient::class, function () {
            $mock = \Mockery::mock(AccountAdminClient::class)->makePartial();
            $mock->allows('customers')
                ->andReturnUsing(function () {
                    $customerClientMock = \Mockery::mock(AdminCustomerClient::class)->makePartial();
                    $customerClientMock->allows('getById')
                        ->withAnyArgs()
                        ->andReturn(new Customer([
                            'accountStatus' => 'Private Client',
                        ]));
                    return $customerClientMock;
                });
            return $mock;
        });
    }

    public function testTargetedResellerRun()
    {
        $command = $this->createCommandMock();
        $command->handle();

        $runningTotal = $this->getProtectedPropertyValue($command, 'runningTotal');
        $this->assertGreaterThan(0, $runningTotal);

        $billing = $this->getProtectedPropertyValue($command, 'billing');
        $this->assertArrayHasKey($this->resellerId, $billing);
        $this->assertEquals(1, count($billing));
    }

    /**
     * @throws \ReflectionException
     */
    protected function setProtectedPropertyValue($object, $property, $value)
    {
        $reflectionClass = new \ReflectionClass($object);
        $privateProp = $reflectionClass->getProperty($property);
        $privateProp->setAccessible(true);
        $privateProp->setValue($object, $value);
        $privateProp->setAccessible(false);
    }

    /**
     * @throws \ReflectionException
     */
    protected function getProtectedPropertyValue($object, $property)
    {
        $reflectionClass = new \ReflectionClass($object);
        $privateProp = $reflectionClass->getProperty($property);
        $privateProp->setAccessible(true);
        $retVal = $privateProp->getValue($object);
        $privateProp->setAccessible(false);
        return $retVal;
    }

    protected function createCommandMock()
    {
        $command = \Mockery::mock(ProcessBilling::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $timeZone = new \DateTimeZone(config('app.timezone'));
        $this->setProtectedPropertyValue($command, 'runningTotal', 0);
        $this->setProtectedPropertyValue($command, 'timeZone', $timeZone);
        $this->setProtectedPropertyValue(
            $command,
            'startDate',
            Carbon::createFromTimeString("First day of last month 00:00:00", $timeZone)
        );
        $this->setProtectedPropertyValue(
            $command,
            'endDate',
            Carbon::createFromTimeString("last day of last month 23:59:59", $timeZone)
        );

        $command->allows('option')->with('reseller')->andReturn(7052);
        $command->allows('option')->with('current-month')->andReturnNull();
        $command->allows('option')->with('test-run')->andReturnTrue();
        $command->allows('option')->with('debug')->andReturnTrue();
        $command->allows('info')->withAnyArgs()->andReturnTrue();
        $command->allows('line')->withAnyArgs()->andReturnTrue();

        $command->allows('getVpcMetrics')->withAnyArgs()->andReturnUsing(function () {
            $metrics = BillingMetric::query()->get();
            return $metrics->mapToGroups(function ($item, $key) {
                return [$item['key'] => $item];
            });
        });
        return $command;
    }
}
