<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
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
 * Class ImageParameter
 * @package App\Models\V2
 */
class ImageParameter extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DeletionRules, DefaultName;

    public string $keyPrefix = 'imgparam';

    const TYPE_PASSWORD = 'Password';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'image_id',
            'name',
            'key',
            'type',
            'description',
            'required',
            'is_hidden',
            'validation_rule',
        ]);

        $this->casts = [
            'required' => 'boolean',
            'is_hidden' => 'boolean'
        ];

        parent::__construct($attributes);
    }

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        return $query
            ->whereHas('image.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->orWhereHas('image', function ($query) use ($user) {
                $query->where('public', true)->where('active', true);
            })
            ->where('is_hidden', false);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('image_id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('key', Filter::$stringDefaults),
            $factory->create('type', Filter::$stringDefaults),
            $factory->create('description', Filter::$stringDefaults),
            $factory->boolean()->create('required', '1', '0'),
            $factory->boolean()->create('is_hidden', '1', '0'),
            $factory->create('validation_rule', Filter::$stringDefaults),
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
            $factory->create('image_id'),
            $factory->create('name'),
            $factory->create('key'),
            $factory->create('type'),
            $factory->create('description'),
            $factory->create('required'),
            $factory->create('is_hidden'),
            $factory->create('validation_rule'),
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
            'image_id' => 'image_id',
            'key' => 'key',
            'type' => 'type',
            'description' => 'description',
            'required' => 'required',
            'is_hidden' => 'is_hidden',
            'validation_rule' => 'validation_rule',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
