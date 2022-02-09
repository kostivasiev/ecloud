<?php
namespace Tests\V2\Console;

use App\Console\Commands\VPC\ProcessBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\DiscountPlan;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ProductPriceCustom;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ReflectionProperty;

trait BillingMetricTrait
{

    protected $command;
    protected \DateTimeZone $timeZone;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected $hours;

    protected $infoArgument;
    protected array $lineArgument;
    protected $lineArgumentItem;
    protected $errorArgument;

    protected $totalCost = 0;
    protected $resellerId;
    protected $scriptTotal;
    protected $simplePrice;
    protected $averageMonthHours = 730;

    public function billingMetricSetup()
    {
        $this->timeZone = new \DateTimeZone(config('app.timezone'));
        $this->startDate = Carbon::createFromTimeString("First day of last month 00:00:00", new \DateTimeZone(config('app.timezone')));
        $this->endDate = Carbon::createFromTimeString("last day of last month 23:59:59", new \DateTimeZone(config('app.timezone')));

        $this->hours = $this->startDate->diffInHours($this->endDate);
        // minimum 1 hour
        $this->hours = ($this->hours < 1) ? 1 : $this->hours;

        $this->command = \Mockery::mock(ProcessBilling::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->setProtectedProperty($this->command, 'billing', []);
        $this->setProtectedProperty($this->command, 'timeZone', $this->timeZone);
        $this->setProtectedProperty($this->command, 'startDate', $this->startDate);
        $this->setProtectedProperty($this->command, 'endDate', $this->endDate);
        $this->setProtectedProperty($this->command, 'runningTotal', 0);
        $this->setProtectedProperty($this->command, 'discountBilling', []);

        $this->command->shouldReceive('info')
            ->with(\Mockery::capture($this->infoArgument))->andReturnUsing(function () {
                return true;
            });
        $this->command->shouldReceive('line')
            ->with(\Mockery::capture($this->lineArgumentItem))->andReturnUsing(function () {
                $this->lineArgument[] = $this->lineArgumentItem;
                return true;
            });
        $this->command->shouldReceive('error')
            ->with(\Mockery::capture($this->errorArgument))->andReturnUsing(function () {
                return true;
            });
        $this->command->shouldReceive('addBillingToAccount')
            ->with(\Mockery::capture($this->resellerId), \Mockery::capture($this->scriptTotal))
            ->andReturnUsing(function () {
                return true;
            });

        $this->vpc();
        DB::connection('ecloud')
            ->update('UPDATE vpcs set created_at = ? WHERE id = ?', [$this->startDate, $this->vpc()->id]);
        $this->vpc()->refresh();


        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAdminCustomerClient = \Mockery::mock(\UKFast\Admin\Account\AdminCustomerClient::class)->makePartial();

        $mockAdminCustomerClient->shouldReceive('getById')->andReturn(
            new \UKFast\Admin\Account\Entities\Customer(
                [
                    'accountStatus' => 'Staff'
                ]
            )
        );
        $mockAccountAdminClient->shouldReceive('customers')->andReturn(
            $mockAdminCustomerClient
        );

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });
    }

    public function setDebugRunExpectation($debug, $testRun = 0)
    {
        $this->command->expects('option')
            ->with('debug')
            ->times($debug)
            ->andReturnUsing(function () {
                return true;
            });
        $this->command->expects('option')
            ->with('test-run')
            ->times($testRun)
            ->andReturnUsing(function () {
                return true;
            });
        $this->command->expects('option')
            ->with('current-month')
            ->once()
            ->andReturnUsing(function () {
                return false;
            });
        $this->command->expects('option')
            ->with('reseller')
            ->once()
            ->andReturnUsing(function () {
                return false;
            });
        return;
    }

    /**
     * @param $object
     * @param $propertyName
     * @param $propertyValue
     * @throws \ReflectionException
     */
    public function setProtectedProperty($object, $propertyName, $propertyValue)
    {
        $reflection = $this->unprotectProperty($object, $propertyName);
        $reflection->setValue($object, $propertyValue);
    }

    public function unprotectProperty($object, $propertyName)
    {
        $reflection = new ReflectionProperty(get_class($object), $propertyName);
        $reflection->setAccessible(true);
        return $reflection;
    }

    public function getDiscountBilling()
    {
        return $this->unprotectProperty($this->command, 'discountBilling')
            ->getValue($this->command);
    }

    public function createBillingMetricAndCost($code, $price, $quantity)
    {
        factory(Product::class)->create([
            'product_name' => $this->availabilityZone()->id . ': ' . $code,
            'product_cost_price' => $price,
        ])->each(function ($product) use ($price) {
            factory(ProductPrice::class)->create([
                'product_price_product_id' => $product->id,
                'product_price_sale_price' => $price,
            ])->each(function ($productPrice) use ($price) {
                factory(ProductPriceCustom::class)->create([
                    'product_price_custom_product_id' => $productPrice->product_price_product_id,
                    'product_price_custom_reseller_id' => 1,
                    'product_price_custom_sale_price' => $price,
                ]);
            });
        });

        factory(BillingMetric::class)->create([
            'resource_id' => $this->instance()->id,
            'vpc_id' => $this->vpc()->id,
            'reseller_id' => 1,
            'key' => $code,
            'value' => $quantity,
            'category' => 'Compute',
            'price' => $price
        ]);

        return ($this->hours * $price) * $quantity;
    }

    public function endBillingMetric(string $code, int $quantity = 1)
    {
        $billingMetric = BillingMetric::where([
            ['resource_id', '=', $this->instance()->id],
            ['vpc_id', '=', $this->vpc()->id],
            ['key', '=', $code],
        ])
            ->whereNull('end')
            ->whereNull('deleted_at')
            ->first();
        if ($billingMetric->quantity > $quantity) {
            // then it's only a part cancellation
            $attribs = $billingMetric->getAttributes();
            $remove = ['id', 'start', 'end', 'created_at', 'updated_at', 'deleted_at'];
            $attribs = array_diff_key($attribs, array_flip($remove));
            $attribs['quantity'] = $billingMetric->quantity - $quantity;
            factory(BillingMetric::class)->create($attribs);
        }
        $billingMetric->setAttribute('end', $this->endDate)->saveQuietly();
        return $this;
    }

    public function getLineArgumentPrice($code)
    {
        foreach ($this->lineArgument as $line) {
            if (preg_match('/' . $code . ':[^0-9]+([0-9\.]+)/i', $line, $matches)) {
                return (float) $matches[1];
            }
        }
        return null;
    }

    public function averageMonth()
    {
        $this->hours = $this->averageMonthHours;
        $this->adjustEndDate();
        return $this;
    }

    public function useSimplePrice()
    {
        $this->simplePrice = (1 / $this->averageMonthHours);
        return $this;
    }

    public function setGlobalPrice(int $price = 1)
    {
        $this->simplePrice = $price;
        return $this;
    }

    public function forDays($days = 1)
    {
        $this->hours = $days * 24;
        $this->adjustEndDate();
        return $this;
    }

    public function forHours($hours = 0)
    {
        $this->hours = $hours;
        $this->adjustEndDate();
        return $this;
    }

    public function adjustEndDate()
    {
        $this->endDate = Carbon::createFromTimeString("First day of last month 00:00:00", new \DateTimeZone(config('app.timezone')));
        $this->endDate->addHours($this->hours);
        $this->setProtectedProperty($this->command, 'endDate', $this->endDate);
        return $this;
    }

    public function getCost()
    {
        return (float) $this->scriptTotal;
    }

    public function noVpc()
    {
        Vpc::withoutEvents(function () {
            $this->vpc()->delete();
        });
        return $this;
    }

    public function addVcpu(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.00263849;
        $this->totalCost += $this->createBillingMetricAndCost('vcpu-1', $price, $quantity);
        return $this;
    }

    public function endVcpu(int $quantity = 1)
    {
        $this->endBillingMetric('vcpu-1', $quantity);
        return $this;
    }

    public function addRam(int $quantity = 1)
    {
        $name = 'ram-1mb';
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.004148224;
        if ($quantity > 24) {
            $name = 'ram:high-1mb';
            $price = (!empty($this->simplePrice)) ? ($this->simplePrice * 2) : 0.0001410959;
        }
        $this->totalCost += $this->createBillingMetricAndCost($name, $price, $quantity);
        return $this;
    }

    public function endRam(int $quantity = 1)
    {
        $name = 'ram-1mb';
        if ($quantity > 24) {
            $name = 'ram:high-1mb';
        }
        $this->endBillingMetric($name, $quantity);
        return $this;
    }

    public function addVolume(int $quantity = 1, int $iops = 300)
    {
        $name = 'volume@'.$iops.'-1gb';
        switch ($iops) {
            case 300:
                $price = 0.000108219;
                break;
            case 600:
                $price = 0.000131507;
                break;
            case 1200:
                $price = 0.000180822;
                break;
            case 2500:
                $price = 0.0003;
                break;
        }
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : $price;
        $this->totalCost += $this->createBillingMetricAndCost($name, $price, $quantity);
        return $this;
    }

    public function endVolume(int $quantity, int $iops = 300)
    {
        $name = 'volume@'.$iops.'-1gb';
        $this->endBillingMetric($name, $quantity);
        return $this;
    }

    public function addWindowsLicense(int $quantity = 1, bool $host = false)
    {
        $name = 'windows-os-license';
        if ($host) {
            $name = 'host ' . $name;
        }
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.00186301;
        $this->totalCost += $this->createBillingMetricAndCost($name, $price, $quantity);
        return $this;
    }

    public function endWindowsLicense(int $quantity = 1, bool $host = false)
    {
        $name = 'windows-os-license';
        if ($host) {
            $name = 'host ' . $name;
        }
        $this->endBillingMetric($name, $quantity);
        return $this;
    }

    public function addBackup(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.000164384;
        $this->totalCost += $this->createBillingMetricAndCost('backup-1gb', $price, $quantity);
        return $this;
    }

    public function endBackup(int $quantity = 1)
    {
        $this->endBillingMetric('backup-1gb', $quantity);
        return $this;
    }

    public function addSupport(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.003054795;
        $this->totalCost += $this->createBillingMetricAndCost('support minimum', $price, $quantity);
        return $this;
    }

    public function endSupport(int $quantity = 1)
    {
        $this->endBillingMetric('backup-1gb', $quantity);
        return $this;
    }

    public function addThroughput(int $quantity = 1, string $threshold = '25mb')
    {
        $name = 'throughput '.$threshold;
        switch (strtolower($threshold)) {
            default:
            case "25mb":
                $price = 0;
                break;
            case "50mb":
                $price = 0.00236986;
                break;
            case "100mb":
                $price = 0.00876712;
                break;
            case "250mb":
                $price = 0.0219178;
                break;
            case "500mb":
                $price = 0.0520548;
                break;
            case "1gb":
                $price = 0.0931507;
                break;
            case "2.5gb":
                $price = 0.219178;
                break;
            case "5gb":
            case "10gb":
                $price = 9999;
                break;
        }
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : $price;
        $this->totalCost += $this->createBillingMetricAndCost($name, $price, $quantity);
        return $this;
    }

    public function endThroughput(int $quantity = 1, string $threshold = '25mb')
    {
        $name = 'throughput '.$threshold;
        $this->endBillingMetric($name, $quantity);
        return $this;
    }

    public function addFloatingIp(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.00547945;
        $this->totalCost += $this->createBillingMetricAndCost('floating ip', $price, $quantity);
        return $this;
    }

    public function endFloatingIp(int $quantity = 1)
    {
        $this->endBillingMetric('floating ip', $quantity);
        return $this;
    }

    // not live pricing
    public function addHostgroup(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.0000115314;
        $this->totalCost += $this->createBillingMetricAndCost('hostgroup', $price, $quantity);
        return $this;
    }

    public function endHostgroup(int $quantity = 1)
    {
        $this->endBillingMetric('hostgroup', $quantity);
        return $this;
    }

    // not live pricing
    public function addHostspec(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.00694444;
        $this->totalCost += $this->createBillingMetricAndCost('hs-aaaaaaaa', $price, $quantity);
        return $this;
    }

    public function endHostspec(int $quantity = 1)
    {
        $this->endBillingMetric('hs-aaaaaaaa', $quantity);
        return $this;
    }

    // not live pricing
    public function addAdvancedNetworking(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.000001338;
        $this->totalCost += $this->createBillingMetricAndCost('advanced networking', $price, $quantity);
        return $this;
    }

    public function endAdvancedNetworking(int $quantity = 1)
    {
        $this->endBillingMetric('advanced networking', $quantity);
        return $this;
    }

    // not live pricing
    public function addVpn(int $quantity = 1)
    {
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : 0.05;
        $this->totalCost += $this->createBillingMetricAndCost('site to site vpn', $price, $quantity);
        return $this;
    }

    public function endVpn(int $quantity = 1)
    {
        $this->endBillingMetric('site to site vpn', $quantity);
        return $this;
    }

    // not live pricing
    public function addLoadBalancer(int $quantity = 1, string $type = 'small')
    {
        $name = 'load balancer ' . $type;
        switch (strtolower($type)) {
            default:
            case "small":
                $price = 0.01;
                break;
            case "medium":
                $price = 0.02;
                break;
            case "large":
                $price = 0.03;
                break;
        }
        $price = (!empty($this->simplePrice)) ? $this->simplePrice : $price;
        $this->totalCost += $this->createBillingMetricAndCost($name, $price, $quantity);
        return $this;
    }

    public function endLoadBalancer(int $quantity = 1, string $type = 'small')
    {
        $name = 'load balancer ' . $type;
        $this->endBillingMetric($name, $quantity);
        return $this;
    }

    public function addDiscountPlan($commitment = 0.00)
    {
        factory(DiscountPlan::class)->create([
            'contact_id' => 1,
            'commitment_amount' => $commitment - (($commitment / 100) * 10),
            'commitment_before_discount' => $commitment,
            'orderform_id' => '84bfdc19-977e-462b-a14b-0c4b907fff55',
            'status' => 'approved',
            'term_start_date' => $this->startDate,
            'term_end_date' => $this->endDate,
        ]);
        return $this;
    }

    public function runBilling()
    {
        $this->command->handle();
        return $this;
    }
}