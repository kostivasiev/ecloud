<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class V1ModelWrapper extends Model
{
    public function getIdAttribute()
    {
        $message = PHP_EOL;
        foreach (debug_backtrace() as $row) {
            $message .= $row['file'] . ':' . $row['line'] . PHP_EOL;
        }
        Log::debug(get_class($this) . '->id was read from on a V1 resource in...' . rtrim($message));

        return $this->attributes[$this->getKeyName()];
    }

    public function setIdAttribute($value)
    {
        $message = PHP_EOL;
        foreach (debug_backtrace() as $row) {
            $message .= $row['file'] . ':' . $row['line'] . PHP_EOL;
        }
        Log::debug(get_class($this) . '->id was wrote to on a V1 resource in...' . rtrim($message));

        $this->attributes[$this->getKeyName()] = $value;
    }
}
