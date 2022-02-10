<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use UKFast\Admin\SafeDNS\AdminClient;
use UKFast\SDK\SafeDNS\Entities\Record;

class AllocateRdnsHostname extends TaskJob
{
    use Batchable, LoggableModelJob;

    public FloatingIp $model;
    private $rdns;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->model = $this->task->resource;
        if (empty($this->model->ip_address)) {
            $this->info("Floating IP has not been assigned, RDNS host not generated.", ['floating_ip_id' => $this->model->id]);
            $this->fail(new \Exception('Floating IP has not been assigned, RDNS host not generated ' . $this->model->id));

            return;
        }

        if (empty($this->model->rdns_hostname)) {
            $this->model->rdns_hostname = config('defaults.floating-ip.rdns.default_hostname');
        }

        $safednsClient = app()->make(AdminClient::class);
        $dnsName = $this->reverseIpLookup($this->model->ip_address);
        $this->rdns = $safednsClient->records()->getPage(1, 15, ['name:eq' => $dnsName]);

        if (count($this->rdns->getItems()) !== 1) {
            $this->info("More than one RDNS found", ['floating_ip_id' => $this->model->id]);
            $this->fail(new \Exception('More than one RDNS found ' . $this->model->id));

            return;
        }

        $this->rdns = $this->rdns->getItems()[0];

        if ($this->rdns['content'] != trim($this->model->rdns_hostname)) {
            $safednsClient->records()->update($this->createRecord());
        }

        $this->model->save();

        $this->info(sprintf('RDNS assigned [%s]', $this->model->id));
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
