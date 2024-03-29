<?php

namespace App\Http\Requests\V2\BillingMetric;

use App\Models\V2\Instance;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Models\V2\VpnService;
use App\Rules\V2\ExistsForUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'resource_id' => [
                'sometimes',
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
                'sometimes',
                'required',
                'string',
                new ExistsForUser([
                    Vpc::class,
                ]),
            ],
            'reseller_id' => ['sometimes', 'required', 'numeric'],
            'name' => ['sometimes', 'required', 'string'],
            'key' => ['sometimes', 'required', 'string'],
            'value' => ['sometimes', 'required', 'string'],
            'start' => ['sometimes', 'required', 'date'],
            'end' => ['sometimes', 'date'],
            'category' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
        ];


        if (Auth::user()->isAdmin()) {
            $rules['end'][] = 'nullable';
        }

        return $rules;
    }
}
