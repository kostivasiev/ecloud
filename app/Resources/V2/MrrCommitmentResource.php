<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class MrrCommitmentResource
 * @package App\Resources\V2
 * @property string id
 * @property string reseller_id
 * @property string contact_id
 * @property string employee_id
 * @property string name
 * @property string commitment_amount
 * @property string commitment_before_discount
 * @property string discount_rate
 * @property string term_length
 * @property string term_start_date
 * @property string term_end_date
 */
class MrrCommitmentResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'contact_id' => $this->contact_id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'commitment_amount' => $this->commitment_amount,
            'commitment_before_discount' => $this->commitment_before_discount,
            'discount_rate' => $this->discount_rate,
            'term_length' => $this->term_length,
            'term_start_date' => Carbon::parse(
                $this->term_start_date,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'term_end_date' => Carbon::parse(
                $this->term_end_date,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'created_at' => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];

        return $data;
    }
}
