<?php

namespace App\Models\V2;

use App\Events\V2\Nat\Created;
use App\Events\V2\Nat\Deleted;
use App\Events\V2\Nat\Saved;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nat extends Model
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'nat';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'destination_id',
        'translated_id',
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
        'saved' => Saved::class,
        'deleted' => Deleted::class,
    ];

    /**
     * Load the associated destination resource
     * @return Model|\Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function destination()
    {
        return $this->morphTo('destinationable', null, 'destination_id', 'id');
    }

    public function translated()
    {
        return $this->morphTo('translatedable', null, 'translated_id', 'id');
    }
}
