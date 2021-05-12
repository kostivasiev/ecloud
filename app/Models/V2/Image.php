<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    public string $keyPrefix = 'img';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'appliance_version_id',
        ]);

        parent::__construct($attributes);
    }

    public function getNameAttribute()
    {
        return $this->applianceVersion->appliance->name;
    }

    public function getScriptTemplateAttribute()
    {
        return $this->applianceVersion->script_template;
    }

    public function getVMTemplateNameAttribute()
    {
        return $this->applianceVersion->appliance_version_vm_template;
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

    public function getPlatformAttribute()
    {
        return $this->applianceVersion->serverLicense()->category;
    }

    public function getLicenseIDAttribute()
    {
        return $this->applianceVersion->serverLicense()->id;
    }

    public function parameters()
    {
        return $this->applianceVersion->applianceScriptParameters();
    }

    public function metadata()
    {
        return $this->applianceVersion->applianceVersionData();
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
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isAdmin()) {
            return $query->whereHas('applianceVersion.appliance', function ($query) use ($user) {
                $query->where('appliance_is_public', 'Yes')
                      ->where('appliance_active', 'Yes');
            });
        }

        return $query;
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
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
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
