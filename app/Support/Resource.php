<?php

namespace App\Support;

use Illuminate\Support\Traits\Macroable;

class Resource
{
    use Macroable;

    /**
     * Loads a resource for a given "key-123456" formatted ID
     * @param $id
     * @return null
     */
    public static function loadFromId($id)
    {
        list($key, $id) = explode('-', $id);
        $files = array_diff(scandir(app()->basePath('app/Models/V2')), array('.', '..'));
        foreach ($files as $file) {
            $model = 'App\\Models\\V2\\' . str_replace('.php', '', $file);
            if (!class_exists($model)) {
                continue;
            }
            $instance = (new $model);
            if ($key == $instance->keyPrefix) {
                return $instance->findOrFail($key . '-' . $id);
            }
        }
        return null;
    }
}
