<?php
namespace App\Http\Requests\V2\NetworkPolicy;

use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\NetworkHasNoPolicy;
use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
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
            'name' => 'nullable|string',
            'network_id' => [
                'required',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
                new NetworkHasNoPolicy(),
                new IsResourceAvailable(Network::class),
            ],
            'catchall_rule_action' => [
                'sometimes',
                'required',
                'string',
                'in:ALLOW,DROP,REJECT'
            ],
        ];
    }

    public function messages()
    {
        return [];
    }
}
