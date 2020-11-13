<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Support\Resource;
use Illuminate\Support\Facades\Log;

class Assign extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $resource = Resource::classFromId($this->data['resource_id'])::findOrFail($this->data['resource_id']);
        $floatingIp = FloatingIp::findOrFail($this->data['floating_ip_id']);

        $nat = app()->make(Nat::class);
        $nat->destination()->associate($floatingIp);  // fIP
        $nat->translated()->associate($resource);    //NIC
        $nat->action = Nat::ACTION_DNAT;
        $nat->save();
        Log::info(get_class($this) . ' : DNAT created: destination: ' . $floatingIp->getKey() . ' translated: ' . $resource->getKey());

        $nat = app()->make(Nat::class);
        $nat->source()->associate($resource); // NIC
        $nat->translated()->associate($floatingIp); //fIP
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();
        Log::info(get_class($this) . ' : SNAT created: source: ' . $resource->getKey() . ' translated: ' . $floatingIp->getKey());

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
