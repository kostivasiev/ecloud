<?php

namespace App\Http\Requests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\RouterThroughput\ExistsForAvailabilityZone;
use Illuminate\Support\Facades\Auth;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return ($this->user()->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $availabilityZoneId = null;
        if ($this->request->has('availability_zone_id')) {
            $availabilityZoneId = $this->request->get('availability_zone_id');
        }
        // Default AZ
        if (empty($availabilityZoneId) && $this->request->has('vpc_id')) {
            $vpc = Vpc::forUser(Auth::user())->find($this->request->get('vpc_id'));
            if (!empty($vpc)) {
                $availabilityZoneId = $vpc->region
                    ->availabilityZones
                    ->first()->id;
            }
        }

        return [
            'name' => 'nullable|string|max:255',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
            ],
            'router_throughput_id' => [
                'sometimes',
                'required',
                'exists:ecloud.router_throughputs,id,deleted_at,NULL',
                new ExistsForAvailabilityZone($availabilityZoneId)
            ]
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [

            'vpc_id.required' => 'The :attribute field is required',
            'vpc_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
