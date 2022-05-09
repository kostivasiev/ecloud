<?php

namespace App\Console\Commands\Billing;

use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Console\Commands\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SetFriendlyNames extends Command
{
    protected $signature = 'billing:set-friendly-names {--T|test-run} {--R|reset}';
    protected $description = 'Sets the friendly name field for historical billing metrics';

    public function handle()
    {
        $this->info('Setting friendly names for Billing Metrics' . PHP_EOL);
        $billableClasses = $this->getBillableClasses();

        if ($this->option('reset')) {
            BillingMetric::each(function ($metric) {
                $this->info('Billing Metric: ' . $metric->id . ' setting friendly name to NULL');
                $metric->setAttribute('name', null)->saveQuietly();
                return;
            });
            return Command::SUCCESS;
        }

        BillingMetric::each(function ($metric) use ($billableClasses) {
            $friendlyName = null;
            if (preg_match(
                '/^((?:throughput|license|license\.mssql|host|load\-balancer|disk\.capacity)\.)'.
                '((?:\d+(?:mb|gb)|plesk|cpanel|standard|web|enterprise|small|medium|large|\d+|(?:lbs|hs)-[a-z0-9]+))$/i',
                $metric->key,
                $matches
            )) {
                $this->info('Setting: ' . $matches[0] . ' with key:' . $matches[1] . ' and value:' . $matches[2]);
                if (array_key_exists($matches[1], $billableClasses)) {
                    $friendlyName = $billableClasses[$matches[1]]::getFriendlyName($matches[2]);
                }
            } else {
                if ($metric->key == 'network.advanced') {
                    $metric->key = 'networking.advanced';
                }
                if (array_key_exists($metric->key, $billableClasses)) {
                    $friendlyName = $billableClasses[$metric->key]::getFriendlyName($metric->key);
                }
            }
            if ($friendlyName === null) {
                return;
            }
            $this->info('Billing Metric: ' . $metric->id . ' : ' . $metric->key . ' setting friendly name to ' . $friendlyName);
            if (!$this->option('test-run')) {
                $metric->setAttribute('name', $friendlyName)->saveQuietly();
            }
        });

        return Command::SUCCESS;
    }

    /**
     * Gets all the Listeners that implement the Billable interface. Use the autoload_classmap to avoid making the
     * filesystem reads, and pull everything from the array that's returned from the classmap, then call the getKeyName
     * method statically so that we can make an array classmap based on the codes that we use in the billing_metrics
     * table.
     * @return array
     */
    private function getBillableClasses(): array
    {
        $billableClasses = [];
        $classMap = require __DIR__ . '/../../../../vendor/composer/autoload_classmap.php';
        foreach ($classMap as $key => $value) {
            if (preg_match('/^App\\\\Listeners\\\\V2\\\\.*$/i', $key)) {
                $className = Arr::last(Str::of($key)->explode('\\')->toArray());
                if ($className !== 'Billable') {
                    $instance = app()->make($key);
                    if ($instance instanceof Billable) {
                        $className = Arr::last(Str::of($key)->explode('\\')->toArray());
                        if ($className == 'UpdateRamBilling') {
                            $items = json_decode($instance::getKeyName(''));
                            foreach ($items as $item) {
                                $billableClasses[$item] = $instance;
                            }
                        } else {
                            $billableClasses[$instance::getKeyName('')] = $instance;
                        }
                    }
                }
            }
        }
        return $billableClasses;
    }
}
