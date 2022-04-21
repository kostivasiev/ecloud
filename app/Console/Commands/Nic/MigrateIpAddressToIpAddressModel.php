<?php

namespace App\Console\Commands\Nic;

use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MigrateIpAddressToIpAddressModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nic:migrate-ip-address {--T|test-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate ip address from NIC model to IpAddress Model';


    public function __construct(
        public int $updated = 0,
        public int $errors = 0
    ) {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Nic::all()->each(function ($nic) {
            $lock = Cache::lock("ip_address." . $nic->network_id, 60);
            try {
                $lock->block(60);
                if (empty($nic->getOriginal('ip_address'))) {
                    return;
                }

                $ipAddress = $nic->ipAddresses()->firstOrNew(
                    [
                        'ip_address' => $nic->getOriginal('ip_address'),
                        'network_id' => $nic->network_id
                    ],
                    [
                        'type' => IpAddress::TYPE_DHCP
                    ]
                );

                if ($ipAddress->exists && $ipAddress->type != IpAddress::TYPE_DHCP) {
                    $this->error(
                        'Failed to add Ip address record with IP ' . $nic->getIPAddress() .
                        ' on network ' . $nic->network_id .
                        '. The IP address is already in use.'
                    );
                    $this->errors++;
                    return;
                }

                if (!$this->option('test-run')) {
                    $nic->ipAddresses()->save($ipAddress);

                    if ($ipAddress->wasRecentlyCreated) {
                        $this->info(
                            'IpAddress record ' . $ipAddress->id . ' created for ' .
                            $ipAddress->getIpAddress() . ' on network ' . $nic->network_id
                        );
                        $this->updated++;
                    }

                    $nic->ip_address = null;
                    $nic->save();
                } else {
                    if (!$ipAddress->exists) {
                        $this->updated++;
                    }
                }
            } finally {
                $lock->release();
            }
        });

        $this->info('Total Updated: ' . $this->updated . ', Total Errors: ' . $this->errors);
        return 0;
    }
}
