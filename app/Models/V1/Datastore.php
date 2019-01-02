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

class Datastore extends Model implements Filterable, Sortable
{
    /**
     * Eloquent configuration
     * ----------------------
     */

    protected $table = 'reseller_lun';
    protected $primaryKey = 'reseller_lun_id';
    public $timestamps = false;


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
        $names = [];

        foreach ($this->properties() as $property) {
            $names[$property->getFriendlyName()] = $property->getDatabaseName();
        }

        return $names;
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
            $factory->create('solution_id', Filter::$numericDefaults),
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
     * Resource configuration
     * ----------------------
     */

    /**
     * Map request property to database field
     *
     * @return array
     */
    public function properties()
    {
        return [
            IdProperty::create('reseller_lun_id', 'id'),
            IntProperty::create('reseller_lun_reseller_id', 'reseller_id'),

            IntProperty::create('reseller_lun_ucs_reseller_id', 'solution_id'),
            IntProperty::create('reseller_lun_ucs_site_id', 'site_id'),

            StringProperty::create('reseller_lun_friendly_name', 'name'),
            StringProperty::create('reseller_lun_status', 'status'),
            StringProperty::create('reseller_lun_type', 'type'),

            IntProperty::create('reseller_lun_size_gb', 'capacity'),
            IntProperty::create(null, 'allocated'),
            IntProperty::create(null, 'available'),

            StringProperty::create('reseller_lun_name', 'lun_name'),
            StringProperty::create('reseller_lun_wwn', 'lun_wwn'),
            StringProperty::create('reseller_lun_lun_type', 'lun_type'),
            StringProperty::create('reseller_lun_lun_sub_type', 'lun_subtype'),
        ];
    }

    /**
     * End Package Config
     * ----------------------
     */


    public static $collectionProperties = [
        'reseller_lun_id',
        'reseller_lun_friendly_name',
        'reseller_lun_status',
        'reseller_lun_ucs_reseller_id',
        'reseller_lun_ucs_site_id',
        'reseller_lun_size_gb',
    ];

    public static $itemProperties = [
        'reseller_lun_id',
        'reseller_lun_friendly_name',
        'reseller_lun_status',
        'reseller_lun_ucs_reseller_id',
        'reseller_lun_ucs_site_id',
        'reseller_lun_size_gb',
        'allocated',
        'available',
    ];

    public static $adminProperties = [
        'reseller_lun_reseller_id',
        'reseller_lun_type',
        'reseller_lun_name',
        'reseller_lun_wwn',
        'reseller_lun_lun_type',
        'reseller_lun_lun_sub_type',
    ];

    /**
     * Return Solution
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function solution()
    {
        return $this->hasOne(
            'App\Models\V1\Solution',
            'ucs_reseller_id',
            'reseller_lun_ucs_reseller_id'
        );
    }

    /**
     * Return Pod
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getPodAttribute()
    {
        return $this->solution->pod;
    }

    /**
     * Mutate the reseller_lun_friendly_name attribute
     * @param $value
     * @return string
     */
    public function getResellerLunFriendlyNameAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        $name_parts = explode('_', $this->reseller_lun_name);
        $name_number = array_pop($name_parts);
        $name_number = is_numeric($name_number) ? $name_number : 1;

        return
            'Datastore ' . ucwords(strtolower($this->reseller_lun_lun_type)) .
            '-' .
            str_pad($name_number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * get VMware usage stats
     */
    public function getVmwareUsage()
    {
        try {
            $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', [
                $this->pod
            ]);

            $vmwareDatastore = $kingpin->getDatastore(
                $this->solution->ucs_reseller_id,
                $this->reseller_lun_name
            );
        } catch (\Exception $exception) {
            throw $exception;
        }

        return $this->vmwareUsage = (object)[
            'capacity' => $vmwareDatastore->capacity,
            'freeSpace' => $vmwareDatastore->freeSpace,
            'uncommitted' => $vmwareDatastore->uncommitted,
            'provisioned' => $vmwareDatastore->provisioned,
            'available' => $vmwareDatastore->available,
            'used' => $vmwareDatastore->used,
        ];
    }

    /**
     * Return Usage
     * @throws \Exception
     */
    public function getUsageAttribute()
    {
        if (!is_object($this->vmwareUsage)) {
            $this->getVmwareUsage();
        }

        return $this->vmwareUsage;
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
            $query->where('reseller_lun_reseller_id', $resellerId);
        }

        return $query;
    }

    public function scopeWithSolution($query, $solutionId)
    {
        $solutionId = filter_var($solutionId, FILTER_SANITIZE_NUMBER_INT);

        $query->where('reseller_lun_ucs_reseller_id', $solutionId);
        return $query;
    }

    /**
     * Scope datastore query by (LUN) name
     * @param $query
     * @param $name
     * @return mixed
     */
    public function scopeWithName($query, $name)
    {
        $name = filter_var($name, FILTER_SANITIZE_STRING);

        if (!empty($name)) {
            $query->where('reseller_lun_name', $name);
        }

        return $query;
    }

    /**
     * Load datastores for a solution
     * @param $solutionId
     * @param null $UCSSiteId
     * @return bool
     */
    public static function getForSolution($solutionId, $UCSSiteId = null)
    {
        try {
            return Solution::find($solutionId)->datastores($UCSSiteId);
        } catch (\Exception $exception) {
            return false;
        }
    }


    /**
     * Gt default datastore
     * @param $solutionId
     * @param string $ecloudType
     * @param bool $backupRequired
     * @param null $solutionSiteId
     * @return mixed|bool
     * @throws \Exception
     */
    public static function getDefault($solutionId, $ecloudType, $backupRequired = false, $solutionSiteId = null)
    {
        switch ($ecloudType) {
            case 'Hybrid':
            case 'Private':
                $datastores = static::getForSolution($solutionId, $solutionSiteId);
                if (empty($datastores)) {
                    throw new \Exception('failed to load solution datastores');
                }

                $defaultDatastore = $datastores[0];
                if (count($datastores) > 1) {
                    //default on dedicated is the one with the most space
                    foreach ($datastores as &$datastore) {
                        try {
                            //get the usage from vmware
                            $datastore->getVmwareUsage();
                        } catch (\Exception $exception) {
                            continue;
                        }

                        if ($datastore->available > $defaultDatastore->available) {
                            $defaultDatastore = $datastore;
                        }
                    }
                }

                return $defaultDatastore;

            case 'Burst':
                $defaultDatastore = static::find(5);
                break;

            case 'Public':
                if (!$backupRequired) {
                    $defaultDatastore = static::find(3);
                } else {
                    $defaultDatastore = static::find(4);
                }
                break;

            default:
                throw new \Exception('failed to load default datastore');
        }

        //allow default datastores to over provision
        $defaultDatastore->usage = (object)[
            'capacity' => $defaultDatastore->reseller_lun_size_gb,
            'freeSpace' => $defaultDatastore->reseller_lun_size_gb,
            'uncommitted' => 0,
            'provisioned' => 0,
            'available' => $defaultDatastore->reseller_lun_size_gb,
            'used' => 0,
        ];

        return $defaultDatastore;
    }
}
