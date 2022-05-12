<?php

namespace App\Console\Commands\Nic;

use App\Models\V2\Nic;
use App\Console\Commands\Command;

class DeletePivotDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nic:delete-pivot-duplicates {--T|test-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete pivot duplicates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $updated = 0;

        Nic::all()->each(function ($nic) use (&$updated) {
            $records = $nic->ipAddresses()->pluck('id');
            $unique = $records->unique();

            if ($unique->count() != $records->count()) {
                $diff = $records->diffAssoc($unique);

                $this->info('Removing duplicates for ' . $nic->id . ':' . $diff->implode(','));

                if (!$this->option('test-run')) {
                    $nic->ipAddresses()->sync([]);
                    $nic->ipAddresses()->sync($unique->toArray());
                }
                $updated++;
            }
        });

        $this->info('Total: ' . $updated . ' records removed');

        return 0;
    }
}
