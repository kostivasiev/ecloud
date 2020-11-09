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
            if (method_exists($model, 'getRelatableInstance')) {
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
            }
        });
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithAll(Builder $query): Builder
    {
        return $query->with($this->children);
    }

    /**
     * @return Builder
     */
    public function getRelatableInstance(): Builder
    {
        return (new self)::withAll();
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
