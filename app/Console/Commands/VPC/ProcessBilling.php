<?php

namespace App\Console\Commands\VPC;

use App\Models\V2\BillingMetric;
use App\Models\V2\DiscountPlan;
use App\Models\V2\Product;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use UKFast\Admin\Account\AdminClient as AccountAdminClient;
use UKFast\Admin\Billing\AdminClient as BillingAdminClient;
use UKFast\Admin\Billing\Entities\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessBilling
 * @package App\Console\Commands
 */
class ProcessBilling extends Command
{
    protected $signature = 'vpc:process-billing {--D|debug} {--T|test-run}';
    protected $description = 'Process eCloud VPC Billing';

    protected \DateTimeZone $timeZone;
    protected Carbon $startDate;
    protected Carbon $endDate;

    protected array $billing;
    protected array $discountBilling;
    protected float $runningTotal;

    public function __construct()
    {
        parent::__construct();
        $this->billing = [];
        $this->timeZone = new \DateTimeZone(config('app.timezone'));
        $this->startDate = Carbon::createFromTimeString("First day of last month 00:00:00", $this->timeZone);
        $this->endDate = Carbon::createFromTimeString("last day of last month 23:59:59", $this->timeZone);
        $this->runningTotal = 0.00;
    }

    public function handle()
    {
        $this->info('VPC billing for period ' . $this->startDate . ' - ' . $this->endDate . PHP_EOL);

        // First calculate discount plan values
        DiscountPlan::where('status', 'approved')
            ->where(function ($query) {
                $query->where('term_start_date', '<=', $this->startDate);
                $query->orWhereBetween('term_start_date', [$this->startDate, $this->endDate]);
            })->where('term_end_date', '>=', $this->endDate)
            ->each(function ($discountPlan) {
                if ($this->isUkFastAccount($discountPlan->reseller_id)) {
                    return;
                }

                if (!isset($this->discountBilling[$discountPlan->reseller_id])) {
                    $this->discountBilling[$discountPlan->reseller_id]['minimum_spend'] = 0;
                    $this->discountBilling[$discountPlan->reseller_id]['payg_threshold'] = 0;
                }

                if ($this->option('debug')) {
                    $this->info('Reseller Id: ' . $discountPlan->reseller_id);
                    $this->info('Discount plan ' . $discountPlan->id . ' found.');
                    $this->info(
                        'Term start: '.$discountPlan->term_start_date->format('Y-m-d H:i:s')
                        . PHP_EOL . 'Commitment Amount: £' . number_format($discountPlan->commitment_amount, 2)
                        . PHP_EOL . 'Commitment Before Discount: £' . number_format($discountPlan->commitment_before_discount, 2)
                        . PHP_EOL . 'Discount Rate: ' . $discountPlan->discount_rate
                    );
                }

                if ($discountPlan->term_start_date > $this->startDate) {
                    // Discount plan start date is mid-month for the billing period, calculate pro rata discount
                    $hoursInBillingPeriod = $this->startDate->diffInHours($this->endDate);

                    $hoursRemainingInBillingPeriodFromTermStart = $discountPlan->term_start_date->diffInHours($this->endDate);

                    $percentHoursRemaining = ($hoursRemainingInBillingPeriodFromTermStart / $hoursInBillingPeriod) * 100;

                    $proRataCommitmentAmount = ($discountPlan->commitment_amount / 100) * $percentHoursRemaining;

                    $proRataDiscountRate = ($discountPlan->discount_rate / 100) * $percentHoursRemaining;

                    if ($this->option('debug')) {
                        $this->info('Discount plan ' . $discountPlan->id . ' starts mid billing period. Calculating pro rata discount for this billing period.');
                        $this->info(
                            'Term start: '.$discountPlan->term_start_date->format('Y-m-d H:i:s')
                            . PHP_EOL . round($percentHoursRemaining) . '% of Billing period remaining'
                            . PHP_EOL . 'Calculated Pro Rata Commitment Amount: £' . number_format($proRataCommitmentAmount, 2)
                            . PHP_EOL . 'Calculated Pro Rata Discount Rate: ' . $proRataDiscountRate
                        );
                    }

                    $discountPlan->commitment_amount = $proRataCommitmentAmount;
                    $discountPlan->discount_rate = $proRataDiscountRate;
                }
                $this->info(PHP_EOL);
                $this->discountBilling[$discountPlan->reseller_id]['minimum_spend'] += $discountPlan->commitment_amount;
                $this->discountBilling[$discountPlan->reseller_id]['payg_threshold'] += $discountPlan->commitment_before_discount;
            });

        // Collect and sort VPC metrics by reseller
        Vpc::withTrashed()
            ->where('deleted_at', '>=', $this->startDate)
            ->orWhereNull('deleted_at')
            ->each(function ($vpc) {
                if ($this->isUkFastAccount($vpc->reseller_id)) {
                    return;
                }

                $metrics = $this->getVpcMetrics($vpc->id);

                if ($metrics->count() == 0) {
                    return true;
                }

                $metrics->keys()->each(function ($key) use ($metrics, $vpc) {

                    $metrics->get($key)->each(function ($metric) use ($key, $vpc) {
                        if ($metric->price > 0.00) {
                            if (!isset($this->billing[$vpc->reseller_id][$vpc->id]['metrics'][$key])) {
                                $this->billing[$vpc->reseller_id][$vpc->id]['metrics'][$key] = 0;
                            }
                            $start = $this->startDate;
                            $end = $this->endDate;

                            if (($metric->start > $this->startDate) || (!empty($metric->end) && $metric->end < $this->endDate)) {
                                $start = Carbon::parse($metric->start, $this->timeZone);
                            }

                            if (!empty($metric->end) && $metric->end < $this->endDate) {
                                $end = Carbon::parse($metric->end, $this->timeZone);
                            }

                            $hours = $start->diffInHours($end);

                            // Minimum period is 1 hour
                            $hours = ($hours < 1) ? 1 : $hours;

                            $cost = ($hours * $metric->price) * $metric->value;

                            $this->billing[$vpc->reseller_id][$vpc->id]['metrics'][$key] += $cost;
                        }
                    });
                });

                // VPC Support
                $this->billing[$vpc->reseller_id][$vpc->id]['support'] = [
                    'enabled' => false,
                    'pro-rata' => false
                ];

                $vpcSupport = $this->getVpcSupport($vpc->id);

                if (!empty($vpcSupport)) {
                    $this->billing[$vpc->reseller_id][$vpc->id]['support']['enabled'] = true;

                    // Incomplete month
                    if ($vpcSupport->start_date > $this->startDate) {
                        $this->billing[$vpc->reseller_id][$vpc->id]['support']['pro-rata'] = true;
                    }
                }

                if (!array_key_exists('metrics', $this->billing[$vpc->reseller_id][$vpc->id])) {
                    return true;
                }

                $total = array_sum($this->billing[$vpc->reseller_id][$vpc->id]['metrics']);

                $this->billing[$vpc->reseller_id][$vpc->id]['total'] = $total;
            });

        // Calculate the total for all VPC's for each reseller
        foreach ($this->billing as $resellerId => $vpcs) {
            $total = 0;
            $this->line('Reseller ID: ' . $resellerId . PHP_EOL);

            foreach ($vpcs as $vpcId => $vpc) {
                if (!array_key_exists('metrics', $vpc)) {
                    continue;
                }

                $this->line('---------- ' . $vpcId . ' ----------' . PHP_EOL);
                foreach ($vpc['metrics'] as $key => $val) {
                    $this->line($key . ': £' . number_format($val, 2));
                }

                $supportCost = 0;
                if ($vpc['support']['enabled'] === true) {
                    try {
                        $supportMinimumProduct = $this->getSupportMinimumProduct($vpcId);
                    } catch (\Exception $exception) {
                        $this->error($exception->getMessage());
                        Log::error(get_class($this) . ' : ' . $exception->getMessage());
                        return;
                    }

                    $supportMinimumPrice = $supportMinimumProduct->getPrice($resellerId);

                    // Support is calculated as the greater of 25% of the cost of the VPC, or the support minimum price.
                    $vpcBasedSupportCost = $vpc['total'] * 0.25;

                    $supportCost = ($vpcBasedSupportCost > $supportMinimumPrice) ? $vpcBasedSupportCost : $supportMinimumPrice;

                    if ($vpc['support']['pro-rata']) {
                        // A pro-rata payment will have been taken upfront already for the billing period, we just need to charge the difference
                        if ($vpcBasedSupportCost > $supportMinimumPrice) {
                            $supportCost = $vpcBasedSupportCost - $supportMinimumPrice;
                        }
                    }
                }

                $this->line(PHP_EOL . 'Usage Total: £' . number_format($vpc['total'], 2));

                $this->line('Support: £' . number_format($supportCost, 2));

                if ($this->option('debug') && $vpc['support']['pro-rata']) {
                    $this->info('Support started during this billing cycle - applying pro-rata support billing');
                }

                $vpcTotal = $vpc['total'] + $supportCost;

                $this->line(PHP_EOL . 'VPC Total: £' . number_format($vpcTotal, 2));

                $total += $vpcTotal;
            }

            $this->line('-----------------------------------');
            $this->line('Reseller ' . $resellerId . ' VPC\'s Total: £' . number_format($total, 2));

            // If reseller has a discount plan, then work out how much the spend should be
            if (isset($this->discountBilling[$resellerId]['payg_threshold'])) {
                if ($total < $this->discountBilling[$resellerId]['payg_threshold']) {
                    $total = $this->discountBilling[$resellerId]['minimum_spend'];
                } else {
                    $discountedTotal = $this->discountBilling[$resellerId]['minimum_spend'] +
                        ($total - $this->discountBilling[$resellerId]['payg_threshold']);
                    $total = $discountedTotal;
                }
                $this->line('Reseller ' . $resellerId . ' VPC\'s Total with Discounts: £' . number_format($total, 2) . PHP_EOL);
            } else {
                if ($this->option('debug')) {
                    $this->info('No discounts found for reseller '.$resellerId);
                }
            }

            // Don't create accounts logs when zero charges
            if ($total <= 0) {
                if ($this->option('debug')) {
                    $this->info('Reseller #' . $resellerId . ' has no billable charges - skipping accounts log entry.');
                }

                continue;
            }

            // Min £1 surcharge
            if ($total > 0) {
                $total = ($total < 1) ? 1 : $total;
                $this->addBillingToAccount($resellerId, $total);
            }

            // Remove the billed reseller from the array
            unset($this->discountBilling[$resellerId]);
            $this->runningTotal += $total;
        }

        foreach ($this->discountBilling as $resellerId => $discountTotals) {
            // Don't create accounts logs for ukfast accounts
            if ($this->isUkFastAccount($resellerId)) {
                continue;
            }
            $total = $discountTotals['minimum_spend'];

            $this->line('-----------------------------------');
            $this->line('Reseller ' . $resellerId . ' No VPC\'s Committed Spend Total: £' . number_format($total, 2) . PHP_EOL);

            if (!empty($total)) {
                $this->addBillingToAccount($resellerId, $total);
                $this->runningTotal += $total;
            }
        }

        if ($this->option('debug')) {
            $this->info('Running Total: £' . number_format($this->runningTotal, 2));
        }

        return Command::SUCCESS;
    }

    /**
     * Load the metrics for the VPC for the billing period
     * @param $vpcId
     * @return mixed
     */
    protected function getVpcMetrics($vpcId)
    {
        $metrics = BillingMetric::where('vpc_id', $vpcId)
            ->where(function ($query) {
                // any billing metrics for the vpc that start within the billing period
                $query->whereBetween('start', [$this->startDate, $this->endDate]);

                // any metrics that start before the billing period and end within it
                $query->orWhere('start', '<=', $this->startDate)
                    ->where('end', '<=', $this->endDate);

                // any metrics that start before the billing period are still active
                $query->orWhere(function ($query) {
                    $query->where('start', '<', $this->startDate)
                        ->whereNull('end');
                });

                $query->orWhere(function ($query) {
                    $query->where('start', '<', $this->startDate)
                        ->where('end', '>', $this->endDate);
                });
            })
            ->get();

        return $metrics->mapToGroups(function ($item, $key) {
            return [$item['key'] => $item];
        });
    }

    /**
     * @param $vpcId
     * @return VpcSupport|null
     */
    protected function getVpcSupport($vpcId): ?VpcSupport
    {
        return VpcSupport::where('vpc_id', $vpcId)
            ->where(function ($query) {
                $query->where('start_date', '<=', $this->startDate);
                $query->orWhereBetween('start_date', [$this->startDate, $this->endDate]);
            })
            ->where(function ($query) {
                $query->where('end_date', '>=', $this->endDate);
                $query->orWhereNull('end_date');
            })
            ->first();
    }

    /**
     * @param $vpcId
     * @return Product
     * @throws \Exception
     */
    protected function getSupportMinimumProduct($vpcId): Product
    {
        $availabilityZone = Vpc::withTrashed()
            ->findorFail($vpcId)
            ->region
            ->availabilityZones
            ->first();

        if (!$availabilityZone) {
            throw new \Exception('Failed to load default availability zone for VPC ' . $vpcId);
        }

        $supportProduct = $availabilityZone->products()
            ->where('product_name', 'LIKE', '%support minimum%')
            ->first();

        if (empty($supportProduct)) {
            throw new \Exception('Failed to load \'support minimum\' product for availability zone ' . $availabilityZone->id);
        }

        return $supportProduct;
    }

    /**
     * @param $resellerId
     * @param $total
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addBillingToAccount($resellerId, $total): void
    {
        $bilingAdminClient = app()->make(BillingAdminClient::class);
        $payment = new Payment([
            'description' => 'eCloud VPCs from ' . $this->startDate->format('d/m/Y') . ' to ' . $this->endDate->format('d/m/Y'),
            'category' => 'eCloud VPC',
            //'productId' => '',
            'resellerId' => $resellerId,
            'quantity' => 1,
            'date' => Carbon::now($this->timeZone)->format('c'),
            'dateFrom' => $this->startDate->format('c'),
            'dateTo' => $this->endDate->format('c'),
            'netpg' => '', // no payment taken, payment required
            'nominalCode' => '41003',
            'source' => 'myukfast',
            'cost' => number_format($total, 2, '.', ''),
            'vat' => 00.00
        ]);

        if (!$this->option('test-run')) {
            // Create acc.log entries
            try {
                $response = $bilingAdminClient->payments()->create($payment);
                if ($this->option('debug')) {
                    $this->info('Accounts Log ' . $response->getId() . ' created.');
                }
            } catch (\Exception $exception) {
                $error = 'Failed to crate accounts log for reseller ' . $resellerId;
                $this->error($error . $exception->getMessage());
                Log::error(get_class($this) . ' : ' . $error, [$exception->getMessage()]);
            }
        }
    }

    /**
     * @param $resellerId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isUkFastAccount($resellerId): bool
    {
        try {
            $customer = (app()->make(AccountAdminClient::class))->customers()->getById($resellerId);
            if ($customer->accountStatus == 'Internal Account') {
                return true;
            }
        } catch (\Exception $exception) {
            $error = 'Failed to load customer details for for reseller ' . $resellerId;
            $this->error($error . ' - ' . $exception->getMessage());
            Log::error(get_class($this) . ' : ' . $error, [$exception->getMessage()]);
        }
        return false;
    }

    public function calculateProRata($discountPlan)
    {
        if ($discountPlan->term_start_date <= $this->startDate) {
            return $discountPlan;
        }

        // Discount plan start date is mid-month for the billing period, calculate pro rata discount
        $hoursInBillingPeriod = $this->startDate->diffInHours($this->endDate);

        $hoursRemainingInBillingPeriodFromTermStart = $discountPlan->term_start_date->diffInHours($this->endDate);

        $percentHoursRemaining = ($hoursRemainingInBillingPeriodFromTermStart / $hoursInBillingPeriod) * 100;

        $proRataCommitmentAmount = ($discountPlan->commitment_amount / 100) * $percentHoursRemaining;

        $proRataCommitmentBeforeDiscount = ($discountPlan->commitment_before_discount / 100) * $percentHoursRemaining;

        $proRataDiscountRate = ($discountPlan->discount_rate / 100) * $percentHoursRemaining;

        if ($this->option('debug')) {
            $this->info('Discount plan ' . $discountPlan->id . ' starts mid billing period. Calculating pro rata discount for this billing period.');
            $this->info(
                'Term start: 2020-12-20 13:12:10'
                . PHP_EOL . round($percentHoursRemaining) . '% of Billing period remaining'
                . PHP_EOL . 'Original Commitment Amount: £' . number_format($discountPlan->commitment_amount, 2)
                . PHP_EOL . 'Calculated Pro Rata Commitment Amount: £' . number_format($proRataCommitmentAmount, 2)
                . PHP_EOL . 'Original Commitment Before Discount: £' . number_format($discountPlan->commitment_before_discount, 2)
                . PHP_EOL . 'Calculated Pro Rata Commitment Before Discount: £' . number_format($proRataCommitmentBeforeDiscount, 2)
                . PHP_EOL . 'Original Discount Rate: ' . $discountPlan->discount_rate
                . PHP_EOL . 'Calculated Pro Rata Discount Rate: ' . $proRataDiscountRate
            );
            $this->info(PHP_EOL);
        }

        $discountPlan->commitment_amount = $proRataCommitmentAmount;
        $discountPlan->commitment_before_discount = $proRataCommitmentBeforeDiscount;
        $discountPlan->discount_rate = $proRataDiscountRate;

        return $discountPlan;
    }
}
