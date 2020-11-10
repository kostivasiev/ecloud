<?php

namespace App\Traits\V2;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait DeletionRules
{
    /**
     * @throws \Exception
     */
    public static function bootDeletionRules()
    {
        static::deleting(function ($model) {
            $model = $model->getRelatableInstance()
                ->findOrFail($model->getKey());
            foreach ($model->getRelations() as $relation) {
                if ($relation->count() > 0) {
                    throw new \Exception(
                        'Active resources exist for this item',
                        412
                    );
                }
            }
        });
    }

    /**
     * @return Builder
     */
    public function getRelatableInstance(): Builder
    {
        return (new self)::with($this->children);
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
