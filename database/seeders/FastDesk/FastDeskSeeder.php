<?php

namespace Database\Seeders\FastDesk;

use App\Models\V2\FloatingIp;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

return new class extends Seeder {

    use WithoutModelEvents;

    public function run()
    {
        $fastDeskMgmt = VpnService::find('vpn-191bd289');
        $fastDeskShared = VpnService::find('vpn-47da7cbc');
        $vpn34291 = VpnService::find('vpn-890e1ab4');

        // FastDesk Management - eCloud VPC
        FloatingIpResource::factory()
            ->for(FloatingIp::find('fip-1d93e06e'))
            ->hasAttached(
                VpnEndpoint::factory()
                    ->for($fastDeskMgmt)
                    ->make([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'FastDesk Management - eCloud VPC'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);

        VpnSession::factory()
            ->create([
                'id' => $this->addCustomKey(VpnSession::class),
            ]);

        // FastDesk Management -> Fastdesk Old
        FloatingIpResource::factory()
            ->for(FloatingIp::find('fip-727faf58'))
            ->hasAttached(
                VpnEndpoint::factory()
                    ->for($fastDeskMgmt)
                    ->make([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'FastDesk Management -> Fastdesk Old'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);

        // Fastdesk Shared Client - eCloud VPC
        FloatingIpResource::factory()
            ->for(FloatingIp::find('fip-495722a7'))
            ->hasAttached(
                VpnEndpoint::factory()
                    ->for($fastDeskShared)
                    ->make([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'Fastdesk Shared Client - eCloud VPC'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);

        // vpne_3429_1
        FloatingIpResource::factory()
            ->for(FloatingIp::find('fip-84e34f0c'))
            ->hasAttached(
                VpnEndpoint::factory()
                    ->for($vpn34291)
                    ->make([
                        'id' => $this->addCustomKey(VpnEndpoint::class),
                        'name' => 'vpne_3429_1'
                    ])
            )->create([
                'id' => $this->addCustomKey(FloatingIpResource::class),
            ]);
    }

    public function addCustomKey(string $className): string
    {
        $model = new $className;
        $suffix = App::environment() === 'local' ? '-dev' : '';

        try {
            do {
                $modelId = $model->keyPrefix . '-' . bin2hex(random_bytes(4)) . $suffix;
            } while ($model->withTrashed()->find($modelId));
        } catch (\Exception $exception) {
            Log::error('Failed to set Custom Key on ' . get_class($model), [
                $exception,
            ]);
            throw $exception;
        }

        return $modelId;
    }

};
