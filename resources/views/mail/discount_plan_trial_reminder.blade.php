@extends('mail.components.layout')

@section('content')
<h2>Your eCloud VPC trial is coming to an end.</h2>

<p>Your eCloud VPC trial will expire at midnight on {{ \Carbon\Carbon::parse($discountPlan->term_end_date)->format('l, jS F Y') }}.</p>

<p>We hope that you've had a chance to familiarise yourself with eCloud VPC, and that you choose to continue to use the platform once your trial has ended.</p>

<p><strong>Haven't had a chance to use your trial?</strong> You still have {{{ $daysRemaining }}} {{ Str::plural('day', $daysRemaining) }} left to experiment. <a href="https://portal.ans.co.uk/ecloud">Log in to your account</a> to deploy your first instance and get started.</p>

<p>However, if you would like to end your trial, please <a href="https://portal.ans.co.uk/ecloud">log in to your account</a> and remove any resources to ensure you are not charged for any unwanted usage.</p>
@endsection


