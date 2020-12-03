<?php

namespace App\Responses;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use UKFast\Api\Paginator\UKFastLengthAwarePaginator;

class PaginatedCollectionResponse extends Response
{
    /**
     * PaginatedCollectionResponse constructor.
     * @param $collection Collection
     */
    public function __construct($collection)
    {
        parent::__construct();

        $perPage = app()->request->input('per_page', env('PAGINATION_LIMIT', 100));

        $paginator = new UKFastLengthAwarePaginator(
            array_values($collection->slice(
                (UKFastLengthAwarePaginator::resolveCurrentPage('page') - 1) * $perPage,
                $perPage
            )->all()),
            $collection->count(),
            $perPage
        );

        $paginator->setPath(env('APP_URL') . app()->request->path());

        $this->setContent($paginator);
    }
}
