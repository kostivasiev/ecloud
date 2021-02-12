<?php
namespace App\Http\Requests\V2\NetworkPolicy;

use App\Models\V2\Network;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\NetworkHasNoAcl;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
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
        return [
            'network_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
                new NetworkHasNoAcl(),
            ],
            'vpc_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class)
            ],
            'name' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [];
    }
}
