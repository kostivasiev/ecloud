<?php

namespace App\Listeners\V2\FloatingIp;

use App\Events\V2\FloatingIp\Deleted;
use App\Models\V2\FloatingIp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use UKFast\SDK\SafeDNS\Entities\Record;
use UKFast\SDK\SafeDNS\RecordClient;

class ResetRdnsHostname implements ShouldQueue
{
    use InteractsWithQueue;

    private $rdns;
    private RecordClient $safednsClient;

    /**
     * @param Deleted $event
     * @return void
     * @throws \Exception
     */
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started');

        $this->safednsClient = app()->make(RecordClient::class);
        $floatingIp = FloatingIp::withTrashed()->findOrFail($event->model->id);

        $floatingIp->rdns_hostname = $this->safednsClient->defaultHostname;
        $floatingIp->save();

        $dnsName = $this->reverseIpLookup($floatingIp->ip_address);
        $this->rdns = $this->safednsClient->getByName($dnsName);

        $this->safednsClient->update($this->createRecord());

        Log::info(get_class($this) . ' : Finished');
    }

    private function reverseIpLookup($ip): string
    {
        return sprintf(
            '%s.%s',
            implode('.', array_reverse(explode('.', $ip))),
            $this->safednsClient->dnsSuffix
        );
    }

    private function createRecord(): Record
    {
        $record = [
            'id' => $this->rdns->id,
            'name' => $this->rdns->name,
            'zone' => $this->rdns->zone,
            'content' => $this->safednsClient->defaultHostname,
        ];

        return new Record($record);
    }
}