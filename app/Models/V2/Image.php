<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Image
 * @package App\Models\V2
 * This model is a proxy to the underlying V1 Appliances. In future, the underlying appliances will be dropped in favour of images
 */
class Image extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DeletionRules;

    public string $keyPrefix = 'image';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
        ]);

        parent::__construct($attributes);
    }

    public function getLogoURIAttribute()
    {
        return $this->applianceVersion->appliance->logo_uri;
    }

    public function getDocumentationURIAttribute()
    {
        return $this->applianceVersion->appliance->documentation_uri;
    }

    public function getDescriptionAttribute()
    {
        return $this->applianceVersion->appliance->description;
    }

    public function getActiveAttribute()
    {
        return $this->applianceVersion->appliance->active == "Yes";
    }

    public function getIsPublicAttribute()
    {
        return $this->applianceVersion->appliance->is_public == "Yes";
    }

    public function parameters()
    {
        return $this->applianceVersion->applianceScriptParameters();
    }

    public function applianceVersion()
    {
        return $this->belongsTo(
            ApplianceVersion::class,
            'appliance_version_id',
            'appliance_version_uuid'
        );
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory): array
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory): array
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    public function databaseNames(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
