<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\HostSet;
use Illuminate\Http\Request;

class HostSetController extends BaseController
{

    private static $model = HostSet::class;

    /**
     * get item by ID
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public static function getById(Request $request, $id)
    {
        return static::getQuery($request)->findorFail($id);
    }

    /**
     * Return a query builder for the model
     * @param Request $request
     * @return mixed
     */
    public static function getQuery(Request $request)
    {
        $query = self::$model::query();
        if ($request->user->resellerId != 0) {
            $query->join('ucs_reseller', (new self::$model)->getTable() . '.ucs_reseller_id', '=', 'ucs_reseller.ucs_reseller_id')
            ->where('ucs_reseller_reseller_id', '=', $request->user->resellerId);
        }

        return $query;
    }
}
