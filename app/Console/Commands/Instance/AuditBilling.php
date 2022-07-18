<?php

namespace App\Console\Commands\Instance;

use App\Console\Commands\Command;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Services\V2\KingpinService;

class AuditBilling extends Command
{
    protected $signature = 'instance:audit-billing {--T|test-run}';
    protected $description = 'Audit instances without active compute billing';

    protected $total = 0;

    public function handle()
    {
        Instance::all()->each(function (Instance $instance) {
            $reason = [];

            $ram = BillingMetric::getActiveByKey($instance, 'ram.capacity');
            $ramHigh = BillingMetric::getActiveByKey($instance, 'ram.capacity.high');
            $vcpu = BillingMetric::getActiveByKey($instance, 'vcpu.count');

            if (empty($ram) && empty($ramHigh)) {
                $reason[] = 'No RAM Billing';
            }

            if (empty($vcpu)) {
                $reason[] = 'No vCPU Billing';
            }

            try {
                $response = $instance->availabilityZone
                    ->kingpinService()
                    ->get(
                        sprintf(KingpinService::GET_INSTANCE_URI, $instance->vpc_id, $instance->id)
                    );
            } catch (\Exception $e) {
                $this->error('Failed to query power state for instance ' . $instance->id . ': ' . $e->getMessage());
                return;
            }
            $powerState =  (json_decode($response->getBody()->getContents()))->powerState;

            if (!empty($reason) && $powerState === KingpinService::INSTANCE_POWERSTATE_POWEREDON) {
                $this->line($instance->id . ': ' . implode(', ', $reason));
                $this->total++;
            }
        });

        $this->line('Total powered on instances with no active compute billing: ' . $this->total);
    }
}
