<?php

namespace App\Resources\V1;

use App\Services\V1\Resource\CustomResource;

class VirtualMachineTagsResource extends CustomResource
{
    public function toArray($request, $visible = [])
    {
        return [
            'key' => $this->metadata_key,
            'value' => $this->metadata_value,
            'created_at' => $this->metadata_created,
        ];
    }
}
