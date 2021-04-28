<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteDhcps extends Job
{
    use Batchable;

    private Vpc $vpc;

    public function __construct(Vpc $vpc)
    {
        $this->vpc = $vpc;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->vpc->id]);

        $this->vpc->dhcps()->each(function ($dhcp) {
            $dhcp->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->vpc->id]);
    }
}
