<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Started');
        $nat = Nat::findOrFail($this->data['nat_id']);

        $oldRuleId = $this->data['original_destination'] . '-to-' . $this->data['original_translated'];
        if ($oldRuleId !== '-to-') {
            Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Deleting ' . $oldRuleId . ' NAT Rule');
            // TODO - Delete previous NAT rule from NSX
        }

        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Adding ' . $nat->rule_id . ' NAT Rule');
        // TODO - Add new NAT rule to NSX

        Log::info('Nat Deploy ' . $this->data['nat_id'] . ' : Finished');
    }
}
