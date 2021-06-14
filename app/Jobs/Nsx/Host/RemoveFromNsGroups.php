<?php

namespace App\Jobs\Nsx\Host;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveFromNsGroups extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $host = $this->model;
        $availabilityZone = $host->hostGroup->availabilityZone;

        // On deletion, we need to retrieve NSGroups that a transport node (HOST GROUP) is a member of, and remove it from those groups

        // Get the host MAC address from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->model->hostGroup->vpc->id .'/host/' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());
        if (!$response) {
            $this->fail(new \Exception('Failed to get host compute profile'));
            return;
        }

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $this->model->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // use that to load host from kingpin - the name is the IP of the node
        $response = $availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $host->hostGroup->vpc_id . '/hostgroup/' . $host->hostGroup->id . '/host/' . $macAddress
        );
        $response = json_decode($response->getBody()->getContents());
        if (!$response) {
            $this->fail(new \Exception('Failed to retrieve host from Kingpin'));
            return;
        }

        $hostIpAddress = $response->name;

        //use the IP of the node to query NSX for the transport node so that we can get the ID GET https://185.197.63.88/api/v1/search/query?query=resource_type:TransportNode%20AND%20display_name:172.19.0.57
        $response = $availabilityZone->nsxService()->get('/api/v1/search/query?query=resource_type:TransportNode%20AND%20display_name:' . $hostIpAddress);
        $response = json_decode($response->getBody()->getContents());
        if (!$response) {
            $this->fail(new \Exception('Failed to query transport nodes'));
            return;
        }

        if ($response->result_count != 1) {
            $this->fail(new \Exception('Failed to load TransportNode for host ' . $host->id . ' with IP ' . $hostIpAddress));
            return;
        }

        $transportNodeUuid = $response->results[0]->id;

        //using the uuid get the ns groups that have the transport node as a member GET https://185.197.63.88/api/v1/search/query?query=resource_type:NSGroup%20AND%20members.value:ebe3adf5-c920-4442-b7fb-573e28d543c1
        $response = $availabilityZone->nsxService()->get('/api/v1/search/query?query=resource_type:NSGroup%20AND%20members.value:' . $transportNodeUuid);
        $response = json_decode($response->getBody()->getContents());
        if (!$response) {
           $this->fail(new \Exception('Failed to load NSGroups which contain TransportNode ' . $transportNodeUuid));
           return;
        }

        if ($response->result_count < 1) {
            $this->fail(new \Exception('No NSGroups found which contain TransportNode ' . $transportNodeUuid));
            return;
        }


        //loop over the ns groups and PUT the endpoint minus the node profile
        $nsGroups = $response->results;

        foreach ($nsGroups as $nsGroup) {
           $members = collect($nsGroup->members)->filter(function ($member) use ($transportNodeUuid) {
               return $member->value != $transportNodeUuid;
           })->toArray();

           $nsGroup->members = $members;

           $nsGroup->effective_member_count--;
           $nsGroup->member_count--;

           $availabilityZone->nsxService()->put('/api/v1/ns-groups/' . $nsGroup->id,
               [
                   'json' => $nsGroup
               ]
           );
        }
    }
}
