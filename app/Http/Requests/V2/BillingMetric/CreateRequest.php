<?php

namespace App\Http\Requests\V2\BillingMetric;

use App\Models\V2\Instance;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Models\V2\VpnService;
use App\Rules\V2\ExistsForUser;
use Illuminate\Support\Facades\Auth;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'resource_id' => [
                'required',
                'string',
                new ExistsForUser([
                    Instance::class,
                    Router::class,
                    Volume::class,
                    VpnService::class,
                ]),
            ],
            'vpc_id' => [
                'required',
                'string',
                new ExistsForUser([
                    Vpc::class,
                ]),
            ],
            'reseller_id' => ['required', 'numeric'],
            'name' => ['sometimes', 'required', 'string'],
            'key' => ['required', 'string'],
            'value' => ['required', 'string'],
            'start' => ['required', 'date'],
            'end' => ['sometimes', 'date'],
            'category' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
        ];

        if (Auth::user()->isAdmin()) {
            $rules['end'][] = 'nullable';
        }

        return $rules;
    }
}
