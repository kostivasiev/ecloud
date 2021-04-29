<?php

namespace App\Traits\V2;

use Symfony\Component\HttpFoundation\Response;

trait DeletionRules
{
    public function canDelete()
    {
        $relationships = collect(
            $this->with($this->children)
                ->findOrFail($this->id)
                ->getRelations()
        )->sum(function ($relation) {
            return (!empty($relation)) ? $relation->count() : 0;
        });
        return $relationships === 0;
    }

    public function getDeletionError()
    {
        return response()->json(
            [
                'errors' => [
                    [
                        'title' => 'Precondition Failed',
                        'detail' => 'The specified resource has dependant relationships and cannot be deleted',
                        'status' => Response::HTTP_PRECONDITION_FAILED,
                        ],
                    ],
                ],
            Response::HTTP_PRECONDITION_FAILED
        );
    }
}
