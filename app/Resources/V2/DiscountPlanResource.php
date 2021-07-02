<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class DiscountPlanResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'commitment_amount' => $this->commitment_amount,
            'commitment_before_discount' => $this->commitment_before_discount,
            'discount_rate' => $this->discount_rate,
            'term_length' => $this->term_length,
            'term_start_date' => Carbon::parse($this->term_start_date)->toDateString(),
            'term_end_date' => Carbon::parse($this->term_end_date)->toDateString(),
            'status' => $this->status,
            'response_date' => $this->response_date ?
                Carbon::parse($this->created_at, new \DateTimeZone(config('app.timezone')))->toIso8601String() : null,
            'created_at' => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];

        if ($request->user()->isAdmin()) {
            $data = $data + [
                'contact_id' => $this->contact_id,
                'employee_id' => $this->employee_id,
                'reseller_id' => $this->reseller_id,
                'orderform_id' => $this->orderform_id,
            ];
        }

        return $data;
    }
}
