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

    public function __construct($server)
    {
        $this->merchant_id = $server['merchant_id'];
        $this->merchant_key = $server['merchant_key'];
        $this->passphrase = $server['passphrase'];
        $this->testmode = $server['testmode'];
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

        // $identifier = $this->payment->onsite->generatePaymentIdentifier($data);
        $identifier = $this->generatePaymentIdentifier($data);

        if ($identifier !== null) {
            return $identifier;
        }
    }

    public function fetchSubscription($token)
    {
        $headers = $this->getHeaders();

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

        if (! $response['uuid']) {
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
