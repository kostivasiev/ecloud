<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class VpnProfile extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpnp';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'ike_version',
            'encryption_algorithm',
            'digest_algorithm',
            'diffie_-_hellman',
        ];
        parent::__construct($attributes);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('ike_version', Filter::$stringDefaults),
            $factory->create('encryption_algorithm', Filter::$enumDefaults),
            $factory->create('digest_algorithm', Filter::$enumDefaults),
            $factory->create('diffie_-_hellman', Filter::$enumDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('ike_version'),
            $factory->create('encryption_algorithm'),
            $factory->create('digest_algorithm'),
            $factory->create('diffie_-_hellman'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('id', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'ike_version' => 'ike_version',
            'encryption_algorithm' => 'encryption_algorithm',
            'digest_algorithm' => 'digest_algorithm',
            'diffie_-_hellman' => 'diffie_-_hellman',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    public function getDigestAlgorithmAttribute()
    {
        return explode(',', $this->attributes['digest_algorithm']);
    }

    public function setDigestAlgorithmAttribute($value)
    {
        $this->attributes['digest_algorithm'] = implode(',', $value);
    }

    public function getEncryptionAlgorithmAttribute()
    {
        return explode(',', $this->attributes['encryption_algorithm']);
    }

    public function setEncryptionAlgorithmAttribute($value)
    {
        $this->attributes['encryption_algorithm'] = implode(',', $value);
    }

    public function getDiffieHellmanAttribute()
    {
        return explode(',', $this->attributes['diffie_-_hellman']);
    }

    public function setDiffieHellmanAttribute($value)
    {
        $this->attributes['diffie_-_hellman'] = implode(',', $value);
    }
}
