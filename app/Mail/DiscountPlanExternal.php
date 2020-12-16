<?php
namespace App\Mail;

use App\Models\V2\DiscountPlan;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Mail\Mailable;
use UKFast\Admin\Account\AdminContactClient;
use UKFast\Admin\Account\AdminCustomerClient;
use UKFast\Admin\HR\EmployeeClient;

class DiscountPlanExternal extends Mailable
{

    protected AdminCustomerClient $customerClient;
    protected AdminContactClient $contactClient;
    protected DiscountPlan $discountPlan;
    protected EmployeeClient $employee;
    protected int $resellerId;

    public int $priority = 3;

    public function __construct(DiscountPlan $discountPlan)
    {
        $this->contactClient = new AdminContactClient();
        $this->customerClient = new AdminCustomerClient();
        $this->discountPlan = $discountPlan;
        $this->employee = new EmployeeClient();
        $this->resellerId = app('request')->user->resellerId;
    }

    public function build()
    {
        try {
            $primaryContactId = ($this->customerClient->getById($this->resellerId))->primaryContactId;
            if ($primaryContactId) {
                $primaryContactEmail = ($this->contactClient->getById($primaryContactId))->email_address;
                if ($primaryContactId == $this->discountPlan->contact_id) {
                    $this->to($primaryContactEmail);
                } else {
                    $this->to(($this->contactClient->getById($this->discountPlan->contact_id))->email_address);
                    $this->cc($primaryContactEmail);
                }
            }
        } catch (GuzzleException $e) {
            $this->to(config('emails.discount-plan.default.to'));
        }
        // build and send the email
        $this->subject('Discount Plan Agreement #'.$this->discountPlan->id.' confirmation');
        $this->priority($this->priority);

        return $this->view('mail.discountPlanCreated')
            ->with([
                'discount_plan' => $this->discountPlan,
            ]);
    }
}
