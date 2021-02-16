<?php

namespace App\Models\V1;

use App\Traits\V1\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Exceptions\NotFoundException;
use UKFast\Api\Resource\Property\DateTimeProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class GpuProfile extends Model implements Filterable, Sortable
{
    // Table uses UUID's
    use UUIDHelper;

    use SoftDeletes;

    protected $connection = 'ecloud';

    protected $table = 'gpu_profile';

    protected $keyType = 'string';
    // Use UUID as primary key
    protected $primaryKey = 'uuid';
    // Don't increment the primary key for UUID's
    public $incrementing = false;

    // Automatically manage our timestamps
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * The attributes included in the model's JSON form.
     * Admin scope / everything
     *
     * @var array
     */
    protected $visible = [
        'uuid',
        'name',
        'profile_name',
        'card_type',
        'created_at',
        'updated_at',
    ];

    /**
     * Restrict visibility for non-admin
     */
    const VISIBLE_SCOPE_RESELLER = [
        'uuid',
        'name',
        'card_type'
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
            'id' => 'uuid', //UUID, not internal id
            'name' => 'name',
            'profile_name' => 'profile_name',
            'card_type' => 'card_type',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
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
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('profile_name', Filter::$stringDefaults),
            $factory->create('card_type', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults)
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
            $factory->create('name'),
            $factory->create('profile_name'),
            $factory->create('card_type'),
            $factory->create('created_at'),
            $factory->create('updated_at')
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
            $sortFactory->create('name', 'asc'),
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
            IdProperty::create('uuid', 'id', null, 'uuid'),
            StringProperty::create('name', 'name'),
            StringProperty::create('profile_name', 'profile_name'),
            StringProperty::create('card_type', 'card_type'),
            DateTimeProperty::create('appliance_created_at', 'created_at'),
            DateTimeProperty::create('appliance_updated_at', 'updated_at')
        ];
    }

    /**
     * Get GPU resource pool availability
     * @return int|mixed
     */
    public static function gpuResourcePoolAvailability()
    {
        $available = config('gpu.cards_available');

        $profiles = static::select('uuid', 'profile_name')->get()->pluck('uuid', 'profile_name');
        $profiles = $profiles->flip()->toArray();

        // Get a list of active VM's with a GPU profile assigned
        $vms = VirtualMachine::query()
            ->where('servers_ecloud_gpu_profile_uuid', '!=', '0')
            ->where('servers_active', '=', 'y')
            ->whereNotNull('servers_ecloud_gpu_profile_uuid')
            ->where('servers_ecloud_gpu_profile_uuid', '!=', '')
            ->pluck('servers_ecloud_gpu_profile_uuid', 'servers_id')
            ->toArray();

        $cardProfiles = config('gpu.card_profiles');

        foreach ($vms as $vmId => $profileUuid) {
            $arrayResult = in_array(
                $profiles[$profileUuid],
                array_keys($cardProfiles)
            );
            if (!in_array($profileUuid, array_keys($profiles)) || !$arrayResult) {
                Log::error(
                    'Unrecognised GPU profile \'' . $profileUuid . '\' found on Virtual Machine # ' . $vmId . ' when calculating GPU pool availability'
                );
                continue;
            }

            $available -= $cardProfiles[$profiles[$profileUuid]];
        }

        return $available;
    }

    /**
     * Return how much resource this profile allocates, 0.5 or 1 GPU card.
     * @return mixed
     * @throws NotFoundException
     */
    public function getResourceAllocation()
    {
        $cardProfiles = config('gpu.card_profiles');
        if (!in_array($this->profile_name, array_keys($cardProfiles))) {
            throw new NotFoundException('GPU profile ' . $this->profile_name . ' is not a valid card type');
        }

        return $cardProfiles[$this->profile_name];
    }
}
