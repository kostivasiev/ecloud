<?php
namespace App\Traits\V2;

use App\Models\V2\Task;

trait JobModel
{
    public function getModel()
    {
        $property = (!property_exists($this, 'model')) ? 'task' : 'model';
        return (get_class($this->{$property}) === Task::class) ? $this->{$property}->resource : $this->{$property};
    }
}
