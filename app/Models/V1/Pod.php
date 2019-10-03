<?php

namespace App\Models\V1;

use App\Services\Kingpin\V1\KingpinService;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Log;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\BooleanProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Pod extends Model implements Filterable, Sortable
{
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
        if (!$request->user->isAdministrator) {
            return $properties;
        }

        // admin only properties
        return array_merge($properties, [
            IntProperty::create('ucs_datacentre_datacentre_id', 'datacentre_id'),
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
        /**
         * select * from `gpu_profile`
         * inner join `gpu_profile_pod_availability` on `gpu_profile_pod_availability`.`gpu_profile_id` =
         * `gpu_profile`.`id`  where `gpu_profile_pod_availability`.`ucs_datacentre_id` = ?
         * and `gpu_profile`.`deleted_at` is null
         */
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
     * Load VCE server details
     * @return array|bool
     */
    public function vceServerDetails()
    {
        if (empty($this->ucs_datacentre_vce_server_id)) {
            Log::error('Invalid or missing VCE server ID for Pod ' . $this->getKey());
            return false;
        }

        $serverDetail = ServerDetail::withParent($this->ucs_datacentre_vce_server_id)
            ->where('server_detail_type', '=', 'API')
            ->where('server_detail_user', '=', KingpinService::KINGPIN_USER)
            ->first();

        if (!$serverDetail) {
            Log::error('Failed to load Kingpin server details record for VCE server #' . $this->ucs_datacentre_vce_server_id);
            return false;
        }

        return $serverDetail;
    }
}
