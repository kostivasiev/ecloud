<?php

namespace App\Console\Commands\Nat;

use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FindOrphaned extends Command
{
    protected $signature = 'nat:find-orphaned';

    protected $description = 'Finds orphaned NAT records';

    public function handle()
    {
        $failed = false;
        foreach (Nat::all() as $nat) {
            if (!empty($nat->source_id) && !$nat->source()->exists()) {
                $this->error("Orphaned NAT {$nat->id} exists for deleted source {$nat->source_id}");
                $failed = true;
            }
            if (!empty($nat->destination_id) && !$nat->destination()->exists()) {
                $this->error("Orphaned NAT {$nat->id} exists for deleted destination {$nat->destination_id}");
                $failed = true;
            }
            if (!empty($nat->translated_id) && !$nat->translated()->exists()) {
                $this->error("Orphaned NAT {$nat->id} exists for deleted translated {$nat->translated_id}");
                $failed = true;
            }
        }

        if ($failed) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
