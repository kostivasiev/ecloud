<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('NSX Dhcp Delete ' . $this->data['id'] . ' : Started');
        $dhcp = Dhcp::withTrashed()->findOrFail($this->data['id']);

        try {
            $response = $dhcp->availabilityZone->nsxService()->delete('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->id);
            if ($response->getStatusCode() !== 200) {
                $message = 'NSX Dhcp Delete ' . $this->data['id'] . ' : Failed';
                Log::error($message, ['response' => $response]);
                $this->fail(new \Exception($message));
                return;
            }
        } catch (\Exception $exception) {
            $message = 'NSX Dhcp Delete ' . $this->data['id'] . ' : Exception';
            Log::error($message, ['exception' => $exception]);
            $this->fail(new \Exception($message));
            return;
        }

        Log::info('NSX Dhcp Delete ' . $this->data['id'] . ' : Finished');
    }
}
