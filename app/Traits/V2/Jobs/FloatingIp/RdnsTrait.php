<?php

namespace App\Traits\V2\Jobs\FloatingIp;

use UKFast\Admin\SafeDNS\AdminClient;
use UKFast\SDK\Page;
use UKFast\SDK\SafeDNS\Entities\Record;

trait RdnsTrait
{
    public function reverseIpLookup($ip): string
    {
        return sprintf(
            config('defaults.floating-ip.rdns.dns_suffix'),
            implode('.', array_reverse(explode('.', $ip)))
        );
    }

    public function reverseIpDefault($ip): string
    {
        return sprintf(
            config('defaults.floating-ip.rdns.default_rdns'),
            $ip,
        );
    }

    public function createRecord($id, $name, $zone, $content): Record
    {
        $record = [
            'id' => $id,
            'name' => $name,
            'zone' => $zone,
            'content' => $content,
        ];

        return new Record($record);
    }

    public function getRecords($ip)
    {
        $safednsClient = app()->make(AdminClient::class);
        $dnsName = $this->reverseIpLookup($ip);

        return $safednsClient->records()->getPage(1, 15, ['name:eq' => $dnsName]);
    }
}
