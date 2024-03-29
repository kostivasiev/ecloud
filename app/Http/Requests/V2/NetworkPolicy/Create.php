<?php
namespace App\Http\Requests\V2\NetworkPolicy;

use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\NetworkHasNoPolicy;
use App\Rules\V2\VpcHasAdvancedNetworking;
use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'network_id' => [
                'required',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
                new NetworkHasNoPolicy(),
                new VpcHasAdvancedNetworking(),
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
