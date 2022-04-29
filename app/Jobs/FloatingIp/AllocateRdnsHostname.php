<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Traits\V2\Jobs\FloatingIp\RdnsTrait;
use Illuminate\Bus\Batchable;
use Illuminate\Queue\InteractsWithQueue;
use UKFast\Admin\SafeDNS\AdminClient;

class AllocateRdnsHostname extends TaskJob
{
    use Batchable, InteractsWithQueue, RdnsTrait;

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
            $this->model->rdns_hostname = $this->reverseIpDefault($this->model->ip_address);
        }

        $safednsClient = app()->make(AdminClient::class);
        $dnsName = $this->reverseIpLookup($this->model->ip_address);
        $this->rdns = $safednsClient->records()->getPage(1, 15, ['name:eq' => $dnsName]);

        $rdnsCount = count($this->rdns->getItems());

        if ($rdnsCount === 0) {
            $message = sprintf('No RDNS found on %s', $this->model->id);
            $this->info($message, ['floating_ip_id' => $this->model->id]);
            $this->fail(new \Exception($message));

            return;
        } elseif ($rdnsCount > 1) {
            $message = sprintf('%d RDNS found on %s', $rdnsCount, $this->model->id);
            $this->info($message, ['floating_ip_id' => $this->model->id]);
            $this->fail(new \Exception($message));

            return;
        }

        $this->rdns = $this->rdns->getItems()[0];

        if ($this->rdns['content'] != trim($this->model->rdns_hostname)) {
            $safednsClient->records()->update($this->createRecord(
                $this->rdns['id'],
                $this->rdns['name'],
                $this->rdns['zone'],
                $this->model->rdns_hostname
            ));
        }

        $this->model->save();

        $this->info(sprintf('RDNS assigned [%s]', $this->model->id));
    }
}
