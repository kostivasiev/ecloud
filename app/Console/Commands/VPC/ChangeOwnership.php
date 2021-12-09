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
    protected $signature = 'vpc:change-ownership {--vpc=} {--reseller=} {--date=today} {--T|test-run}';
    protected $description = 'Changes ownership of VPC to a new user';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if ($this->option('date') === 'today') {
            $date = Carbon::now()->format('d/m/Y');
        } else {
            try {
                $formattedDate = Carbon::createFromFormat('d/m/Y', $this->option('date'));
            } catch (\Exception $exception) {
                $this->comment('Invalid Date,  try again with the format DD/MM/YYYY');
                return Command::FAILURE;
            }
            $date = $formattedDate->format('d/m/Y');
        }

        $vpc = Vpc::findOrFail($this->option('vpc'));
        $reseller = $this->option('reseller');

        //act here
        $currentMetrics = BillingMetric::where('vpc_id', $vpc->id)->whereNull('end')->get();
        $currentMetricEndDate = Carbon::createFromFormat('d/m/Y', $date)->endOfDay();
        foreach ($currentMetrics as $currentMetric) {
            $newMetric = $currentMetric->toArray();
            unset($newMetric['created_at']);
            unset($newMetric['updated_at']);
            unset($newMetric['id']);
            $newMetric['start'] = $currentMetricEndDate;
            $newMetric['reseller_id'] = $reseller;
            if (!$this->option('test-run')) {
                BillingMetric::create($newMetric);
                $currentMetric->end = $currentMetricEndDate;
                $currentMetric->save();
            }
        }
        if (!$this->option('test-run')) {
            $vpc->update(['reseller_id' => $reseller]);
        }

        $this->info(
            sprintf(
                'VPC Ownership of %s has been moved to reseller_id %s.',
                $vpc->id,
                $reseller
            )
        );

        return Command::SUCCESS;
    }
}
