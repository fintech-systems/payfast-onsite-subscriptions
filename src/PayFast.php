<?php

namespace FintechSystems\PayFast;

use Carbon\Carbon;
use FintechSystems\PayFast\Contracts\BillingProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayFast implements BillingProvider
{
    private string $merchant_id;
    private string $merchant_key;
    private string $passphrase;
    private string $testmode;
    private string $returnUrl;
    private string $cancelUrl;
    private string $notifyUrl;

    public function __construct($client)
    {
        $this->merchant_id = $client['merchant_id'];

        $this->merchant_key = $client['merchant_key'];

        $this->passphrase = $client['passphrase'];

        $this->testmode = $client['testmode'];

        $this->returnUrl = $client['return_url'];

        $this->cancelUrl = $client['cancel_url'];

        $this->notifyUrl = $client['notify_url'];

        $this->urlCollection = [
            'return_url' => $this->returnUrl,
            'cancel_url' => $this->cancelUrl,
            'notify_url' => $this->notifyUrl,
        ];
    }

    public function cancelSubscription($payfast_token)
    {
        $headers = $this->getHeaders();

        ray("cancelSubscription is called with this token", $payfast_token);

        return Http::withHeaders($headers)
            ->put("https://api.payfast.co.za/subscriptions/$payfast_token/cancel")
            ->json();
    }

    /**
     * Create a new subscription using PayFast Onsite Payments
     */
    public function createOnsitePayment($planId, $billingDate = null, $mergeFields = [], $cycles = 0)
    {
        $plan = config('payfast.plans')[$planId];

        $recurringType = Subscription::frequencies($planId);

        ray("billingDate in createOnsitePayment: " . $billingDate);

        $data = [
            'merchant_id' => config('payfast.merchant_id'),
            'merchant_key' => config('payfast.merchant_key'),
            'subscription_type' => 1,
            'm_payment_id' => Order::generate(),
            'amount' => $plan['initial_amount'],
            'recurring_amount' => $plan['recurring_amount'],
            'billing_date' => $billingDate,
            'frequency' => $planId,
            'cycles' => $cycles,
            'custom_str1' => Auth::user()->getMorphClass(),
            'custom_int1' => Auth::user()->getKey(),
            'custom_int2' => $planId,
            'custom_str2' => $plan['name'],
            'item_name' => config('app.name') . " $recurringType Subscription",
            'email_address' => Auth::user()->email,
        ];

        $data = array_merge($data, $this->urlCollection);

        if ($mergeFields) {
            $data = array_merge($data, $mergeFields);
        }

        $message = "The callback URL defined in createOnsitePayment is " . $data['notify_url'];

        ray($message);

        $message = "PayFast onsite payment modal was invoked with these merged values:";

        Log::debug($message);

        ray($message)->orange();

        Log::debug($data);

        ray($data)->orange();

        $signature = PayFast::generateApiSignature($data, config('payfast.passphrase'));

        $pfData = array_merge($data, ["signature" => $signature]);

        // $identifier = $this->payment->onsite->generatePaymentIdentifier($data);
        $identifier = $this->generatePaymentIdentifier($pfData);

        if ($identifier !== null) {
            return $identifier;
        }
    }

    public function fetchSubscription($token)
    {
        $headers = $this->getHeaders();

        ray("fetchSubscription is called with this token", $token);

        return Http::withHeaders($headers)
            ->get("https://api.payfast.co.za/subscriptions/$token/fetch")
            ->json();
    }

    public function ping()
    {
        $headers = $this->getHeaders();

        return Http::withHeaders($headers)
            ->get('https://api.payfast.co.za/ping')
            ->body();
    }

    public function dataToString($dataArray)
    {
        // Create parameter string
        $pfOutput = '';

        foreach ($dataArray as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        
        // Remove last ampersand
        return substr($pfOutput, 0, -1);
    }

    public function generatePaymentIdentifier($pfParameters)
    {
        $url = 'https://www.payfast.co.za/onsite/process';
        
        $response = Http::post($url, $pfParameters)->json();

        if (! isset($response['uuid'])) {
            ray("Unable to generate onsite payment identifier");
            ray("generatePaymentIdentifier parameters:", $pfParameters);
            return null;
        }

        return $response['uuid'];
    }

    public function generateApiSignature($pfData, $passPhrase = null)
    {
        if ($passPhrase !== null) {
            $pfData['passphrase'] = $passPhrase;
        }

        // Sort the array by key, alphabetically
        ksort($pfData);

        //create parameter string
        $pfParamString = http_build_query($pfData);

        return md5($pfParamString);
    }

    private function getHeaders()
    {
        $pfData = [
            'merchant-id' => $this->merchant_id,
            'timestamp' => Carbon::now()->toIso8601String(),
            'version' => "v1",
        ];

        $signature = $this->generateApiSignature($pfData, $this->passphrase);

        return array_merge(
            $pfData,
            ["signature" => $signature]
        );
    }

    /**
     * To ensure our tests are working, we do a dependency injection test and simply return true
     */
    public function di()
    {
        return true;
    }
}
