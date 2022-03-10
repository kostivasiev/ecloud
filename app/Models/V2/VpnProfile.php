<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class VpnProfile extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules;

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
            'diffie_hellman',
        ];
        parent::__construct($attributes);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'ike_version' => $filter->string(),
            'encryption_algorithm' => $filter->string(),
            'digest_algorithm' => $filter->string(),
            'diffie_hellman' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
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
        return explode(',', $this->attributes['diffie_hellman']);
    }

    public function setDiffieHellmanAttribute($value)
    {
        $this->attributes['diffie_hellman'] = implode(',', $value);
    }
}
