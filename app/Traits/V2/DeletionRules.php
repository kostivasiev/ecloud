<?php

namespace App\Traits\V2;

use Illuminate\Support\Facades\Log;

trait DeletionRules
{
    public static function bootDeletionRules()
    {
        static::deleting(function ($model) {
            foreach ($model->children as $child) {
                if ($model->$child()->count() > 0) {
                    throw new \Exception('Child records exist for this item', 412);
                }
            }
        });
    }

    /**
     * @param \Exception $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeletionError(\Exception $exception)
    {
        return response()->json([
            'errors' => [
                'title' => 'Precondition Failed',
                'detail' => $exception->getMessage(),
                'status' => $exception->getCode()
            ]
        ], $exception->getCode());
    }
}
