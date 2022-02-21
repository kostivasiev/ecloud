<?php

namespace App\Traits\V2;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

trait DeletionRules
{
    public function canDelete()
    {
        $relationships = $this->getDependentRelationships()->sum(function ($relation) {

            if ($relation instanceof Collection) {
                return $relation->count();
            }

            if ($relation instanceof Model) {
                return 1;
            }

            return 0;
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
