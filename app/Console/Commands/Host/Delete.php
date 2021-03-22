<?php


namespace App\Console\Commands\Host;

use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
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
        $host = Host::withTrashed()->find($this->argument('hostId'));
        if (!$host) {
            $this->alert('Failed to find host');
            return Command::FAILURE;
        }
        $availabilityZone = $host->hostGroup->availabilityZone;

        try {
            // Get the host spec from Conjurer
            $response = $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id . '/host/' . $host->id
            );
            $response = json_decode($response->getBody()->getContents());


            $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

            if (empty($macAddress)) {
                $this->alert('Failed to load eth0 address for host ' . $host->id);
                //return Command::FAILURE;
            }
            $this->line('Found host on UCS MAC address ' . $macAddress);

            try {
                $availabilityZone->conjurerService()->delete(
                    '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id . '/host/' . $host->id
                );
                $this->line('Deleted host from UCS');
            } catch (\Exception $exception) {
                $this->alert('Failed to delete Conjurer host profile for host ' . $host->id);
                //return Command::FAILURE;
            }

            if (!empty($macAddress)) {
                try {
                    $availabilityZone->kingpinService()->delete(
                        '/api/v2/vpc/' . $host->hostGroup->vpc->id . '/hostgroup/' . $host->hostGroup->id . '/host/' . $macAddress
                    );
                    $this->line('Deleted host from VMWare');
                } catch (\Exception $exception) {
                    $this->alert('Failed to delete Kingpin host profile for host ' . $host->id);
                    //return Command::FAILURE;
                }
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                $this->alert('Host was not found on UCS ');
            }
        }

        try {
            $availabilityZone->artisanService()->delete(
                '/api/v2/san/' . $availabilityZone->san_name . '/host/' . $host->id
            );
            $this->line('Deleted host from SAN');
        } catch (\Exception $exception) {
            $this->alert('Failed to delete host from Artisan ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $host->syncDelete();

        return Command::SUCCESS;
    }
}
