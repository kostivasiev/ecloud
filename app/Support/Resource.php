<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;

class Resource
{
    use Macroable;

    /**
     * Get the class for a given "key-123456" formatted ID
     * @param $id
     * @return null|string
     */
    public static function classFromId($id)
    {
        list($key) = explode('-', $id);
        $files = array_diff(scandir(app()->basePath('app/Models/V2')), array('.', '..'));
        foreach ($files as $file) {
            $model = 'App\\Models\\V2\\' . str_replace('.php', '', $file);
            if (!class_exists($model)) {
                continue;
            }
            if ($key == (new $model)->keyPrefix) { // TODO :- Why isn't keyPrefix static const?
                return $model;
            }
        }
        return null;
    }

    /**
     * Load a resource using it's ID
     * @param $id
     * @return Model|null
     */
    public static function getFromId($id): ?Model
    {
        $class = static::classFromId($id);
        if (empty($class)) {
            return null;
        }

        return $class::find($id);
    }
}
