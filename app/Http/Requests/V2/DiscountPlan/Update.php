<?php

namespace App\Http\Requests\V2\DiscountPlan;

use App\Rules\V2\CommitmentIsGreater;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Update
 * @package App\Http\Requests\V2
 */
class Update extends FormRequest
{
    public function rules()
    {
        $discountPlanId = app('request')->route('discountPlanId');
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'orderform_id' => [
                'sometimes',
                'required',
                'string',
                'max:36',
            ],
            'commitment_amount' => [
                'sometimes',
                'required',
                'numeric',
                'regex:/^\d+(\.\d{1,2})?$/',
                new CommitmentIsGreater($discountPlanId)
            ],
            'commitment_before_discount' => [
                'sometimes',
                'required',
                'numeric',
                'regex:/^\d+(\.\d{1,2})?$/',
                new CommitmentIsGreater($discountPlanId)
            ],
            'discount_rate' => 'sometimes|required|numeric|min:0|max:100',
            'term_length' => [
                'sometimes',
                'required',
                'nullable',
                'integer',
                'min:1',
                new CommitmentIsGreater($discountPlanId)
            ],
            'term_start_date' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:today',
                new CommitmentIsGreater($discountPlanId)
            ],
            'term_end_date' => [
                'sometimes',
                'required',
                'nullable',
                'date',
                'after:today',
                new CommitmentIsGreater($discountPlanId),
                function ($attribute, $value, $fail) {
                    if (strtotime($value) <= strtotime($this->request->get('term_start_date'))) {
                        $fail('The '.$attribute.' must be greater than the term_start_date');
                    }
                }
            ],
        ];

        if (Auth::user()->isAdmin()) {
            $rules['term_start_date'] = 'sometimes|required|date';
            $rules['status'] = 'sometimes|required|string|in:approved,rejected';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'required' => 'The :attribute field is required',
            'commitment_amount.regex' => 'The :attribute field is not a valid monetary value',
            'term_start_date.after_or_equal' => 'The :attribute field cannot be a date in the past',
            'term_end_date.after' => 'The :attribute field must be a date after today',
        ];
    }
}
