<?php

namespace App\Console\Commands\Host;

use App\Models\V2\Host;
use Illuminate\Console\Command;

/**
 * Class Delete
 * Delete an instance from vmware
 * @param string instanceId
 * @package App\Console\Commands\Kingpin\Instance
 */
class Delete extends Command
{
    protected $signature = 'host:delete {hostId}';
    protected $description = 'Dedicated Host Undeploy';

    public function handle()
    {
        /** @var Host $instance */
        $host = Host::find($this->argument('hostId'));
        if (!$host) {
            $this->alert('Failed to find host');
            return Command::FAILURE;
        }

        $availabilityZone = $host->hostGroup->availabilityZone;

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id .'/host/' . $host->id
        );
        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (empty($macAddress)) {
            $this->alert('Failed to load eth0 address for host ' . $host->id);
            return Command::FAILURE;
        }

        try {
            $availabilityZone->conjurerService()->delete(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id .'/host/' . $host->id
            );
        } catch (\Exception $exception) {
            $this->alert('Failed to delete Conjurer host profile for host ' . $host->id);
            return Command::FAILURE;
        }

        try {
            $availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $host->hostGroup->vpc->id .'/hostgroup/' . $host->hostGroup->id .'/host/' . $macAddress
            );
        } catch (\Exception $exception) {
            $this->alert('Failed to delete Kingpin host profile for host ' . $host->id);
            return Command::FAILURE;
        }

        $host->syncDelete();

        return Command::SUCCESS;
    }
}