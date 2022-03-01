<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ExpandOsDisk extends Job
{
    use Batchable, LoggableModelJob;

    const RETRY_DELAY = 5;
    public $tries = 60;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/332
     */
    public function handle()
    {
        $volume = $this->model->volumes->first();

        if ($volume->sync->status != Sync::STATUS_COMPLETE) {
            $this->release(static::RETRY_DELAY);
            Log::info(get_class($this) . ' : primary volume is not in sync, retrying in ' . static::RETRY_DELAY . ' seconds');
            return;
        }

        $guestAdminCredential = $this->model->credentials()
            ->where('username', ($this->model->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'PrepareOsDisk failed for ' . $this->model->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // Extend volume to expanded size
        $endpoint = ($this->model->platform == 'Linux') ? 'linux/disk/lvm/extend' : 'windows/disk/expandall';
        $this->model->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/' . $endpoint,
            [
                'json' => [
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
    }
}
