<?php

namespace App\Console\Commands\Billing;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Product;
use App\Models\V2\ProductPrice;
use App\Console\Commands\Command;
use Doctrine\DBAL\Exception;

class SetProductDescriptions extends Command
{
    protected array $descriptions = [
        'vcpu' => '1 vCPU',
        'ram' => '1GB RAM (upto 24GB)',
        'ram:high' => '1GB RAM (over 24GB)',
        'windows' => 'Windows License (per vcpu)',
        'backup' => 'Backup 1GB Storage',
        'volume@300' => 'Volume Storage 1GB @ 300 IOPs',
        'volume@600' => 'Volume Storage 1GB @ 600 IOPs',
        'volume@1200' => 'Volume Storage 1GB @ 1200 IOPs',
        'volume@2500' => 'Volume Storage 1GB @ 2500 IOPs',
        'support_minimum' => 'Support Minimum Charge',
        'throughput_50mb' => 'Router Throughput 50Mbps',
        'throughput_250mb' => 'Router Throughput 250Mbps',
        'throughput_100mb' => 'Router Throughput 100Mbps',
        'throughput_500mb' => 'Router Throughput 500Mbps',
        'throughput_25Mb' => 'Router Throughput 25Mbps',
        'throughput_2.5GB' => 'Router Throughput 2.5Gbps',
        'throughput_1Gb' => 'Router Throughput 1Gbps',
        'volume' => 'Volume Storage 1GB',
        'hostgroup' => 'Host Group',
        'host_windows' => 'Host - Windows',
        'floating_ip' => 'Floating IP',
        'advanced_networking' => 'Advanced Networking',
        'site_to_site_vpn' => 'Site-to-Site VPN',
        'load_balancer_small' => 'Load Balancer - Small',
        'load_balancer_medium' => 'Load Balancer - Medium',
        'load_balancer_large' => 'Load Balancer - Large',
        'plesk' => 'Plesk',
        'cpanel' => 'cPanel',
        'mssql_standard_2_core_pack' => 'MSSQL Standard 2 Core Pack',
        'mssql_web_2_core_pack' => 'MSSQL Web 2 Core Pack',
        'mssql_enterprise_2_core_pack' => 'MSSQL Enterprise 2 Core Pack',
        'software:mcafee' => 'McAfee Antivirus',
    ];

    protected $signature = 'billing:set-product-descriptions {--T|test-run}';

    protected $description = 'Set the product descriptions for eCloud VPC products';

    public function handle()
    {
        AvailabilityZone::all()->each(function ($availabilityZone) {
            $products = $availabilityZone->products();
            $products->get()->each(function ($product) {
                if (isset($this->descriptions[$product->name])) {
                    $this->info('Setting description for \'' . $product->product_name . '\' to: \'' . $this->descriptions[$product->name] . '\'');
                    if (!$this->option('test-run')) {
                        $product->product_description = $this->descriptions[$product->name];
                        $product->save();
                    }
                }
            });

        });



        return Command::SUCCESS;
    }
}
