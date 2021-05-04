<?php
namespace App\Traits\V2;

use App\Models\V2\Sync;

trait JobModel
{
    public function getModel()
    {
        $property = (!property_exists($this, 'model')) ? 'sync' : 'model';
        return (get_class($this->{$property}) === Sync::class) ? $this->{$property}->resource : $this->{$property};
    }
}
