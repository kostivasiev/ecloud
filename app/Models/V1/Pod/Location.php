<?php

namespace App\Models\V1\Pod;

use App\Models\V1\Pod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'ucs_datacentre_location';
    protected $primaryKey = 'ucs_datacentre_location_id';
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pod()
    {
        return $this->belongsTo(Pod::class, 'ucs_datacentre_location_datacentre_id');
    }
}
