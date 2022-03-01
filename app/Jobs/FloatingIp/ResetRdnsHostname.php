<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Traits\V2\Jobs\FloatingIp\RdnsTrait;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\SafeDNS\AdminClient;

class ResetRdnsHostname extends TaskJob implements ShouldQueue
{
    use InteractsWithQueue, Batchable, RdnsTrait;

    /**
     * @return void
     * @throws GuzzleException
     */
    public function handle()
    {
        /** @var FloatingIp $model */
        $model = $this->task->resource;
        $this->info(get_class($this) . ' : Started');

        $safednsClient = app()->make(AdminClient::class);

        $model->rdns_hostname = $this->reverseIpDefault($model->ip_address);
        $model->save();

        $dnsName = $this->reverseIpLookup($model->ip_address);

        $rdns = $safednsClient->records()->getPage(1, 15, ['name:eq' => $dnsName]);

        if (count($rdns->getItems()) !== 1) {
            $this->info("Unable to determine RDNS record, can not update.");
            $this->fail(new \Exception('Unable to determine RDNS record, can not update.' . $model->id));

            return;
        }

        $rdns = $rdns->getItems()[0];

        if ($safednsClient->records()->update($this->createRecord(
            $rdns->id,
            $rdns->name,
            $rdns->zone,
            $model->rdns_hostname
        ))) {
            $this->info(get_class($this) . ' : Finished');
        } else {
            Log::error("Failed to reset SafeDNS Record for FIP." . $model->id);
            $this->fail("Failed to reset SafeDNS Record for FIP." . $model->id);
        }
    }
}
