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
            return $relation->count();
        });
        return $relationships === 0;
    }

    public function getDeletionError()
    {
        return \Illuminate\Http\JsonResponse::create(
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
