<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\SafeDNS\AdminRecordClient;
use UKFast\SDK\SafeDNS\Entities\Record;
use UKFast\SDK\SafeDNS\RecordClient;

class AllocateRdnsHostname extends Job
{
    use Batchable, LoggableModelJob;

    public FloatingIp $model;
    private bool $hasUpdated = false;
    private Record $rdns;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->model = $floatingIp;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (empty($this->model->ip_address)) {
            log::info("Floating IP has not been assigned, RDNS host not generated.");
            $this->fail(new \Exception('Floating IP has not been assigned, RDNS host not generated ' . $this->model->id));
            return;
        }

        $safednsClient = app()->make(AdminRecordClient::class);
        $dnsName = $this->reverseIpLookup($this->model->ip_address);
        $this->rdns = $safednsClient->getPage(1, 15, ['name:eq' => $dnsName]);

        if (count($this->rdns->getItems()) !== 1) {
            log::info("More than one RDNS found");
            $this->fail(new \Exception('More than one RDNS found ' . $this->model->id));
            return;
        }

        $this->rdns = $this->rdns->getItems()[0];

        if (empty($this->model->rdns_hostname)) {
            if (!empty($this->rdns['content']) && $this->rdns) {
                $this->model->rdns_hostname = $this->rdns['content'];
            } else {
                $this->model->rdns_hostname = config('defaults.floating-ip.rdns.default_hostname');
            }
            $this->model->save();

            log::info('RDNS assigned.', $this->model->id);
        } else {
            $safednsClient->update($this->createRecord());

            log::info('RDNS updated.', $this->model->id);
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
            'id' => $this->rdns['id'],
            'name' => $this->rdns['name'],
            'zone' => $this->rdns['zone'],
            'content' => $this->model->rdns_hostname,
        ];

        return new Record($record);
    }
}
