<?php

namespace App\Console\Commands\VPC;

use App\Models\V2\BillingMetric;
use App\Models\V2\DiscountPlan;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use Illuminate\Console\Command;
use UKFast\Admin\Billing\AdminClient;
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

    /**
     * Billable metrics - Add any metrics to this array that we want to bill for.
     * @var array|string[]
     */
    protected array $billableMetrics = [
        'vcpu.count',
        'ram.capacity',
        'disk.capacity',
        'license.windows'
    ];

    public function handle()
    {
        $vpcs = Vpc::get();

        $this->timeZone = new \DateTimeZone(config('app.timezone'));
        $this->startDate = Carbon::createFromTimeString("First day of last month 00:00:00", $this->timeZone);
        $this->endDate = Carbon::createFromTimeString("last day of last month 23:59:59", $this->timeZone);

        $this->info('VPC billing for period ' . $this->startDate . ' - ' . $this->endDate . PHP_EOL);

        // Collect and sort VPC metrics by reseller
        $vpcs->each(function ($vpc) {
            $metrics = $this->getVpcMetrics($vpc->id);

            if ($metrics->count() == 0) {
                return true;
            }

            $metrics->keys()->each(function ($key) use ($metrics, $vpc) {
                if (!in_array($key, $this->billableMetrics)) {
                    return true;
                }

                $this->billing[$vpc->reseller_id][$vpc->id]['metrics'][$key] = 0;

                $metrics->get($key)->each(function ($metric) use ($key, $vpc) {
                    $start = $this->startDate;
                    $end = $this->endDate;

                    if ($metric->start > $this->startDate) {
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
                });
            });

            $total = array_sum($this->billing[$vpc->reseller_id][$vpc->id]['metrics']);

            $this->billing[$vpc->reseller_id][$vpc->id]['total'] = $total;
        });

        // Calculate the total for all VPC's for each reseller
        foreach ($this->billing as $resellerId => $vpcs) {
            $total = 0;
            $this->line('Reseller ID: ' . $resellerId . PHP_EOL);

            foreach ($vpcs as $vpcId => $vpc) {
                $this->line('---------- ' . $vpcId . ' ----------' . PHP_EOL);

                foreach ($vpc['metrics'] as $key => $val) {
                    $this->line($key . ': £' . number_format($val, 2));
                }

                $this->line(PHP_EOL . 'Total: £' . number_format($vpc['total'], 2));

                $total += $vpc['total'];
            }

            $this->line('-----------------------------------');
            $this->line('Reseller ' . $resellerId . ' VPC\'s Usage Total: £' . number_format($total, 2) . PHP_EOL);

            // Apply any discount plans
            $discountPlans = DiscountPlan::where('reseller_id', $resellerId)
                ->where('status', 'accepted')
                ->where(function ($query) {
                    $query->where('term_start_date', '<=', $this->startDate);
                    $query->orWhereBetween('term_start_date', [$this->startDate, $this->endDate]);
                })
                ->where('term_end_date', '>=', $this->endDate)
                ->get();

            $discountsToApply = collect();

            $discountPlans->each(function ($discountPlan) use ($total, &$discountsToApply) {
                if ($discountPlan->term_start_date <= $this->startDate) {
                    $discountsToApply->add($discountPlan);
                }

                if ($discountPlan->term_start_date > $this->startDate) {
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

                    $discountsToApply->add($discountPlan);
                }
            });

            if ($discountsToApply->count() > 0) {
                $this->line('Applying ' . $discountsToApply->count() . ' discounts...');

                if ($total < $discountsToApply->max('commitment_amount')) {
                    // Charge at least the largest commitment amount
                    $discountedTotal = $discountsToApply->max('commitment_amount');
                    $total = $discountedTotal;
                } else {
                    foreach ($discountsToApply as $discountPlan) {
                        if ($total <= $discountPlan->commitment_before_discount) {
                            $discountedTotal = $discountPlan->commitment_amount;
                        } else {
                            //$total > $discountPlan->commitment_before_discount
                            $difference = $total - $discountPlan->commitment_before_discount;

                            $discountedTotal = $discountPlan->commitment_amount + $difference;

                            if ($this->option('debug')) {
                                $this->info('Applying discount ' . $discountPlan->id . '...' . PHP_EOL . 'New Total: £' . $discountedTotal);
                            }
                        }
                        $total = $discountedTotal;
                    }
                }
                $this->line(PHP_EOL . 'Total after discounts: £' . number_format($total, 2));
            } else {
                if ($this->option('debug')) {
                    $this->info('No discounts found');
                }
            }

            // Min £1 surcharge
            $total = ($total < 1) ? 1 : $total;

            $bilingAdminClient = app()->make(AdminClient::class);
            $payment = new Payment([
                'description' => 'eCloud resource usage from ' . $this->startDate->format('d/m/Y') . ' to ' . $this->endDate->format('d/m/Y'),
                'category' => 'eCloud v2',
                //'productId' => '',
                'resellerId' => $resellerId,
                'quantity' => 1,
                'date' => Carbon::now($this->timeZone)->format('c'),
                'dateFrom' => $this->startDate->format('c'),
                'dateTo' => $this->endDate->format('c'),
                'netpg' => '', // no payment taken, payment required
                'nominalCode' => '41003',
                'source' => 'myukfast',
                'cost' => number_format($total, 2),
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
                    Log::error($error, [$exception->getMessage()]);
                }
            }
        }
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
}
