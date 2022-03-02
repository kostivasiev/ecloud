<?php

namespace App\Models\V1;

use App\Datastore\Exceptions\DatastoreNotFoundException;
use App\Datastore\Status;
use App\Events\V1\DatastoreCreatedEvent;
use App\Exceptions\V1\ArtisanException;
use App\Exceptions\V1\KingpinException;
use App\Http\Controllers\V1\VolumeSetController;
use App\Services\Artisan\V1\ArtisanService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Datastore extends Model implements Filterable, Sortable
{
    use HasFactory;

    /**
     * Eloquent configuration
     * ----------------------
     */

    protected $table = 'reseller_lun';
    protected $primaryKey = 'reseller_lun_id';
    public $timestamps = false;

    public $isSystemStorage = false;

    // Events triggered by actions on the model
    protected $dispatchesEvents = [
        'created' => DatastoreCreatedEvent::class,
    ];

    public static $maxDatastoreSizeGb = 16000;

    // Validation Rules
    public static function getRules()
    {
        return [
            'solution_id' => ['required_without:site_id', 'integer'],
            'name' => ['sometimes', 'max:255'],
            'type' => ['sometimes', 'in:Hybrid,Private'],
            'capacity' => ['required', 'numeric', 'min:1', 'max:' . static::$maxDatastoreSizeGb],
            'lun_type' => ['sometimes', 'in:DATA,CLUSTER,QRM'],
            'site_id' => ['sometimes', 'integer'],
            'san_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', Rule::in(Status::all())]
        ];
    }

    // Validation rules for expansion
    public static function getExpandRules()
    {
        return [
            'capacity' => 'required|integer|min:2|max:' . static::$maxDatastoreSizeGb
        ];
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
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
//            $factory->create('name', Filter::$stringDefaults),
            $factory->create('status', Filter::$stringDefaults),
            $factory->create('reseller_id', Filter::$numericDefaults),
            $factory->create('capacity', Filter::$numericDefaults),
            $factory->create('solution_id', Filter::$numericDefaults),
            $factory->create('site_id', Filter::$numericDefaults),
            $factory->create('lun_type', Filter::$stringDefaults),
            $factory->create('status', Filter::$stringDefaults),
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
//            $factory->create('name'),
            $factory->create('status'),
            $factory->create('capacity'),
            $factory->create('reseller_id'),
            $factory->create('solution_id'),
            $factory->create('site_id'),
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
            StringProperty::create('reseller_lun_lun_sub_type', 'lun_subtype'), //@deprecated
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
     * Return Solution
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function storage()
    {
        return $this->hasOne(
            'App\Models\V1\Storage',
            'id',
            'reseller_lun_ucs_storage_id'
        );
    }

    /**
     * todo: We will need to be careful with this now as it returns the *solution's* pod, not the pod for the datastore
     * (which could be different?) We should look at refactoring this out.
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
     * @throws \Exception
     */
    public function getVmwareUsage()
    {
        //allow system datastores to over provision
        if ($this->isSystemStorage) {
            return $this->vmwareUsage = (object)[
                'capacity' => $this->reseller_lun_size_gb,
                'freeSpace' => $this->reseller_lun_size_gb,
                'uncommitted' => 0,
                'provisioned' => 0,
                'available' => $this->reseller_lun_size_gb,
                'used' => 0,
            ];
        }

        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
                $this->pod,
                $this->reseller_lun_type
            ]);
            $vmwareDatastore = $kingpin->getDatastore(
                $this->reseller_lun_ucs_reseller_id,
                $this->reseller_lun_name
            );
        } catch (KingpinException $exception) {
            Log::error(
                'Failed to load datastore usage from VMWare',
                [
                    'error' => $exception->getMessage(),
                    'reseller_id' => $this->reseller_lun_ucs_reseller_id,
                    'lun_name' => $this->reseller_lun_name
                ]
            );
            throw new \Exception('Unable to load datastore usage from VMWare');
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
            try {
                $this->getVmwareUsage();
            } catch (\Exception $exception) {
                throw new KingpinException('Unable to load datastore usage');
            }
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
     * Get default datastore
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

                        if ($datastore->usage->available > $defaultDatastore->usage->available) {
                            $defaultDatastore = $datastore;
                        }
                    }
                }

                return $defaultDatastore;

            case 'Burst':
            case 'GPU': // TODO: To be confirmed
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

        $defaultDatastore->isSystemStorage = true;
        return $defaultDatastore;
    }

    /**
     * Get default datastore for Public VMs
     * @param $pod
     * @param $backupRequired
     * @return Datastore
     * @throws DatastoreNotFoundException
     */
    public static function getPublicDefault($pod, $backupRequired)
    {
        // clusters aren't named after their db IDs, need to investigate using
        // the pod short name if we can update them. for now I will have to map here
        $podMapping = [
            14 => 1,
            20 => 0,
            21 => 3,
            22 => 8,
            23 => 'X',
        ];

        $podId = $pod->getKey();
        if (array_key_exists($podId, $podMapping)) {
            $podId = $podMapping[$pod->getKey()];
        }

        $clusterName = 'MCS_P' . $podId . '_VV_VMPUBLICSTORE_SSD_' . ($backupRequired ? 'BACKUP' : 'NONBACKUP');

        // temp fudge until infra fix the cluster name
        if ($podId === 1 && $backupRequired == false) {
            $clusterName = 'MCS_VV_P1_VMPUBLICSTORE_SSD_NONBACKUP';
        }

        $datastore = static::where('reseller_lun_name', $clusterName)->first();
        if (empty($datastore)) {
            Log::error(
                'Failed to locate public datastore record',
                [
                    'cluster_name' => $clusterName
                ]
            );
            throw new DatastoreNotFoundException('unable to locate datastore');
        }

        // flag as system storage to bypass resource checks
        $datastore->isSystemStorage = true;

        return $datastore;
    }

    /**
     * Generates the next identifier for a volume for a solution. Volume identifiers are based on the lun_type
     * and the number owned by the solution, e.g. solution 17106 has 2 x DATA volumes, "MCS_G0_VV_17106_DATA_1" and
     * "MCS_G0_VV_17106_DATA_2", so the next identifier would be "DATA_3"
     * @return string
     */
    protected function getNextVolumeIdentifier()
    {
        // Get all the datastores for this datastore's solution, including deleted so we can generate a unique identifier
        $datastores = Datastore::query()
            ->join('ucs_reseller', 'ucs_reseller_id', '=', 'reseller_lun_ucs_reseller_id')
            ->where('ucs_reseller_id', '=', $this->reseller_lun_ucs_reseller_id)
            ->where('reseller_lun_name', '!=', '')
            ->where('reseller_lun_lun_type', '=', $this->reseller_lun_lun_type);

        if ($datastores->count() < 1) {
            return $this->reseller_lun_lun_type . '_' . 1;
        }

        $index = 0;
        $datastores->get()->map(function ($item) use (&$index) {
            if (preg_match('/\w+(DATA|CLUSTER|QRM)_?(\d+)*/', $item->reseller_lun_name, $matches) == true) {
                $numeric = $matches[2] ?? 1;
                $index = ($numeric > $index) ? (int)$numeric : $index;
            }
        });

        return $this->reseller_lun_lun_type . '_' . ++$index;
    }


    /**
     * Add the datastore to VMWare
     * @return bool
     * @throws \Exception
     */
    public function create()
    {
        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
                $this->storage->pod,
                $this->reseller_lun_type
            ]);
        } catch (\Exception $exception) {
            throw new KingpinException('Failed to create datastore usage on VMWare');
        }

        $result = $kingpin->createDatastore(
            $this->reseller_lun_ucs_reseller_id,
            $this->reseller_lun_name,
            $this->reseller_lun_wwn
        );

        if (!$result) {
            throw new KingpinException('Failed to create datastore on VMWare: ' . $kingpin->getLastError());
        }
    }


    /**
     * Create the volume for the datastore on the SAN
     * @return bool
     * @throws ArtisanException
     */
    public function createVolume()
    {
        $identifier = $this->getNextVolumeIdentifier();

        $artisanService = app()->makeWith(ArtisanService::class, [['datastore' => $this]]);

        // Convert GB to MiB
        $sizeMiB = $this->reseller_lun_size_gb * 1024;

        $artisanResult = $artisanService->createVolume($identifier, $this->reseller_lun_lun_type, $sizeMiB);

        if (!$artisanResult) {
            throw new ArtisanException('Failed to create volume: ' . $artisanService->getLastError());
        }

        // Update the volume properties
        $this->reseller_lun_name = $artisanResult->name;
        $this->reseller_lun_wwn = $artisanResult->wwn;
        $this->reseller_lun_size_gb = $artisanResult->sizeMiB / 1024;

        $this->save();

        return true;
    }

    /**
     * Expand the volume on the SAN
     * @param $newSizeGB
     * @return bool
     * @throws ArtisanException
     */
    public function expandVolume($newSizeGB)
    {
        $artisanService = app()->makeWith(ArtisanService::class, [['datastore' => $this]]);

        // Convert GB to Mib
        $newSizeMiB = $newSizeGB * 1024;

        if (!$artisanService->expandVolume($this->reseller_lun_name, $newSizeMiB)) {
            throw new ArtisanException('Failed to expand datastore volume: ' . $artisanService->getLastError());
        }

        $this->reseller_lun_size_gb = $newSizeGB;
        $this->save();

        return true;
    }

    /**
     * Expand datastore
     * @return bool
     * @throws \Exception
     */
    public function expand()
    {
        $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
            $this->storage->pod,
            $this->reseller_lun_type
        ]);

        if (!$kingpin->expandDatastore($this->reseller_lun_ucs_reseller_id, $this->reseller_lun_name)) {
            throw new \Exception('Failed to expand datastore: ' . $kingpin->getLastError());
        }

        return true;
    }

    /**
     * Rescan the datastore's cluster on vmware
     * @throws \Exception
     */
    public function clusterRescan()
    {
        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [
                $this->storage->pod,
                $this->reseller_lun_type
            ]);

            if (!$kingpin->clusterRescan($this->reseller_lun_ucs_reseller_id)) {
                throw new \Exception('Failed to perform cluster rescan: ' . $kingpin->getLastError());
            }
        } catch (\Exception $exception) {
            throw new \Exception('Failed to perform cluster rescan ' . $exception->getMessage());
        }

        return true;
    }

    /**
     * Load the volume set for a datastore
     * This is quite an expensive method to run, so lets only do it when we have to!
     * @return VolumeSet|null
     */
    public function volumeSet()
    {
        $query = VolumeSetController::getQuery(app('request'));

        $query->where('ucs_reseller_id', '=', $this->solution->getKey());

        if ($query->count() > 0) {
            $artisan = app()->makeWith(ArtisanService::class, [['datastore' => $this]]);

            foreach ($query->get() as $volumeSet) {
                $artisanResponse = $artisan->getVolumeSet($volumeSet->name);

                if (!$artisanResponse) {
                    $error = $artisan->getLastError();
                    if (strpos($error, 'Set does not exist') !== false) {
                        continue;
                    }
                    Log::error(
                        'Failed to get volume set details from the SAN',
                        [
                            'datastore_id' => $this->getKey(),
                            'volume_set' => $volumeSet->name,
                            'SAN error message' => $error
                        ]
                    );
                }

                $arrayResult = in_array(
                    $this->reseller_lun_name,
                    $artisanResponse->volumes
                );
                if (!empty($artisanResponse->volumes) && $arrayResult) {
                    return $volumeSet;
                }
            }
        }

        return null;
    }
}
