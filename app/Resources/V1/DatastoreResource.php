<?php

namespace App\Resources\V1;

use Illuminate\Support\Facades\Log;
use UKFast\Api\Resource\CustomResource;
use App\Models\V1\Datastore;

class DatastoreResource extends CustomResource
{
    /**
     * Transform the data resource into an array.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param \Illuminate\Http\Request $request
     * @param array $visible
     * @return array
     */
    public function toArray($request, $visible = [])
    {
        $response_type = null;
        if ($visible == Datastore::$collectionProperties) {
            $response_type = 'collection';
        } elseif ($visible == Datastore::$itemProperties) {
            $response_type = 'item';
        }

        // return additional admin properties if required
        // Auth::user()->isAdmin()
        // $request->user()
        if ($request->user->isAdministrator) {
            $visible = array_merge(
                $visible,
                Datastore::$adminProperties
            );
        }

        $attributes = $this->getModelAttributes($this->resource, $request, $visible);
        if ($response_type === 'item') {
            try {
                $attributes = array_merge($attributes, [
                    'allocated' => $this->resource->usage->provisioned,
                    'available' => $this->resource->usage->available,
                ]);
            } catch (\Exception $e) {
                Log::info(
                    'Failed to load VMWare usage for datastore',
                    [
                        'datastore_id' => $attributes['id']
                    ]
                );
                $attributes = array_merge($attributes, [
                    'allocated' => 'Unknown',
                    'available' => 'Unknown',
                ]);
            }

            if ($request->user->isAdministrator) {
                try {
                    $volumeSet = $this->resource->volumeSet();
                    $iopsTier = $volumeSet->getIopsTier();

                    $attributes = array_merge($attributes, [
                        'iops_tier' => !empty($iopsTier) ? $iopsTier->getKey() : null,
                    ]);
                } catch (\Exception $exception) {
                    Log::info(
                        'Failed to load IOPS tier for datastore',
                        [
                            'datastore_id' => $attributes['id']
                        ]
                    );

                    $attributes = array_merge($attributes, [
                        'iops_tier' => null,
                    ]);
                }
            }
        }

        return $this->filterProperties($request, $attributes);
    }
}
