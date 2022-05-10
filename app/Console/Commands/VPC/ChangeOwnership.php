<?php
namespace App\Console\Commands\VPC;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use App\Console\Commands\Command;
use Illuminate\Support\Facades\Log;
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
            $date = Carbon::now()->startOfMonth()->format('Y-m-d');
        } else {
            try {
                $formattedDate = Carbon::createFromFormat('Y-m-d', $this->option('date'));
            } catch (\Exception $exception) {
                $this->comment('Invalid Date,  try again with the format YYYY-MM-DD');
                return Command::FAILURE;
            }
            $date = $formattedDate->format('Y-m-d');
        }

        $vpc = Vpc::findOrFail($this->option('vpc'));
        $reseller = $this->option('reseller');

        //act here
        $currentMetrics = BillingMetric::where('vpc_id', $vpc->id)->whereNull('end')->get();
        $currentMetricEndDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();

        if ($currentMetrics->first()->reseller_id == $reseller) {
            $this->error(sprintf('Reseller %s already owns this vpc.', $reseller));

            return Command::FAILURE;
        }

        foreach ($currentMetrics as $currentMetric) {
            $newMetric = $currentMetric->replicate(['id', 'created_at', 'updated_at'])
                ->fill([
                    'start' => $currentMetricEndDate,
                    'reseller_id' => $reseller,
                ]);
            if (!$this->option('test-run')) {
                $newMetric->save();
                $currentMetric->end = $currentMetricEndDate;
                $currentMetric->save();
            }
        }
        if (!$this->option('test-run')) {
            $vpc->update(['reseller_id' => $reseller]);
        }

        $feedback = sprintf(
            'VPC Ownership of %s has been moved to reseller_id %s.',
            $vpc->id,
            $reseller
        );

        $this->info($feedback);
        Log::info($feedback);

        return Command::SUCCESS;
    }
}
