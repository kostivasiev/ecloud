<?php
namespace Tests\V2\Console;

use App\Console\Commands\VPC\ProcessBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Models\V2\ProductPriceCustom;
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

        // turn on debug and test-run
        $this->command->expects('option')
            ->with('debug')
            ->times(2)
            ->andReturnUsing(function () {
                return true;
            });
        $this->command->expects('option')
            ->zeroOrMoreTimes()
            ->with('test-run')
            ->andReturnUsing(function () {
                return true;
            });

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

    /**
     * @param $object
     * @param $propertyName
     * @param $propertyValue
     * @throws \ReflectionException
     */
    public function setProtectedProperty($object, $propertyName, $propertyValue)
    {
        $reflection = new ReflectionProperty(get_class($object), $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $propertyValue);
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

    public function getLineArgumentPrice($code)
    {
        foreach ($this->lineArgument as $line) {
            if (preg_match('/' . $code . ':[^0-9]+([0-9\.]+)/i', $line, $matches)) {
                return (float) $matches[1];
            }
        }
        return null;
    }
}