<?php

namespace App\Jobs\Nsx\Host;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
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

        if (empty($host->mac_address)) {
            Log::warning("MAC address empty, skipping");
            return true;
        }

        // On deletion, we need to retrieve NSGroups that a transport node (HOST) is a member of, and remove it from those groups

        // use that to load host from kingpin - the name is the IP of the node
        try {
            $response = $availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $host->hostGroup->vpc_id . '/hostgroup/' . $host->hostGroup->id . '/host/' . $host->mac_address
            );
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                Log::info("Host doesn't exist, skipping");
                return true;
            }
            throw $exception;
        }

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

        if ($response->result_count == 0) {
            Log::warning('TransportNode node with name ' . $hostIpAddress . ' not found, skipping');
            return true;
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
            Log::warning('No NSGroups found which contain TransportNode ' . $transportNodeUuid . ', skipping');
            return true;
        }

        //loop over the ns groups and PUT the endpoint minus the node profile
        $nsGroups = $response->results;

        foreach ($nsGroups as $nsGroup) {
            $filteredMembers = [];
            foreach ($nsGroup->members as $member) {
                if ($member->value != $transportNodeUuid) {
                    $filteredMembers[] = $member;
                }
            }

            $nsGroup->members = $filteredMembers;
            $nsGroup->member_count--;
            unset($nsGroup->effective_member_count);

            $availabilityZone->nsxService()->put(
                '/api/v1/ns-groups/' . $nsGroup->id,
                [
                   'json' => $nsGroup
                ]
            );
        }
    }
}
