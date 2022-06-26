<?php

namespace Tests\Feature;

use FintechSystems\PayFast\Cashier;
use FintechSystems\PayFast\Events\PaymentSucceeded;
use FintechSystems\PayFast\Events\SubscriptionCancelled;
use FintechSystems\PayFast\Events\SubscriptionCreated;
use FintechSystems\PayFast\Events\SubscriptionPaymentSucceeded;
use FintechSystems\PayFast\Events\SubscriptionUpdated;
use FintechSystems\PayFast\Subscription;
use Illuminate\Support\Facades\Http;

class WebhooksTest extends FeatureTestCase
{
    public function test_gracefully_handle_webhook_without_alert_name()
    {
        $this->postJson('payfast/webhook', [
            'event_time' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertOk();
    }

    // public function test_it_can_handle_a_payment_succeeded_event()
    // {
    //     Http::fake();

    //     $user = $this->createUser();

    //     ray("Customer in handle payment is created", $user);

    //     $this->postJson('payfast/webhook', [
    //         "m_payment_id" => "11-20220606194740",
    //         "pf_payment_id" => "86883189",
    //         "payment_status" => "COMPLETE",
    //         "item_name" => "PayFast Test Monthly Subscription",
    //         "item_description" => "PayFast-Test subscription for Unsubscribed #4",
    //         "amount_gross" => "5.99",
    //         "amount_fee" => "-2.52",
    //         "amount_net" => "3.47",
    //         "custom_str1" => "App\Models\User",
    //         "custom_str2" => "Monthly R 99",
    //         "custom_str3" => null,
    //         "custom_str4" => null,
    //         "custom_str5" => null,
    //         "custom_int1" => "1", // User
    //         "custom_int2" => "3", // Plan
    //         "custom_int3" => null,
    //         "custom_int4" => null,
    //         "custom_int5" => null,
    //         "name_first" => "Unsubscribed #4",
    //         "name_last" => "Unsubscribed #4",
    //         "email_address" => "eugene4@vander.host",
    //         "merchant_id" => "13741656",
    //         "token" => "5ec913d8-54a0-40f6-be19-04ffc614aa2d",
    //         "billing_date" => "2022-06-06",
    //         "signature" => "8d1b6a3f61e1bd63d8f7584b02b9fc0a",
    //         // 'alert_name' => 'payment_succeeded',
    //         // 'event_time' => $paidAt = now()->addDay()->format('Y-m-d H:i:s'),
    //         // 'checkout_id' => 12345,
    //         // 'order_id' => 'foo',
    //         // 'email' => $user->payFastEmail(),
    //         // 'sale_gross' => '12.55',
    //         // 'payment_tax' => '4.34',
    //         // 'currency' => 'EUR',
    //         // 'quantity' => 1,
    //         // 'receipt_url' => 'https://example.com/receipt.pdf',
    //         // 'passthrough' => json_encode([
    //         //     'billable_id' => $user->id,
    //         //     'billable_type' => $user->getMorphClass(),
    //         // ]),
    //     ])->assertOk();

    //     ray("WebhooksTest post is done");

    //     $this->assertDatabaseHas('customers', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //     ]);

    //     $this->assertDatabaseHas('receipts', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //         'paddle_subscription_id' => null,
    //         'paid_at' => $paidAt,
    //         'checkout_id' => 12345,
    //         'order_id' => 'foo',
    //         'amount' => '12.55',
    //         'tax' => '4.34',
    //         'currency' => 'EUR',
    //         'quantity' => 1,
    //         'receipt_url' => 'https://example.com/receipt.pdf',
    //     ]);

    //     Cashier::assertPaymentSucceeded(function (PaymentSucceeded $event) use ($user) {
    //         return $event->billable->id === $user->id && $event->receipt->order_id === 'foo';
    //     });
    // }

    // public function test_it_can_handle_a_payment_succeeded_event_when_billable_already_exists()
    // {
    //     Cashier::fake();

    //     $user = $this->createBillable('taylor', [
    //         'trial_ends_at' => now('UTC')->addDays(5),
    //     ]);

    //     $this->postJson('paddle/webhook', [
    //         'alert_name' => 'payment_succeeded',
    //         'event_time' => $paidAt = now()->addDay()->format('Y-m-d H:i:s'),
    //         'checkout_id' => 12345,
    //         'order_id' => 'foo',
    //         'email' => $user->paddleEmail(),
    //         'sale_gross' => '12.55',
    //         'payment_tax' => '4.34',
    //         'currency' => 'EUR',
    //         'quantity' => 1,
    //         'receipt_url' => 'https://example.com/receipt.pdf',
    //         'passthrough' => json_encode([
    //             'billable_id' => $user->id,
    //             'billable_type' => $user->getMorphClass(),
    //         ]),
    //     ])->assertOk();

    //     $this->assertDatabaseHas('customers', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //     ]);

    //     $this->assertDatabaseHas('receipts', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //         'paddle_subscription_id' => null,
    //         'paid_at' => $paidAt,
    //         'checkout_id' => 12345,
    //         'order_id' => 'foo',
    //         'amount' => '12.55',
    //         'tax' => '4.34',
    //         'currency' => 'EUR',
    //         'quantity' => 1,
    //         'receipt_url' => 'https://example.com/receipt.pdf',
    //     ]);

    //     Cashier::assertPaymentSucceeded(function (PaymentSucceeded $event) use ($user) {
    //         return $event->billable->id === $user->id && $event->receipt->order_id === 'foo';
    //     });
    // }

    // public function test_it_can_handle_a_subscription_payment_succeeded_event()
    // {
    //     Cashier::fake();

    //     $user = $this->createBillable();

    //     $subscription = $user->subscriptions()->create([
    //         'name' => 'main',
    //         'paddle_id' => 244,
    //         'paddle_plan' => 2323,
    //         'paddle_status' => Subscription::STATUS_ACTIVE,
    //         'quantity' => 1,
    //     ]);

    //     $this->postJson('paddle/webhook', [
    //         'alert_name' => 'subscription_payment_succeeded',
    //         'event_time' => $paidAt = now()->addDay()->format('Y-m-d H:i:s'),
    //         'subscription_id' => $subscription->paddle_id,
    //         'checkout_id' => 12345,
    //         'order_id' => 'foo',
    //         'email' => $user->paddleEmail(),
    //         'sale_gross' => '12.55',
    //         'payment_tax' => '4.34',
    //         'currency' => 'EUR',
    //         'quantity' => 1,
    //         'receipt_url' => 'https://example.com/receipt.pdf',
    //         'passthrough' => json_encode([
    //             'billable_id' => $user->id,
    //             'billable_type' => $user->getMorphClass(),
    //         ]),
    //     ])->assertOk();

    //     $this->assertDatabaseHas('customers', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //     ]);

    //     $this->assertDatabaseHas('receipts', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //         'paddle_subscription_id' => $subscription->paddle_id,
    //         'paid_at' => $paidAt,
    //         'checkout_id' => 12345,
    //         'order_id' => 'foo',
    //         'amount' => '12.55',
    //         'tax' => '4.34',
    //         'currency' => 'EUR',
    //         'quantity' => 1,
    //         'receipt_url' => 'https://example.com/receipt.pdf',
    //     ]);

    //     Cashier::assertSubscriptionPaymentSucceeded(function (SubscriptionPaymentSucceeded $event) use ($user) {
    //         return $event->billable->id === $user->id && $event->receipt->order_id === 'foo';
    //     });
    // }

    // public function test_it_can_handle_a_subscription_created_event()
    // {
    //     Cashier::fake();

    //     $user = $this->createUser();

    //     $this->postJson('paddle/webhook', [
    //         'alert_name' => 'subscription_created',
    //         'user_id' => 'foo',
    //         'email' => $user->paddleEmail(),
    //         'passthrough' => json_encode([
    //             'billable_id' => $user->id,
    //             'billable_type' => $user->getMorphClass(),
    //             'subscription_name' => 'main',
    //         ]),
    //         'quantity' => 1,
    //         'status' => Subscription::STATUS_ACTIVE,
    //         'subscription_id' => 'bar',
    //         'subscription_plan_id' => 1234,
    //     ])->assertOk();

    //     $this->assertDatabaseHas('customers', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //     ]);

    //     $this->assertDatabaseHas('subscriptions', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //         'name' => 'main',
    //         'paddle_id' => 'bar',
    //         'paddle_plan' => 1234,
    //         'paddle_status' => Subscription::STATUS_ACTIVE,
    //         'quantity' => 1,
    //         'trial_ends_at' => null,
    //     ]);

    //     Cashier::assertSubscriptionCreated(function (SubscriptionCreated $event) use ($user) {
    //         return $event->billable->id === $user->id && $event->subscription->paddle_plan === 1234;
    //     });
    // }

    // public function test_it_can_handle_a_subscription_created_event_if_billable_already_exists()
    // {
    //     Cashier::fake();

    //     $user = $this->createUser();
    //     $user->customer()->create([
    //         'trial_ends_at' => now('UTC')->addDays(5),
    //     ]);

    //     $this->postJson('paddle/webhook', [
    //         'alert_name' => 'subscription_created',
    //         'user_id' => 'foo',
    //         'email' => $user->paddleEmail(),
    //         'passthrough' => json_encode([
    //             'billable_id' => $user->id,
    //             'billable_type' => $user->getMorphClass(),
    //             'subscription_name' => 'main',
    //         ]),
    //         'quantity' => 1,
    //         'status' => Subscription::STATUS_ACTIVE,
    //         'subscription_id' => 'bar',
    //         'subscription_plan_id' => 1234,
    //     ])->assertOk();

    //     $this->assertDatabaseHas('customers', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //     ]);

    //     $this->assertDatabaseHas('subscriptions', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //         'name' => 'main',
    //         'paddle_id' => 'bar',
    //         'paddle_plan' => 1234,
    //         'paddle_status' => Subscription::STATUS_ACTIVE,
    //         'quantity' => 1,
    //         'trial_ends_at' => null,
    //     ]);

    //     Cashier::assertSubscriptionCreated(function (SubscriptionCreated $event) use ($user) {
    //         return $event->billable->id === $user->id && $event->subscription->paddle_plan === 1234;
    //     });
    // }

    // public function test_it_can_handle_a_subscription_updated_event()
    // {
    //     Cashier::fake();

    //     $billable = $this->createBillable('taylor');

    //     $subscription = $billable->subscriptions()->create([
    //         'name' => 'main',
    //         'paddle_id' => 244,
    //         'paddle_plan' => 2323,
    //         'paddle_status' => Subscription::STATUS_ACTIVE,
    //         'quantity' => 1,
    //     ]);

    //     $this->postJson('paddle/webhook', [
    //         'alert_name' => 'subscription_updated',
    //         'new_quantity' => 3,
    //         'status' => Subscription::STATUS_PAUSED,
    //         'paused_from' => ($date = now('UTC')->addDays(5))->format('Y-m-d H:i:s'),
    //         'subscription_id' => 244,
    //         'subscription_plan_id' => 1234,
    //     ])->assertOk();

    //     $this->assertDatabaseHas('subscriptions', [
    //         'id' => $subscription->id,
    //         'billable_id' => $billable->id,
    //         'billable_type' => $billable->getMorphClass(),
    //         'name' => 'main',
    //         'paddle_id' => 244,
    //         'paddle_plan' => 1234,
    //         'paddle_status' => Subscription::STATUS_PAUSED,
    //         'quantity' => 3,
    //         'paused_from' => $date,
    //     ]);

    //     Cashier::assertSubscriptionUpdated(function (SubscriptionUpdated $event) {
    //         return $event->subscription->paddle_plan === 1234;
    //     });
    // }

    // public function test_it_can_handle_a_subscription_cancelled_event()
    // {
    //     Cashier::fake();

    //     $billable = $this->createBillable('taylor');

    //     $subscription = $billable->subscriptions()->create([
    //         'name' => 'main',
    //         'paddle_id' => 244,
    //         'paddle_plan' => 2323,
    //         'paddle_status' => Subscription::STATUS_ACTIVE,
    //         'quantity' => 1,
    //     ]);

    //     $this->postJson('paddle/webhook', [
    //         'alert_name' => 'subscription_cancelled',
    //         'status' => Subscription::STATUS_DELETED,
    //         'cancellation_effective_date' => ($date = now('UTC')->addDays(5)->startOfDay())->format('Y-m-d'),
    //         'subscription_id' => 244,
    //     ])->assertOk();

    //     $this->assertDatabaseHas('subscriptions', [
    //         'id' => $subscription->id,
    //         'billable_id' => $billable->id,
    //         'billable_type' => $billable->getMorphClass(),
    //         'name' => 'main',
    //         'paddle_id' => 244,
    //         'paddle_plan' => 2323,
    //         'paddle_status' => Subscription::STATUS_DELETED,
    //         'ends_at' => $date,
    //     ]);

    //     Cashier::assertSubscriptionCancelled(function (SubscriptionCancelled $event) {
    //         return $event->subscription->paddle_plan === 2323;
    //     });
    // }

    // public function test_manual_created_paylinks_without_passthrough_values_are_ignored()
    // {
    //     Cashier::fake();

    //     $user = $this->createUser();

    //     $this->postJson('paddle/webhook', [
    //         'alert_name' => 'subscription_created',
    //         'user_id' => 'foo',
    //         'email' => $user->paddleEmail(),
    //         'passthrough' => '',
    //         'quantity' => 1,
    //         'status' => Subscription::STATUS_ACTIVE,
    //         'subscription_id' => 'bar',
    //         'subscription_plan_id' => 1234,
    //     ])->assertOk();

    //     $this->assertDatabaseMissing('customers', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //     ]);

    //     $this->assertDatabaseMissing('subscriptions', [
    //         'billable_id' => $user->id,
    //         'billable_type' => $user->getMorphClass(),
    //         'name' => 'main',
    //         'paddle_id' => 'bar',
    //         'paddle_plan' => 1234,
    //         'paddle_status' => Subscription::STATUS_ACTIVE,
    //         'quantity' => 1,
    //         'trial_ends_at' => null,
    //     ]);

    //     Cashier::assertSubscriptionNotCreated();
    // }
}
