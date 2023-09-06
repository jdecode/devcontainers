<?php

use App\Services\SubscriptionService;
use Stripe\PaymentMethod as StripePaymentMethod;
use Laravel\Cashier\PaymentMethod;
use Stripe\Stripe;

test('Get Payment details', function () {
    Stripe::setApiKey('sk_test_7mJuPfZsBzc3JkrANrFrcDqC');
    $date = now()->addYear();
    $user = createUser();
    $stripePaymentMethod = StripePaymentMethod::create([
        'type' => 'card',
        'card' => [
            'number' => '4242424242424242',
            'exp_month' => $date->month,
            'exp_year' => $date->year,
            'cvc' => '111'
        ]
    ]);

    $paymentMethod = new PaymentMethod($user, $stripePaymentMethod);
    $service = new SubscriptionService();
    $paymentDetails = $service->getPaymentDetails($paymentMethod);
    $this->assertIsArray($paymentDetails);
    $this->assertArrayHasKey('brand', $paymentDetails);
    $this->assertArrayHasKey('exp_month', $paymentDetails);
    $this->assertArrayHasKey('exp_year', $paymentDetails);
    $this->assertArrayHasKey('last_4', $paymentDetails);
});
