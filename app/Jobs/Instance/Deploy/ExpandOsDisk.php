<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ExpandOsDisk extends Job
{
    use Batchable;

    const RETRY_DELAY = 5;
    public $tries = 60;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/332
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $volume = $this->instance->volumes->first();

        if ($volume->sync->status != Sync::STATUS_COMPLETE) {
            $this->release(static::RETRY_DELAY);
            Log::info(get_class($this) . ' : primary volume is not in sync, retrying in ' . static::RETRY_DELAY . ' seconds');
            return;
        }

        $guestAdminCredential = $this->instance->credentials()
            ->where('username', ($this->instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'PrepareOsDisk failed for ' . $this->instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // Extend volume to expanded size
        $endpoint = ($this->instance->platform == 'Linux') ? 'linux/disk/lvm/extend' : 'windows/disk/expandall';
        $this->instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/guest/' . $endpoint,
            [
                'json' => [
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
