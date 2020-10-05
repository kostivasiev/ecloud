<?php

namespace App\Models\V1;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trait EnumHelper
 * @package App\Models\V1
 */
trait EnumHelper
{
    /**
     * Get the possible values from an enum column
     * @param $name
     * @return array|boolean
     */
    public static function getEnumValues($name)
    {
        try {
            $type = DB::select(
                DB::raw('SHOW COLUMNS FROM ' . (new static)->getTable() . ' WHERE Field = "' . $name . '"')
            )[0]->Type;
        } catch (\Illuminate\Database\QueryException $exception) {
            Log::error(
                'Failed to load column \'' . $name . '\' enum values, SQL failure: ' . $exception->getMessage()
            );
            return false;
        }

        if (empty($type)) {
            Log::error('Failed to load column \'' . $name . '\' enum values: Query returned empty data');
            return false;
        }

        if (preg_match('/^(?:enum|set)\((.*)\)$/', $type, $matches) === 1) {
            $enum = [];
            foreach (explode(',', $matches[1]) as $value) {
                $v = trim($value, "'");
                $enum[] = $v;
            }
            return $enum;
        }

        Log::error('Failed to load column \'' . $name . '\' enum values. Column is not enum or has no enum values.');
        return false;
    }
}
