<?php
namespace App\Console\Commands\VPC;

use App\Listeners\V2\Vpc\UpdateSupportEnabledBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use App\Models\V2\VpcSupport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * This script does the following:-
 *
 * 1. Gets current active VPC Supports and puts them into a new billing metric
 * 1a. (Optional) Gets history of VPC Supports and puts them into new billing metric.
 * 2. Verifies the data is still valid.
 */
class ConvertVpcSupportToFlag extends Command
{
    protected $signature = 'vpc:convert-support-flag {--T|test-run}';
    protected $description = 'Converts VPC Support entries to flags tracked by billing metrics.';

    private Collection $vpcSupportActive;
    private Collection $vpcSupportHistory;

    private bool $testMode;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(bool $testing = false)
    {
        $this->testMode = $testing;
        $this->vpcSupportActive = VpcSupport::withoutTrashed()->get();
        $this->vpcSupportHistory = VpcSupport::onlyTrashed()->get();
        $i = 0;
        if (!$this->testMode && strtoupper($this->ask("Would you like to include history? (Y/N)")) === 'Y') {
            foreach ($this->vpcSupportHistory as $vpcHistory) {
                if (!empty($vpcHistory->vpc)) {
                    $this->saveSupport(
                        $vpcHistory->vpc,
                        Carbon::parse($vpcHistory->start_date),
                        $vpcHistory->end_date ? Carbon::parse($vpcHistory->end_date) : null,
                        false
                    );
                    $i++;
                } else {
                    $this->info('[History] VPC Support has no VPC, Vpc Support ID: %s', $vpcHistory->id);
                }
            }
        }


        foreach ($this->vpcSupportActive as $vpcActive) {
            if (!empty($vpcActive->vpc)) {
                $this->saveSupport(
                    $vpcActive->vpc,
                    Carbon::parse($vpcActive->start_date),
                    $vpcActive->end_date ? Carbon::parse($vpcActive->end_date) : null
                );
                $i++;
            } else {
                $this->info('[Active] VPC Support has no VPC, Vpc Support ID: %s', $vpcActive->id);
            }
        }

        if (!$this->testMode) {
            $this->info(sprintf('Successfully moved %s VPC Support entries to flags.', $i));
        }
    }

    private function saveSupport(Vpc $vpc, Carbon $start, ?Carbon $end = null, $active = true)
    {
        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $vpc->id;
        $billingMetric->vpc_id = $vpc->id;
        $billingMetric->reseller_id = $vpc->reseller_id;
        $billingMetric->start = $start;
        $billingMetric->name = UpdateSupportEnabledBilling::getFriendlyName();
        $billingMetric->key = UpdateSupportEnabledBilling::getKeyName();
        $billingMetric->value = 1;
        if ($end !== null) {
            $billingMetric->end = $end;
            $vpc->support_enabled = true;
        } elseif ($active === false) {
            $billingMetric->end = Carbon::now();
        }
        if ($this->testMode || !$this->option('test-run')) {
            $vpc->save();
            $billingMetric->save();
        }
    }
}
