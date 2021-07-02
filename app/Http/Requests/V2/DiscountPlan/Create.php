<?php

namespace App\Http\Requests\V2\DiscountPlan;

use App\Rules\V2\DateIsTodayOrFirstOfMonth;
use Illuminate\Support\Facades\Auth;
use UKFast\FormRequests\FormRequest;

/**
 * Class Create
 * @package App\Http\Requests\V2
 */
class Create extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
//            'contact_id' => 'sometimes|required_without:employee_id|exists:reseller.reseller_contact,reseller_contact_id',
//            'employee_id' => 'sometimes|required_without:contact_id|exists:holiday.employee,employee_id',
            'contact_id' => 'sometimes|required_without:employee_id|integer',
            'employee_id' => 'sometimes|required_without:contact_id|integer',
            'orderform_id' => [
                'sometimes',
                'required',
                'string',
                'max:36',
                'unique:ecloud.discount_plans,orderform_id,NULL,id,deleted_at,NULL',
            ],
            'reseller_id' => 'sometimes|required|integer',
            'name' => 'sometimes|required|string|max:255',
            'commitment_amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'commitment_before_discount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'discount_rate' => 'required|numeric|min:0|max:100',
            'term_length' => 'required|integer|min:1',
            'term_start_date' => [
                'required',
                'date',
                new DateIsTodayOrFirstOfMonth()
            ],
            'term_end_date' => [
                'sometimes',
                'required',
                'date',
                'after:today',
                function ($attribute, $value, $fail) {
                    if (strtotime($value) <= strtotime($this->request->get('term_start_date'))) {
                        $fail('The '.$attribute.' must be greater than the term_start_date');
                    }
                }
            ],
        ];

        if (Auth::user()->isAdmin()) {
            $rules['term_start_date'] = 'required|date';
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
