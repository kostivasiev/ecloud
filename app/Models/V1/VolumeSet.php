<?php

namespace App\Models\V1;

use App\Services\Artisan\V1\ArtisanService;
use App\Traits\V1\UUIDHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use App\Services\V1\Resource\Property\DateTimeProperty;
use App\Services\V1\Resource\Property\IdProperty;
use App\Services\V1\Resource\Property\IntProperty;
use App\Services\V1\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class VolumeSet extends Model implements Filterable, Sortable
{
    // Table uses UUID's
    use UUIDHelper, SoftDeletes, HasFactory;

    protected $table = 'ucs_storage_volume_set';

    // Use UUID as primary key
    protected $primaryKey = 'uuid';

    protected $keyType = 'string';
    // Don't increment the primary key for UUID's
    public $incrementing = false;

    //Automatically manage our timestamps
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    /**
     * The attributes included in the model's JSON form.
     *
     * @var array
     */
    protected $visible = [
        'uuid',
        'name',
        'ucs_reseller_id',
        'max_iops',
        'created_at',
        'updated_at'
    ];

    protected $attributes = [
        'max_iops' => 0
    ];

    // Validation Rules
    public static $rules = [
        'solution_id' => ['required', 'numeric']
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
            'id' => 'uuid',
            'name' => 'name',
            'solution_id' => 'ucs_reseller_id',
            'max_iops' => 'max_iops',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
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
            $factory->create('solution_id', Filter::$numericDefaults),
            $factory->create('max_iops', Filter::$numericDefaults),
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
            $factory->create('solution_id'),
            $factory->create('max_iops'),
            $factory->create('created_at'),
            $factory->create('updated_at')
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return mixed
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
     * End Ditto configuration
     * ----------------------
     */


    /**
     * Resource package
     * Map request property to database field
     *
     * @return array
     * @throws \App\Services\V1\Resource\Exceptions\InvalidPropertyException
     */
    public function properties()
    {
        return [
            IdProperty::create('uuid', 'id', null, 'uuid'),
            StringProperty::create('name', 'name'),
            IntProperty::create('ucs_reseller_id', 'solution_id'),
            IntProperty::create('max_iops', 'max_iops'),
            DateTimeProperty::create('created_at', 'created_at'),
            DateTimeProperty::create('updated_at', 'updated_at')
        ];
    }

    /**
     * Maps the Host to the related Solution
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function solution()
    {
        return $this->belongsTo(
            'App\Models\V1\Solution',
            'ucs_reseller_id', //Local Column
            'ucs_reseller_id' //Relation's column
        );
    }

    /**
     * Scope to a solution
     * @param $query
     * @param $solutionId
     * @return mixed
     */
    public function scopeWithSolution($query, $solutionId)
    {
        $solutionId = filter_var($solutionId, FILTER_SANITIZE_NUMBER_INT);

        $query->where('ucs_reseller_id', $solutionId);

        return $query;
    }

    /**
     * Generate a sequential ID for the volume sets based on the solution.
     * Include deleted items too so that we *always* use a unique identifier
     * @param Solution $solution
     * @return int|mixed
     */
    public static function getNextIdentifier(Solution $solution): int
    {
        if ($solution->volumeSets()->withTrashed()->count() == 0) {
            return 1;
        }

        $index = 0;
        $solution->volumeSets()->withTrashed()->get()->map(function ($item) use (&$index, $solution) {
            if (preg_match('/\w+(' . $solution->getKey() . ')_?(\d+)*$/', $item->name, $matches) == true) {
                $numeric = $matches[2] ?? 1;
                $index = ($numeric > $index) ? (int)$numeric : $index;
            }
        });

        return ++$index;
    }

    /**
     * Get a list of volumes from Artisan
     * @return array
     */
    public function volumes()
    {
        if (!$this->solution) {
            return [];
        }

        $sanVolumes = [];
        foreach ($this->solution->pod->sans as $san) {
            $artisan = app()->makeWith(ArtisanService::class, [['solution' => $this->solution, 'san' => $san]]);
            $artisanResponse = $artisan->getVolumeSet($this->name);
            if (!$artisanResponse) {
                Log::error($artisan->getLastError());
                continue;
            }
            $sanVolumes[$san->servers_id] = $artisanResponse->volumes ?? null;
        }
        return $sanVolumes;
    }
}
