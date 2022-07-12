@extends('mail.components.layout')

@section('content')
    <h2>Your eCloud VPC trial expires at midnight.</h2>

    <p>We hope that you've had a chance to familiarise yourself with eCloud VPC, and that you choose to continue to use the platform once your trial has ended.</p>

    <p><strong>Don't wish to continue with your trial?</strong> You have until midnight tonight ({{ \Carbon\Carbon::parse($discountPlan->term_end_date)->format('l, jS F Y') }}) to <a href="https://portal.ans.co.uk/ecloud">log in to your account</a>  and remove any resources to ensure you are not charged for any unwanted usage.</p>

    <p>Thank you for exploring this trial with eCloud VPC, we hope you had a chance to create something amazing, and look forward to your continued custom.</p>
@endsection




