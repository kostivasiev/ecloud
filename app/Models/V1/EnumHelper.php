<?php

namespace App\Models\V1;

use Illuminate\Support\Facades\DB;

/**
 * Trait EnumHelper
 * @package App\Models\V1
 */
trait EnumHelper
{
    /**
     * Get the possible values from an enum column
     * @param $name
     * @return array
     */
    public static function getEnumValues($name){
        $type = DB::select( DB::raw('SHOW COLUMNS FROM '.(new static)->getTable().' WHERE Field = "'.$name.'"') )[0]->Type;
        preg_match('/^enum\((.*)\)$/', $type, $matches);
        $enum = array();
        foreach(explode(',', $matches[1]) as $value){
            $v = trim( $value, "'" );
            $enum[] = $v;
        }
        return $enum;
    }
}
