<?php

namespace App\Http\Controllers\v1\Profile;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Traits\HttpResponse;
use Illuminate\Support\Facades\Auth;

class ProfileSubscriptionController extends Controller
{
    use HttpResponse;

    public function __construct(protected SubscriptionService $subscriptionService)
    {
    }

    public function __invoke()
    {
        $user = Auth::user();
        if (!$user->hasStripeId() || !$user->subscribed()) {
            return $this->response([], __('messages.subscription.no_subscription'));
        }
        $paymentMethod = $user->defaultPaymentMethod() ?? $user->paymentMethods()->first();
        $subscription = $user->subscription();
        $paymentDetails = $this->subscriptionService->getPaymentDetails($paymentMethod);
        return $this->response([
            'billing_portal_url' => $user->billingPortalUrl(),
            'subscription' => [
                'status' => $subscription->stripe_status,
                'created_at' => $subscription->created_at,
                'updated_at' => $subscription->updated_at,
                'quantity' => $subscription->quantity,
                'ends_at' => $subscription->ends_at,
                'on_trial' => $subscription->onTrial(),
                'trial_ends_at' => $subscription->trial_ends_at,
            ],
            'payment' => [
                'type' => $paymentMethod->type,
                'details' => $paymentDetails
            ]
        ]);
    }
}
