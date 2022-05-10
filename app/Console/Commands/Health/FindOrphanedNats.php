<?php

namespace App\Console\Commands\Health;

use App\Models\V2\Nat;
use App\Console\Commands\Command;
use Illuminate\Support\Carbon;

class FindOrphanedNats extends Command
{
    protected $signature = 'health:find-orphaned-nats {--F|force}';

    protected $description = 'Finds orphaned NAT records';

    public function handle()
    {
        $failed = false;

        $nats = Nat::query()
            ->where('updated_at', '<=', Carbon::now()->addHours(-12))->get();

        foreach ($nats as $nat) {
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
