<?php
namespace App\Console\Commands\VPC;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandResponse;

/**
 * This script does the following:-
 * Change ownership of a VPC with Billing Metrics.
 *
 * Given I run the script | When the command initialises | Then I am prompted for vpc-id, new reseller-id, date of move
 * When valid data is provided | Then the vpc, and all active billing for that vpc, are moved to the new reseller
 * When no change-date is provided | Then the date defaults to the 1st of the current month
 */
class ChangeOwnership extends Command
{
    protected $signature = 'vpc:change-ownership {--T|test-run}';
    protected $description = 'Changes ownership of VPC to a new user';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //prepare
        restart:
        $vpcId = $this->ask('Please enter the VPC id:');
        $resellerId = $this->ask('Please enter the Reseller id:');
        $dateValid = false;
        $date = null;

        while ($dateValid === false) {
            if (!($date = $this->ask('Please enter the Date to Move (Leave blank for today): DD/MM/YYYY'))) {
                $date = Carbon::now()->format('d/m/Y');
                $dateValid = true;
            } else {
                if (!($formattedDate = Carbon::createFromFormat('d/m/Y', $date))) {
                    $this->comment('Invalid Date,  try again with the format DD/MM/YYYY');
                } else {
                    $date = $formattedDate->format('d/m/Y');
                    $dateValid = true;
                }
            }
        }

        $confirmed = false;
        while ($confirmed === false) {
            if (strtolower($this->ask(
                sprintf(
                    "Using VPC ID: [%s]. Reseller ID: [%s]. Date to Move: [%s]. Please type 'Y' to confirm.",
                    $vpcId,
                    $resellerId,
                    $date
                )
            )) === 'y') {
                $confirmed = true;
            } else {
                goto restart;
            }
        }

        //act here
        $currentMetrics = BillingMetric::where('vpc_id', $vpcId)->whereNull('end')->get();
        $currentMetricEndDate = Carbon::createFromFormat('d/m/Y', $date)->endOfDay();
        foreach ($currentMetrics as $currentMetric) {
            $newMetric = $currentMetric->toArray();
            unset($newMetric['created_at']);
            unset($newMetric['updated_at']);
            unset($newMetric['id']);
            $newMetric['start'] = $currentMetricEndDate;
            $newMetric['reseller_id'] = $resellerId;
            if (!$this->option('test-run')) {
                BillingMetric::create($newMetric);
                $currentMetric->end = $currentMetricEndDate;
                $currentMetric->save();
            }
        }
        if (!$this->option('test-run')) {
            Vpc::find($vpcId)->update(['reseller_id' => $resellerId]);
        }

        $this->info(
            sprintf(
                'VPC Ownership of %s has been moved to reseller_id %s.',
                $vpcId,
                $resellerId
            )
        );
    }
}
