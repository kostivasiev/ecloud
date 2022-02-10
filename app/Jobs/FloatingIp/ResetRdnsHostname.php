<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\SafeDNS\AdminClient;
use UKFast\SDK\SafeDNS\Entities\Record;

class ResetRdnsHostname extends TaskJob implements ShouldQueue
{
    use InteractsWithQueue, Batchable;

    private $rdns;
    private $safednsClient;

    /**
     * @return void
     * @throws GuzzleException
     */
    public function handle()
    {
        /** @var FloatingIp $model */
        $model = $this->task->resource;
        $this->info(get_class($this) . ' : Started');

        $this->safednsClient = app()->make(AdminClient::class);
        $floatingIp = FloatingIp::withTrashed()->findOrFail($model->id);

        $floatingIp->rdns_hostname = config('defaults.floating-ip.rdns.default_hostname');
        $floatingIp->save();

        $dnsName = $this->reverseIpLookup($floatingIp->ip_address);

        $this->rdns = $this->safednsClient->records()->getPage(1, 15, ['name:eq' => $dnsName]);

        if (count($this->rdns->getItems()) !== 1) {
            $this->info("Unable to determine RDNS record, can not update.");
            $this->fail(new \Exception('Unable to determine RDNS record, can not update.' . $model->id));

            return;
        }

        $this->rdns = $this->rdns->getItems()[0];

        if ($this->safednsClient->records()->update($this->createRecord())) {
            $this->info(get_class($this) . ' : Finished');
        } else {
            Log::error("Failed to reset SafeDNS Record for FIP." . $model->id);
            $this->fail("Failed to reset SafeDNS Record for FIP." . $model->id);
        }
    }

    private function reverseIpLookup($ip): string
    {
        return sprintf(
            '%s.%s',
            implode('.', array_reverse(explode('.', $ip))),
            config('defaults.floating-ip.rdns.dns_suffix')
        );
    }

    private function createRecord(): Record
    {
        $record = [
            'id' => $this->rdns->id,
            'name' => $this->rdns->name,
            'zone' => $this->rdns->zone,
            'content' => config('defaults.floating-ip.rdns.default_hostname'),
        ];

        return new Record($record);
    }
}
