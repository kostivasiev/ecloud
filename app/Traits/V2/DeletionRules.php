<?php

namespace App\Traits\V2;

use Symfony\Component\HttpFoundation\Response;

trait DeletionRules
{
    public function canDelete()
    {
        $relationships = $this->getDependentRelationships()->sum(function ($relation) {
            return $relation->count();
        });
        return $relationships === 0;
    }

    public function getDependentRelationships()
    {
        return collect($this->with($this->children)
            ->findOrFail($this->id)
            ->getRelations());
    }


    /**
     * @deprecated Use App\Http\Middleware\CanBeDeleted middleware instead
     */
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
