<?php

namespace App\Http\Requests\V2\Router;

use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\RouterThroughput\ExistsForAvailabilityZone;
use Illuminate\Support\Facades\Request;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->routerId = Request::route('routerId');
    }


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
        $router = Router::forUser(app('request')->user)->findOrFail($this->routerId);

        return [
            'name' => 'sometimes|required|string',
            'router_throughput_id' => [
                'sometimes',
                'required',
                new ExistsForAvailabilityZone($this->request->get('availability_zone_id') ?? $router->availability_zone_id)
            ],
            'vpc_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class)
            ],
            'availability_zone_id' => 'sometimes|required|string|exists:ecloud.availability_zones,id,deleted_at,NULL',
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
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'vpc_id.required' => 'The :attribute field, when specified, cannot be null',
            'vpc_id.exists' => 'The specified :attribute was not found',
            'availability_zone_id.required' => 'The :attribute field, when specified, cannot be null',
            'availability_zone_id.exists' => 'The specified :attribute was not found',
            'throughput.in' => 'The specified :attribute is not valid. Allowed values are ' . implode(',', Router::THROUGHPUT_OPTIONS)
        ];
    }
}
