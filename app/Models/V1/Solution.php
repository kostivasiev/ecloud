<?php

namespace App\Models\V1;

use App\Exceptions\V1\KingpinException;
use App\Solution\EncryptionBillingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use UKFast\Api\Resource\Property\BooleanProperty;
use UKFast\Api\Resource\Property\DateTimeProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

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
     * Model properties to surface to customers
     */
    const VISIBLE_SCOPE_RESELLER = [
        'ucs_reseller_id',
        'ucs_reseller_solution_name',
        'ucs_reseller_datacentre_id',
        'ucs_reseller_type',
        'ucs_reseller_encryption_enabled',
        'ucs_reseller_encryption_default'
    ];

    // Validation Rules
    public static $rules = [
        'name' => ['nullable'],
        'environment' => ['required', 'in:Hybrid,Private'],
        'pod_id' => ['required'],
        'reseller_id' => ['required', 'numeric'],
        'active' => ['nullable', 'boolean'],
        'status' => ['required'],
        'windows_licence' => ['nullable'],
        'redundant_firewall' => ['nullable'],
        'nplusone_active' => ['nullable'],
        'nplus_redundancy' => ['nullable'],
        'nplus_overprovision' => ['nullable'],
        'min_term' => ['nullable'],
        'start_date' => ['sometimes', 'date_format:"Y-m-d H:i:s'],
        'source' => ['nullable'],
        'can_move_between_luns' => ['nullable', 'boolean'],
        'saleorder_id' => ['nullable', 'numeric'],
        'encryption_enabled' => ['nullable', 'boolean'],
        'encryption_default' => ['nullable', 'boolean'],
    ];

    /**
     * Return model (Create) validation rules
     * @return array
     * @throws \ReflectionException
     */
    public static function getRules()
    {
        $rules = static::$rules;
        $rules['encryption_billing_type'] = ['sometimes', Rule::in(EncryptionBillingType::all())];
        return $rules;
    }


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
            'environment' => 'ucs_reseller_type',
            'pod_id' => 'ucs_reseller_datacentre_id',
            'reseller_id' => 'ucs_reseller_reseller_id',
            'active' => 'ucs_reseller_active',
            'status' => 'ucs_reseller_status',
            'windows_licence' => 'ucs_reseller_windows_licence',
            'redundant_firewall' => 'ucs_reseller_redundant_firewall',
            'nplusone_active' => 'ucs_reseller_nplusone_active',
            'nplus_redundancy' => 'ucs_reseller_nplus_redundancy',
            'nplus_overprovision' => 'ucs_reseller_nplus_overprovision',
            'min_term' => 'ucs_reseller_min_term',
            'start_date' => 'ucs_reseller_start_date',
            'source' => 'ucs_reseller_source',
            'can_move_between_luns' => 'ucs_reseller_can_move_between_luns',
            'saleorder_id' => 'ucs_reseller_saleorder_id',
            'encryption_enabled' => 'ucs_reseller_encryption_enabled',
            'encryption_default' => 'ucs_reseller_encryption_default',
            'encryption_billing_type' => 'ucs_reseller_encryption_billing_type'
        ];
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
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('pod_id', Filter::$numericDefaults),
            $factory->create('environment', Filter::$stringDefaults),
            $factory->create('reseller_id', Filter::$numericDefaults),
            $factory->boolean()->create('active', 'Yes', 'No'),
            $factory->create('status', Filter::$stringDefaults),
            $factory->boolean()->create('windows_licence', 'Yes', 'No'),
            $factory->boolean()->create('redundant_firewall', 'Yes', 'No'),
            $factory->boolean()->create('nplusone_active', 'Yes', 'No'),
            $factory->enum()->create('nplus_redundancy', ['None', 'N+1', 'N+N']),
            $factory->boolean()->create('nplus_overprovision', 'Yes', 'No'),
            $factory->create('min_term', Filter::$numericDefaults),
            $factory->create('start_date', Filter::$dateDefaults),
            $factory->create('source', Filter::$stringDefaults),
            $factory->boolean()->create('can_move_between_luns', 'Yes', 'No'),
            $factory->create('saleorder_id', Filter::$numericDefaults),
            $factory->boolean()->create('encryption_enabled', 'Yes', 'No'),
            $factory->boolean()->create('encryption_default', 'Yes', 'No'),
            $factory->create('encryption_billing_type', Filter::$stringDefaults),
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
            $factory->create('name'),
            $factory->create('pod_id'),
            $factory->create('environment'),
            $factory->create('reseller_id'),
            $factory->create('active'),
            $factory->create('status'),
            $factory->create('windows_licence'),
            $factory->create('redundant_firewall'),
            $factory->create('nplusone_active'),
            $factory->create('nplus_redundancy'),
            $factory->create('nplus_overprovision'),
            $factory->create('min_term'),
            $factory->create('start_date'),
            $factory->create('source'),
            $factory->create('can_move_between_luns'),
            $factory->create('saleorder_id'),
            $factory->create('encryption_enabled'),
            $factory->create('encryption_default'),
            $factory->create('encryption_billing_type')
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
     * @throws \UKFast\Api\Resource\Exceptions\InvalidPropertyException
     */
    public function properties()
    {
        return [
            IdProperty::create('ucs_reseller_id', 'id'),
            StringProperty::create('ucs_reseller_solution_name', 'name'),
            IntProperty::create('ucs_reseller_datacentre_id', 'pod_id'),
            StringProperty::create('ucs_reseller_type', 'environment'),
            IntProperty::create('ucs_reseller_reseller_id', 'reseller_id'),
            BooleanProperty::create('ucs_reseller_active', 'active', null, 'Yes', 'No'),
            StringProperty::create('ucs_reseller_status', 'status'),
            BooleanProperty::create('ucs_reseller_windows_licence', 'windows_licence', null, 'Yes', 'No'),
            BooleanProperty::create('ucs_reseller_redundant_firewall', 'redundant_firewall', null, 'Yes', 'No'),
            BooleanProperty::create('ucs_reseller_nplusone_active', 'nplusone_active', null, 'Yes', 'No'),
            StringProperty::create('ucs_reseller_nplus_redundancy', 'nplus_redundancy'),
            StringProperty::create('ucs_reseller_nplus_overprovision', 'nplus_overprovision'),
            StringProperty::create('ucs_reseller_min_term', 'min_term'),
            DateTimeProperty::create('ucs_reseller_start_date', 'start_date'),
            StringProperty::create('ucs_reseller_source', 'source'),
            BooleanProperty::create('ucs_reseller_can_move_between_luns', 'can_move_between_luns', null, 'Yes', 'No'),
            IntProperty::create('ucs_reseller_saleorder_id', 'saleorder_id'),
            BooleanProperty::create('ucs_reseller_can_move_between_luns', 'can_move_between_luns', null, 'Yes', 'No'),
            BooleanProperty::create('ucs_reseller_encryption_enabled', 'encryption_enabled', null, 'Yes', 'No'),
            BooleanProperty::create('ucs_reseller_encryption_default', 'encryption_default', null, 'Yes', 'No'),
            StringProperty::create('ucs_reseller_encryption_billing_type', 'encryption_billing_type'),
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
     * Get the hostSet for the solution
     * At the moment we have a single host set for a solution
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hostSets()
    {
        return $this->hasMany(
            'App\Models\V1\HostSet',
            'ucs_reseller_id',
            'ucs_reseller_id'
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function volumeSets()
    {
        return $this->hasMany(
            VolumeSet::class,
            'ucs_reseller_id', //Local Column
            'ucs_reseller_id' //Relation's column
        );
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
                'App\Services\Kingpin\V1\KingpinService',
                [
                    $this->pod()->first(),
                    'Hybrid'
                ]
            );
        } catch (\Exception $exception) {
            $error = 'Unable to load hosts';
            Log::error($error . ': ' . $exception->getMessage());
            throw new KingpinException($error);
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
            if (!array_key_exists($vmwareHost->macAddress, $hostsDatabaseRecordsSorted)) {
                continue;
            }

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
        $result = $this->hasMany(
            'App\Models\V1\SolutionSite',
            'ucs_site_ucs_reseller_id',
            'ucs_reseller_id'
        )
            ->where('ucs_site_state', '=', 'Active')
            ->limit(2);
        return $result->count() > 1;
    }

    public function isMultiNetwork()
    {
        return SolutionNetwork::withSolution($this->getKey())
                ->limit(1)
                ->count() > 0;
    }

    public function hasMultipleNetworks()
    {
        return SolutionNetwork::withSolution($this->getKey())
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
        try {
            $kingpin = app()->makeWith(
                'App\Services\Kingpin\V1\KingpinService',
                [
                    $this->pod,
                    $this->ucs_reseller_type
                ]
            );

            //Load the solution datastores from VMWare
            $datastores = $kingpin->getDatastores($this->getKey());
        } catch (KingpinException $exception) {
            throw new \Exception('Failed to load solution datastores.');
        }

        if (!empty($datastores)) {
            foreach ($datastores as $datastore) {
                //Load the datastore record
                $datastoreQuery = Datastore::query()
                    ->withName($datastore->name)
                    ->withSolution($this->attributes['ucs_reseller_id']);

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

    /**
     * Return whether encryption is enabled on the solution
     * @return bool
     */
    public function encryptionEnabled()
    {
        return ($this->ucs_reseller_encryption_enabled == 'Yes');
    }

    /**
     * Determines the billing type for encryption on the solution
     * @return mixed
     */
    public function encryptionBillingType()
    {
        return $this->ucs_reseller_encryption_billing_type;
    }

    /**
     * Return the reseller id from the solution
     * @return mixed
     */
    public function resellerId()
    {
        return $this->ucs_reseller_reseller_id;
    }

    /**
     * Get the DRS Rules for the Solution
     *
     * @return DrsRule[]
     * @throws KingpinException
     */
    public function drsRules()
    {
        $rules = [];
        try {
            $kingpin = app()->makeWith(
                'App\Services\Kingpin\V1\KingpinService',
                [
                    $this->pod,
                    $this->ucs_reseller_type
                ]
            );
        } catch (\Exception $exception) {
            //Failed to connect to Kingpin
            throw new KingpinException('Unable to load constraints');
        }

        $result = $kingpin->getDrsRulesForSolution($this);

        foreach ($result as $rule) {
            $rules[] = new DrsRule($rule);
        }

        return $rules;
    }

    public function getResellerIdAttribute()
    {
        return $this->ucs_reseller_reseller_id;
    }

    public function getUcsResellerEncryptionEnabledAttribute($value)
    {
        return empty($value) ? 'No' : $value;
    }

    public function getUcsResellerEncryptionDefaultAttribute($value)
    {
        return empty($value) ? 'No' : $value;
    }
}
