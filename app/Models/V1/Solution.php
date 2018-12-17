<?php

namespace App\Models\V1;

use App\Exceptions\V1\KingpinException;
use App\Exceptions\V1\SolutionNotFoundException;
use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Solution extends Model implements Filterable, Sortable
{
    const NAME_FORMAT_DESC = 'Alphanumeric, spaces, hyphens and underscores';
    const NAME_FORMAT_REGEX = '^[A-Za-z0-9\-\_\ \.]+$';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ucs_reseller';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'ucs_reseller_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types
     *
     * @var array
     */
    protected $casts = [
        'ucs_reseller_id' => 'integer',
    ];


    /**
     * Ditto configuration
     * ----------------------
     */


    /**
     * Ditto maps raw database names to friendly names.
     * @return array
     */
    public function databaseNames()
    {
        return [
            'id' => 'ucs_reseller_id',
            'name' => 'ucs_reseller_solution_name',
            'type' => 'ucs_reseller_type',
        ];
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns($factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('type', Filter::$stringDefaults),
        ];
    }


    /**
     * Ditto sorting configuration
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns($factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('type'),
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort($sortFactory)
    {
        return [
            $sortFactory->create('id', 'asc'),
        ];
    }


    /**
     * Ditto Selectable persistent Properties
     * @return array
     */
    public function persistentProperties()
    {
        return ['id'];
    }


    /**
     * Resource package
     * Map request property to database field
     *
     * @return array
     */
    public function properties()
    {
        return [
            IdProperty::create('ucs_reseller_id', 'id'),
            StringProperty::create('ucs_reseller_solution_name', 'name'),
            StringProperty::create('ucs_reseller_type', 'type'),
        ];
    }

    /**
     * Scope a query to only include solutions for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('ucs_reseller_reseller_id', $resellerId);
        }

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
            'ucs_reseller_datacentre_id'
        );
    }

    /**
     * Get the Hosts (ucs_nodes) for a solution
     * @param null $datacentreId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hosts($datacentreId = null)
    {
        $query = $this->hasMany(
            'App\Models\V1\Host',
            'ucs_node_ucs_reseller_id',
            'ucs_reseller_id'
        )->where('ucs_node_status', '=', 'Completed');

        if (!is_null($datacentreId) && is_numeric($datacentreId)) {
            $query->where('ucs_node_datacentre_id', '=', $datacentreId);
        }

        return $query->get();
    }


    /**
     * Get the total RAM in GB for a Solution by adding up the RAM specification of all the Solution's Hosts
     * @param null $datacentreId Limit to a particular datacentre
     * @return int calculated total (based on host specification name)
     */
    public function ramTotal($datacentreId = null)
    {
        $ramTotal = 0;

        $hosts = $this->hosts($datacentreId);

        foreach ($hosts as $host) {
            $ramTotal += $host->getRamSpecification();
        }

        return $ramTotal;
    }


    /**
     * Calculate total RAM allocated to VM's
     * @param null $datacentreId
     * @return int
     * @throws KingpinException
     */
    public function ramAllocated($datacentreId = null)
    {
        $ramAllocated = 0;

        // Load hosts from database
        $hostDatabaseRecords = $this->hosts($datacentreId);
        if (empty($hostDatabaseRecords)) {
            return $ramAllocated;
        }

        // Load hosts for Solution from VMWare
        try {
            $kingpin = app()->makeWith(
                'App\Kingpin\V1\KingpinService',
                [
                    $this->pod()->first(),
                    'Hybrid'
                ]
            );
        } catch (\Exception $exception) {
            throw new KingpinException('Unable to load hosts');
        }

        try {
            $vmwareHosts = $kingpin->getHostsForSolution($this->getKey(), true);
        } catch (KingpinException $exception) {
            return $ramAllocated;
        }

        if (empty($vmwareHosts)) {
            return $ramAllocated;
        }

        // Sort db hosts by eth0_mac
        $hostsDatabaseRecordsSorted = [];
        foreach ($hostDatabaseRecords as $hostDatabaseRecord) {
            if (is_null($datacentreId) || $hostDatabaseRecord->ucs_node_datacentre_id == $datacentreId) {
                $hostsDatabaseRecordsSorted[strtolower($hostDatabaseRecord->ucs_node_eth0_mac)] = $hostDatabaseRecord;
            }
        }

        // Foreach vmware host check against db host record and add RAM or continue
        foreach ($vmwareHosts as $vmwareHost) {
            $hostDatabaseRecord = $hostsDatabaseRecordsSorted[$vmwareHost->macAddress];
            if (!$hostDatabaseRecord instanceof Host) {
                continue;
            }

            foreach ($vmwareHost->vms as $virtualMachine) {
                if ($virtualMachine->id > 0) {
                    $ramAllocated += $virtualMachine->ramGB;
                }
            }
        }

        return $ramAllocated;
    }


    /**
     * get total RAM reserved for system use and N+ redundancy if active
     * @return int calculated total
     */
    public function ramReserved($datacentreId = null)
    {
        $ramReserved = 0;
        $hostRam = [];

        $hosts = $this->hosts($datacentreId);

        foreach ($hosts as $host) {
            $hostRam[] = $host->getRamSpecification();
        }

        $totalHostsCount = count($hostRam);

        if ($totalHostsCount > 0) {
            if ($this->isNPlusOneActive()) {
                // N+1 requires us to reserve one nodes total RAM
                $ramReserved += $hostRam[0] - Host::RESERVED_SYSTEM_RAM;
            } elseif ($this->isMultiSite()) {
                // N+N requires us to reserve half of all nodes total RAM
                for ($i = 0; $i < ($totalHostsCount / 2); $i++) {
                    //todo: need to check this is correct. This is not half the total ram, its half
                    //todo: the largest ram * half the count hosts?
                    $ramReserved += intval($hostRam[0] / 2) - Host::RESERVED_SYSTEM_RAM;
                }
            }

            $ramReserved += $totalHostsCount * Host::RESERVED_SYSTEM_RAM;
        }

        return $ramReserved;
    }


    /**
     * Get the total ram available to allocate
     * @param null $datacentreId
     * @return int
     * @throws KingpinException
     */
    public function ramAvailable($datacentreId = null)
    {
        $total = $this->ramTotal($datacentreId);
        $allocated = $this->ramAllocated($datacentreId);
        $reserved = $this->ramReserved($datacentreId);

        return $total - ($allocated + $reserved);
    }

    /**
     * Is N+1 active on an account
     * @return boolean
     */
    public function isNPlusOneActive()
    {
        return $this->attributes['ucs_reseller_nplusone_active'] == 'Yes';
    }


    /**
     * check if a solution is a multisite solution (two or more active sites)
     * @return bool
     */
    public function isMultiSite()
    {
        return $this->hasMany(
            'App\Models\V1\SolutionSite',
            'ucs_site_ucs_reseller_id',
            'ucs_reseller_id'
        )
                ->where('ucs_site_state', '=', 'Active')
                ->limit(2)
                ->count() > 1;
    }


    /**
     * Get Datastores for a Solution
     * @param null $UCSSiteId
     * @return array
     * @throws \Exception
     */
    public function datastores($UCSSiteId = null)
    {
        $solutionDatastores = [];
        try {
            $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', [$this->pod]);
            //Load the solution datastores from VMWare
            $datastores = $kingpin->getDatastores($this->getKey());
        } catch (\Exception $exception) {
            throw $exception;
        }

        if (!empty($datastores)) {
            foreach ($datastores as $datastore) {
                //Load the datastore record
                $datastoreQuery = Datastore::query()
                    ->withName($datastore->name)
                    ->withReseller($this->attributes['ucs_reseller_id']);

                if (!empty($UCSSiteId)) {
                    $datastoreQuery->where('reseller_lun_ucs_site_id', '=', $UCSSiteId);
                }

                $datastoreRes = $datastoreQuery->first();

                if ($datastoreRes instanceof Datastore) {
                    $solutionDatastores[] = $datastoreRes;
                }
            }
        }

        return $solutionDatastores;
    }
}
