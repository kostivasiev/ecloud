<?php
namespace App\Http\Requests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnServiceVpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnEndpoint;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        $id = $this->route()[2]['vpnEndpointId'];
        return [
            'name' => 'sometimes|required|string',
        ];
    }
}
