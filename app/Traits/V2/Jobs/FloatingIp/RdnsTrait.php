<?php

namespace App\Traits\V2\Jobs\FloatingIp;

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
            implode('.', array_reverse(explode('.', $ip))),
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
}