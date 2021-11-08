<?php
namespace App\Console\Commands\VPC;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandResponse;

/**
 * This script does the following:-
 *
 * 1. Set an end date of 1st Nov 2021 on any active billing metrics
 * 2. Insert a new entry using same data, but start date of 1st Nov 2021 with the correct price
 * 3. Update billing price for any metric created after 1st Nov 2021
 */
class ConvertBilling extends Command
{
    protected $signature = 'vpc:convert-billing {--T|test-run}';
    protected $description = 'Converts billing entries to fix rounding issue';

    protected \DateTimeZone $timeZone;
    public Carbon $startDate;
    public Carbon $endDate;
    public Carbon $lastPeriodEnd;

    public array $codeMap = [];
    public array $keysToRemove = [];

    public function __construct()
    {
        parent::__construct();
        $this->timeZone = new \DateTimeZone(config('app.timezone'));
        $this->startDate = Carbon::parse('2021-11-01 00:00:00', $this->timeZone);
        $this->endDate = Carbon::parse('2021-11-30 23:59:59');
        $this->lastPeriodEnd = Carbon::parse('2021-10-31 23:59:59');

        $this->codeMap = [
            'backup.quota' => 'backup',
            'disk.capacity' => 'advanced networking',
            'disk.capacity.1200' => 'volume@1200',
            'disk.capacity.2500' => 'volume@2500',
            'disk.capacity.300' => 'volume@300',
            'disk.capacity.600' => 'volume@600',
            'floating-ip.count' => 'floating ip',
            'host.license.windows' => 'host windows-os-license',
            'hostgroup' => 'hostgroup',
            'image.private' => 'volume-1gb',
            'license.windows' => 'windows-os-license',
            'load-balancer.Medium' => 'load balancer Medium',
            'networking.advanced' => 'advanced networking',
            'ram.capacity' => 'ram-1mb',
            'ram.capacity.high' => 'ram:high-1mb',
            'throughput.100Mb' => 'throughput 100Mb',
            'throughput.10GB' => 'throughput 10GB',
            'throughput.1Gb' => 'throughput 1Gb',
            'throughput.2.5Gb' => 'throughput 2.5Gb',
            'throughput.20MB' => 'throughput 20MB',
            'throughput.250Mb' => 'throughput 250Mb',
            'throughput.25Mb' => 'throughput 25Mb',
            'throughput.500Mb' => 'throughput 500Mb',
            'throughput.50Mb' => 'throughput 50Mb',
            'vcpu.count' => 'vcpu',
            'vpn.session.ipsec' => 'site to site vpn',
        ];

        $this->keysToRemove = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    public function handle()
    {
        $this->info('Fix billing entries for period ' . $this->startDate . ' - ' . $this->endDate . PHP_EOL);

        BillingMetric::where(function ($query) {
            $query->whereBetween('start', [$this->startDate, $this->endDate]); // anything started in period
            $query->orWhereBetween('end', [$this->startDate, $this->endDate]); // or anything ended in period
            $query->orWhereNull('end'); // or anything that hasn't ended
        })->each(function ($metric) {
            $this->info('Processing metric: ' . $metric->id . ' - ' . $metric->key);

            // If the VPC doesn't exist anymore, skip
            if ($metric->vpc) {
                // First let's find the correct price!
                $productPrice = $this->getProductPrice($metric);
                $this->line('Old Price: ' . number_format($metric->price, 12));
                $this->line('New Price: ' . number_format($productPrice, 12));

                // if end is null then we need to set the end date to end of last billing period
                // duplicate the entry, change start date to beginning of this period, and change the price
                if (is_null($metric->end)) {
                    $this->line('Ending old metric');
                    $metric->setAttribute('end', $this->lastPeriodEnd);
                    // Start building the new metric
                    $attributes = array_diff_key($metric->getAttributes(), array_flip($this->keysToRemove));
                    $newMetric = new BillingMetric($attributes);
                    $newMetric->price = $productPrice;
                    $newMetric->start = $this->startDate;
                    $newMetric->end = null;
                    $this->line('Creating new metric:-');
                    $this->line('Price: ' . number_format($productPrice, 12));
                    $this->line('Start: ' . $newMetric->start);
                    $this->line('End: ' . $newMetric->end);
                    if (!$this->option('test-run')) {
                        $newMetric->save();
                    }
                } else {
                    $this->line('Changing existing metric price');
                    $metric->price = $productPrice;
                    $this->line('Price: ' . number_format($productPrice, 12));
                    $this->line('Start: ' . $metric->start);
                    $this->line('End: ' . $metric->end);
                }
                if (!$this->option('test-run')) {
                    $metric->save();
                }
            } else {
                $this->line('Vpc ' . $metric->vpc_id . ' no longer exists. ' . $metric->id . ' skipped');
            }
            $this->line('-----------------------------------');
        });
        return CommandResponse::SUCCESS;
    }

    public function getProductPrice(BillingMetric $metric)
    {
        $resource = $metric->getResource();

        if (!$metric->vpc->region) {
            $this->line('Region for metric ' . $metric->id . ' not found, skipping');
            return $metric->price;
        }

        $availabilityZone = $metric->vpc->region->availabilityZones()->first(); // fallback
        if ($resource) {
            if (property_exists('availability_zone_id', $resource)) {
                $newAz = AvailabilityZone::find($resource->availability_zone_id);
                if ($newAz) {
                    $availabilityZone = $newAz;
                }
            }
        }

        $this->line('Availability Zone: ' . $availabilityZone->id);

        // host price is based on hostspec used
        if (strpos($metric->key, 'host.hs' !== false)) {
            $hostSpecId = explode('.', $metric->key)[1];
            $product = $availabilityZone->products()
                ->where('product_name', 'LIKE', '%' . $hostSpecId)
                ->first();
        } else {
            if (array_key_exists($metric->key, $this->codeMap)) {
                $product = $availabilityZone->products()
                    ->where('product_name', 'LIKE', '%' . $availabilityZone->id . ': ' . $this->codeMap[$metric->key] . '%')
                    ->first();
            } else {
                $this->line('Price for metric ' . $metric->key . ' not found, skipping');
                return $metric->price;
            }
        }
        $this->line('Product: ' . $product->id);
        return $product->getPrice($metric->reseller_id);
    }
}
