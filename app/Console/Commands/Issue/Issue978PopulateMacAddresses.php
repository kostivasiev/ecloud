<?php


namespace App\Console\Commands\Issue;

use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;

/**
 * Class Issue978PopulateMacAddresses
 * Populates MAC addresses in the database for issue #978
 * @param string hostId
 * @package App\Console\Commands\Issue
 */
class Issue978PopulateMacAddresses extends Command
{
    protected $signature = 'issue:978-populatemacaddresses {hostId?}';
    protected $description = 'Populates MAC addresses for #978';

    public function handle()
    {
        /** @var Host $instance */

        $hosts = [];
        if ($this->argument('hostId') != null) {
            $host = Host::find($this->argument('hostId'));
            if (!$host) {
                $this->alert('Failed to find host');
                return Command::FAILURE;
            }

            $hosts[] = $host;
        } else {
            $hosts = Host::all();
        }

        foreach ($hosts as $host) {
            $this->doWork($host);
        }

        return Command::SUCCESS;
    }

    protected function doWork($host)
    {
        $availabilityZone = $host->hostGroup->availabilityZone;

        $this->line("Processing host: " . $host->id);

        if (!empty($host->mac_address)) {
            $this->line("MAC address already populated, skipping");
            return;
        }

        try {
            // Get the host spec from Conjurer
            $response = $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id . '/host/' . $host->id
            );
            $response = json_decode($response->getBody()->getContents());

            $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

            if (empty($macAddress)) {
                $this->alert('Failed to load eth0 address for host ' . $host->id);
                return;
            }

            $this->line('Found host on UCS MAC address ' . $macAddress);

            $host->mac_address = $macAddress;
            $host->saveQuietly();
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                $this->alert('Host was not found on UCS');
            }
            $this->alert('Invalid response from conjurer');
        } catch (\Exception $exception) {
            $this->alert('Failed to process host: ' . $exception->getMessage());
        }
    }
}
