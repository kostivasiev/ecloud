<?php
namespace App\Mail;

use App\Models\V2\DiscountPlan;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Mail\Mailable;
use UKFast\Admin\Account\AdminContactClient;
use UKFast\Admin\Account\AdminCustomerClient;
use UKFast\Admin\HR\EmployeeClient;

class DiscountPlanInternal extends Mailable
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
        $customer = $this->customerClient->getById($this->resellerId);
        $accountManager = $this->customerClient->getAccountManager($this->resellerId);
        $employee = $this->employee->getById($this->discountPlan->employee_id);
        if (empty($this->discountPlan->employee_id)) {
            $this->to($accountManager->email ?? config('emails.discount-plan.am-default'));
        } else {
            $this->to($employee->emailAddress);
            $this->cc($accountManager->email);
        }

        // build and send the email
        $this->subject('Discount Plan Agreement #'.$this->discountPlan->id.' confirmation');
        $this->priority($this->priority);
        return $this->view('mail.discountPlanCreated')
            ->with([
                'account_manager' => $accountManager,
                'customer' => $customer,
                'discount_plan' => $this->discountPlan,
                'employee' => $employee,
            ]);
    }
}
