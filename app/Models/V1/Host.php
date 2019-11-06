<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\IntProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Host extends Model implements Filterable, Sortable
{
    const RESERVED_SYSTEM_RAM = 2;


    /**
     * Eloquent configuration
     * ----------------------
     */

    protected $table = 'ucs_node';
    protected $primaryKey = 'ucs_node_id';
    public $timestamps = false;


    /**
     * Ditto configuration
     * ----------------------
     */

    public static $adminProperties = [
        'ucs_node_internal_name'
    ];


    /**
     * Ditto maps raw database names to friendly names.
     * @return array
     */
    public function databaseNames()
    {
        $databaseNames = [];

        foreach ($this->properties() as $property) {
            if (is_array($property)) {
                foreach ($property as $subProperty) {
                    $databaseNames[$subProperty->getFriendlyName()] = $subProperty->getDatabaseName();
                }
                continue;
            }

            $databaseNames[$property->getFriendlyName()] = $property->getDatabaseName();
        }

        return $databaseNames;
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
        ];
    }


    /**
     * Ditto sorting configuration
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $sortFactory)
    {
        return [
            $sortFactory->create('id', 'asc'),
        ];
    }


    /**
     * Resource configuration
     * ----------------------
     */

    /**
     * Map request property to database field
     *
     * @return array
     * @throws \UKFast\Api\Resource\Exceptions\InvalidPropertyException
     */
    public function properties()
    {
        $properties = [
            IdProperty::create('ucs_node_id', 'id'),

            IntProperty::create('ucs_node_ucs_reseller_id', 'solution_id'),
            IntProperty::create('ucs_node_datacentre_id', 'pod_id'),

            StringProperty::create('ucs_specification_friendly_name', 'name'),


            'cpu' => [
                IntProperty::create('ucs_specification_cpu_qty', 'qty'),
                IntProperty::create('ucs_specification_cpu_cores', 'cores'),
                StringProperty::create('ucs_specification_cpu_speed', 'speed'),
            ],

            'ram' => [
                IntProperty::create('ucs_specification_ram', 'capacity'),
            ],
        ];

        $request = app('request');
        if (!$request->user->isAdministrator) {
            return $properties;
        }

        // admin only properties
        return array_merge($properties, [
            StringProperty::create('ucs_node_internal_name', 'internal_name'),
            IntProperty::create('ucs_node_reseller_id', 'reseller_id'),
            StringProperty::create('ucs_specification_name', 'specification'),
        ]);
    }

    /**
     * Model Methods
     * ----------------------
     */


    /**
     * Scope a query for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('ucs_node_reseller_id', $resellerId);
        }

        return $query;
    }

    public function scopeWithSolution($query, $solutionId)
    {
        $solutionId = filter_var($solutionId, FILTER_SANITIZE_NUMBER_INT);

        $query->where('ucs_node_ucs_reseller_id', $solutionId);
        return $query;
    }

    /**
     * Return Pod
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pod()
    {
        return $this->hasOne(
            'App\Models\V1\Pod',
            'ucs_datacentre_id',
            'ucs_node_datacentre_id'
        );
    }

    /**
     * Returns the SOlution that the host belongs to
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function solution()
    {
        return $this->hasOne(
            'App\Models\V1\Solution',
            'ucs_reseller_id',
            'ucs_node_ucs_reseller_id'
        );
    }

    /**
     * Get the RAM from the host specification
     * todo: can we use ucs_specification_ram?
     *
     * @return mixed]
     */
    public function getRamSpecification()
    {
        $specificationName = $this->hasOne(
            'App\Models\V1\HostSpecification',
            'ucs_specification_id',
            'ucs_node_specification_id'
        )
            ->select('ucs_specification_name')
            ->pluck('ucs_specification_name')
            ->first();

        return intval(substr($specificationName, strpos($specificationName, '--')+2));
    }

    /**
     * get VMware usage stats
     */
    public function getVmwareUsage()
    {
        if (!is_null($this->usage)) {
            return $this->usage;
        }

        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
                $this->pod
            ]);

            $vmwareHost = $kingpin->getHostByMac(
                $this->ucs_node_ucs_reseller_id,
                $this->ucs_node_eth0_mac
            );
        } catch (\Exception $exception) {
            throw $exception;
        }

        //collect host stats
        $ramCapacity = intval($this->ucs_specification_ram);
        $ramAllocated = 0;
        $ramReserved = static::RESERVED_SYSTEM_RAM;

        $virtualMachines = [];
        foreach ($vmwareHost->vms as $vm) {
            if ($vm->id > 0) {
                $ramAllocated += $vm->ramGB;
                $virtualMachines[] = $vm->id;
            }
        }

        return $this->usage = json_decode(json_encode([
            'ram' => [
                'capacity' => $ramCapacity,
                'reserved' => $ramReserved,
                'allocated' => $ramAllocated,
                'available' => ($ramCapacity - $ramAllocated - $ramReserved),
            ],
            'virtualMachines' => $virtualMachines
        ]));
    }

    /**
     * Rescan the host's cluster on vmware
     * @throws \Exception
     */
    public function clusterRescan()
    {
        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
                $this->solution->pod,
                $this->solution->ucs_reseller_type
            ]);

            if (!$kingpin->clusterRescan($this->solution->getKey())) {
                throw new \Exception('Failed to perform cluster rescan: ' . $kingpin->getLastError());
            }
        } catch (\Exception $exception) {
            throw new \Exception('Failed to perform cluster rescan ' . $exception->getMessage());
        }

        return true;
    }

    /**
     * We have 4 columns on the host record storing Fibre Channel World Wide Port Names.
     * Determine which ones are set and return as an array.
     * @return array
     */
    public function getFCWWNs()
    {
        $fcwwns = [];
        for ($i = 0; $i < 4; $i++) {
            $wwn = 'ucs_node_fc'. $i .'_wwpn';
            if (!empty($this->$wwn)) {
                $fcwwns[] = $this->$wwn;
            }
        }

        return $fcwwns;
    }
}
