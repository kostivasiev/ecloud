<?php
namespace App\Console\Commands\Orchestrator;

use App\Models\V2\BillingMetric;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Vpc;
use Illuminate\Console\Command;

class EnableSupport extends Command
{
    protected $signature = 'orchestrator:enable-support {--T|test-run}';
    protected $description = 'Enable support for VPCs created with support_enabled flag set to true';

    public function __construct(
        public int $updated = 0,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        OrchestratorBuild::where('created_at', '>=', '15-02-2022 00:00:00')
            ->each(function ($orchestratorBuild) {
                $configData = json_decode($orchestratorBuild->orchestratorConfig->data, true);
                if (!array_key_exists('vpcs', $configData)) {
                    return;
                }

                collect($configData['vpcs'])->each(function ($vpc, $index) use ($orchestratorBuild) {
                    if (array_key_exists('support_enabled', $vpc) && $vpc['support_enabled'] === true) {
                        if (empty($orchestratorBuild->state['vpc'])) {
                            return;
                        }

                        $id = $orchestratorBuild->state['vpc'][$index];

                        $vpc = Vpc::find($id);
                        if (empty($vpc)) {
                            $this->warn('VPC ' . $vpc->id . ' was not found, skipping');
                            return;
                        }

                        // check for any existing support billing metrics
                        if (BillingMetric::where('resource_id', $vpc->id)->where('key', 'vpc.support')->count() > 0) {
                            return;
                        }

                        if (!$this->option('test-run')) {
                            $billingMetric = app()->make(BillingMetric::class);
                            $billingMetric->fill([
                                'resource_id' => $vpc->id,
                                'vpc_id' => $vpc->id,
                                'category' => 'Support',
                                'reseller_id' => $vpc->reseller_id,
                                'name' => 'VPC Support',
                                'key' => 'vpc.support',
                                'value' => 1,
                                'start' => $vpc->created_at
                            ]);
                            $billingMetric->save();
                        }

                        $this->info('Support enabled for VPC ' . $vpc->id);

                        $this->updated++;
                    }
                });
        });

        $this->info('Total Updated: ' . $this->updated);

        return 0;
    }
}
