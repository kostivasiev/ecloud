<?php

namespace App\Models\V2;

use App\Events\V2\Nat\Created;
use App\Events\V2\Nat\Deleted;
use App\Events\V2\Nat\Deleting;
use App\Events\V2\Nat\Saved;
use App\Events\V2\Nat\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nat extends Model
{
    use CustomKey, SoftDeletes, Syncable, Taskable;

    public $keyPrefix = 'nat';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'destination_id',
        'translated_id',
        'action',
        'sequence'
    ];

    const ACTION_DNAT = 'DNAT';
    const ACTION_SNAT = 'SNAT';

    protected $dispatchesEvents = [
        'created' => Created::class,
        'saving' => Saving::class,
        'saved' => Saved::class,
        'deleting' => Deleting::class,
        'deleted' => Deleted::class,
    ];

    /**
     * Load the associated destination resource
     * @return Model|\Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function destination()
    {
        return $this->morphTo('destination', 'destinationable_type', 'destination_id', 'id');
    }

    public function source()
    {
        return $this->morphTo('source', 'sourceable_type', 'source_id', 'id');
    }

    public function translated()
    {
        return $this->morphTo('translated', 'translatedable_type', 'translated_id', 'id');
    }
}
