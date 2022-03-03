<?php

namespace App\Models\V1;

use App\Models\V1\Pod\ResourceAbstract;
use App\Models\V1\Pod\ServiceAbstract;
use App\Services\Artisan\V1\ArtisanService;
use App\Services\Kingpin\V1\KingpinService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Resource\Property\BooleanProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Pod extends Model implements Filterable, Sortable
{
    use HasFactory;

    /**
     * Eloquent configuration
     * ----------------------
     */
    protected $table = 'ucs_datacentre';
    protected $primaryKey = 'ucs_datacentre_id';

    public $timestamps = false;

    protected $casts = [
        'ucs_datacentre_id' => 'integer',
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
            'id' => 'ucs_datacentre_id',
            'name' => 'ucs_datacentre_public_name',
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
            $factory->create('datacentre_id', Filter::$numericDefaults),

            $factory->create('reseller_id', Filter::$numericDefaults),
            $factory->boolean()->create('active', 'Yes', 'No'),

            $factory->boolean()->create('services_public', 1, 0, 'ucs_datacentre_public_enabled'),
            $factory->boolean()->create('services_burst', 'Yes', 'No', 'ucs_datacentre_burst_enabled'),
            $factory->boolean()->create('services_gpu', 'Yes', 'No', 'ucs_datacentre_gpu_enabled'),
            $factory->boolean()->create('services_appliances', 'Yes', 'No', 'ucs_datacentre_oneclick_enabled'),
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
            $factory->create('reseller_id'),
            $factory->create('active'),
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
        $properties = [
            IdProperty::create('ucs_datacentre_id', 'id'),
            StringProperty::create('ucs_datacentre_public_name', 'name'),

            'services' => [
                BooleanProperty::create('ucs_datacentre_public_enabled', 'public', null, 'Yes', 'No'),
                BooleanProperty::create('ucs_datacentre_burst_enabled', 'burst', null, 'Yes', 'No'),
                BooleanProperty::create('ucs_datacentre_oneclick_enabled', 'appliances', null, 'Yes', 'No'),
                BooleanProperty::create('ucs_datacentre_gpu_enabled', 'gpu', null, 'Yes', 'No'),
            ],
        ];

        $request = app('request');
        if (!$request->user()->isAdmin()) {
            return $properties;
        }

        // admin only properties
        return array_merge($properties, [
            IntProperty::create('ucs_datacentre_reseller_id', 'reseller_id'),
            BooleanProperty::create('ucs_datacentre_active', 'active', null, 'Yes', 'No'),
            IntProperty::create('ucs_datacentre_datacentre_id', 'datacentre_id'),

            IntProperty::create('ucs_datacentre_vce_server_id', 'vce_server_id'),
            IntProperty::create('ucs_datacentre_vcl_server_id', 'vcl_server_id'),

            StringProperty::create('ucs_datacentre_vmware_api_url', 'mgmt_api_url'),
        ]);
    }

    /**
     *
     * Return GPU profiles available to the Pod
     *
     * Has-many relationship through gpu_profile_pod_availability mapping table using ucs_datacentre_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function gpuProfiles()
    {
        return $this->hasManyThrough(
            'App\Models\V1\GpuProfile',
            'App\Models\V1\GpuProfilePodAvailability', // Map table
            'ucs_datacentre_id', // Foreign key on gpu_profile_pod_availability table.
            'id', // Foreign key on gpu_profile table.
            'ucs_datacentre_id', // Local key on gpu_profile_pod_availability table.
            'gpu_profile_id'  // Local key on gpu_profile_pod_availability table.
        );
    }

    /**
     *
     * Return SAN's on the Pod
     *
     * Has-many relationship through ucs_storage mapping table
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function sans()
    {
        return $this->hasManyThrough(
            San::class,
            Storage::class,
            'ucs_datacentre_id', // Foreign key on ucs_storage table
            'servers_id', // servers.servers_id
            'ucs_datacentre_id',
            'server_id' // ucs_storage.server_id
        );
    }

    /**
     * Get all the resource types available
     * @return array
     */
    public function getResourceTypesAttribute()
    {
        return [
            'compute' => \App\Models\V1\Pod\Resource\Compute::class,
            'console' => \App\Models\V1\Pod\Resource\Console::class,
            'management' => \App\Models\V1\Pod\Resource\Management::class,
            'network' => \App\Models\V1\Pod\Resource\Network::class,
            'storage' => \App\Models\V1\Pod\Resource\Storage::class,
        ];
    }

    /**
     * Add a resource to this pod
     * @param ResourceAbstract $resource
     * @return bool
     */
    public function addResource(ResourceAbstract $resource)
    {
        $rows = app('db')->connection('ecloud')
            ->table('pod_resource')
            ->where([
                ['pod_id', '=', $this->ucs_datacentre_id],
                ['resource_id', '=', $resource->id],
                ['resource_type', '=', array_search(get_class($resource), $this->resource_types)],
            ])
            ->get();
        if (count($rows) !== 0) {
            return true; // Don't create duplicates, respond with success
        }

        return app('db')->connection('ecloud')->table('pod_resource')->insert([
            'pod_id' => $this->ucs_datacentre_id,
            'resource_id' => $resource->id,
            'resource_type' => array_search(get_class($resource), $this->resource_types),
        ]);
    }

    /**
     * Remove a resource from this pod
     * @param ResourceAbstract $resource
     * @return bool
     */
    public function removeResource(ResourceAbstract $resource)
    {
        return app('db')->connection('ecloud')
            ->table('pod_resource')
            ->where([
                ['pod_id', '=', $this->ucs_datacentre_id],
                ['resource_id', '=', $resource->id],
                ['resource_type', '=', array_search(get_class($resource), $this->resource_types)],
            ])
            ->delete();
    }

    /**
     * Get all the resources used by this pod
     * @return ResourceAbstract[]
     */
    public function resources()
    {
        $rows = app('db')->connection('ecloud')
            ->table('pod_resource')
            ->select('resource_id', 'resource_type')
            ->where('pod_id', '=', $this->ucs_datacentre_id)
            ->get();
        $resources = [];
        foreach ($rows as $row) {
            if (!isset($this->resource_types[$row->resource_type])) {
                continue;
            }
            $resources[] = $this->resource_types[$row->resource_type]::find($row->resource_id);
        }
        return $resources;
    }

    /**
     * Get the first instance of the type
     * @param $type
     * @return mixed
     */
    public function resource($type)
    {
        $resources = array_filter($this->resources(), function ($v, $k) use ($type) {
            return array_search(get_class($v), $this->resource_types) === $type;
        }, ARRAY_FILTER_USE_BOTH);
        return array_shift($resources);
    }

    /**
     * Load VCE server details/credentials by username
     * @param string $username
     * @return array|bool
     * @deprecated Use the devices API
     */
    public function vceServerDetails($username)
    {
        if (empty($this->ucs_datacentre_vce_server_id)) {
            Log::error('Invalid or missing VCE server ID for Pod ' . $this->getKey());
            return false;
        }

        $serverDetail = ServerDetail::withParent($this->ucs_datacentre_vce_server_id)
            ->where('server_detail_type', '=', 'API')
            ->where('server_detail_user', '=', $username)
            ->first();

        if (!$serverDetail) {
            Log::error('Failed to load Kingpin server details record for VCE server #' . $this->ucs_datacentre_vce_server_id);
            return false;
        }

        return $serverDetail;
    }

    /**
     * Return the storage api URL
     * @return mixed
     */
    public function storageApiUrl()
    {
        return $this->ucs_datacentre_storage_api_url;
    }

    /**
     * Return the storage API password
     * @return mixed
     */
    public function storageApiPassword()
    {
        $serverDetail = $this->vceServerDetails(ArtisanService::ARTISAN_API_USER);
        if ($serverDetail) {
            return $serverDetail->getPassword();
        }
        return false;
    }

    public function storageApiPort()
    {
        $serverDetail = $this->vceServerDetails(ArtisanService::ARTISAN_API_USER);
        if ($serverDetail) {
            return $serverDetail->server_detail_login_port;
        }
        return false;
    }

    /**
     * Return the VMWare API URL
     * @return mixed
     * TODO: Implement this in the kingpin service provider
     */
    public function vmwareApiUrl()
    {
        return $this->ucs_datacentre_vmware_api_url;
    }

    /**
     * Return the kingpin passowrd
     * @return bool
     * TODO: implement this in the kingpin service provider
     */
    public function vmwareApiPassword()
    {
        $serverDetail = $this->vceServerDetails(KingpinService::KINGPIN_USER);
        if (!$serverDetail) {
            return $serverDetail->getPassword();
        }
        return false;
    }

    /**
     * has the pod got the requested service enabled
     * @param $serviceName
     * @return bool
     */
    public function hasEnabledService($serviceName)
    {
        return $this->{'ucs_datacentre_' . strtolower($serviceName) . '_enabled'} == 'Yes';
    }
}
