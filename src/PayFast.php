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
        $this->testmode = $client['testmode'];

        if ($this->testmode == true) {
            $this->merchant_id = $client['merchant_id_test'];
            $this->merchant_key = $client['merchant_key_test'];
            $this->passphrase = $client['passphrase_test'];
            $this->url = 'https://sandbox.payfast.co.za​/onsite/process';
            $prependUrl = config('payfast.callback_url_test');
        } else {
            $this->merchant_id = $client['merchant_id'];
            $this->merchant_key = $client['merchant_key'];
            $this->passphrase = $client['passphrase'];
            $this->url = 'https://www.payfast.co.za/onsite/process';
            $prependUrl = config('payfast.callback_url');
        }

        ray("In PayFast constructor, testmode: $this->testmode, URL: $this->url");

        $this->returnUrl = $prependUrl . $client['return_url'];
        $this->cancelUrl = $prependUrl . $client['cancel_url'];
        $this->notifyUrl = $prependUrl . $client['notify_url'];

        $this->urlCollection = [
            'return_url' => $this->returnUrl,
            'cancel_url' => $this->cancelUrl,
            'notify_url' => $this->notifyUrl,
        ];
    }

    public function cancelSubscription($payfast_token)
    {
        ray("cancelSubscription is called with this token: $payfast_token")->blue();

        $append = ($this->testmode == true ? 'testing=true' : "");

        $response = Http::withHeaders($this->headers())
            ->put("https://api.payfast.co.za/subscriptions/$payfast_token/cancel?$append")
            ->json();        

        ray($response['data']['message'])->green();

        return $response;
    }

    /**
     * Create a new subscription using PayFast Onsite Payments
     */
    public function createOnsitePayment($planId, $billingDate = null, $mergeFields = [], $cycles = 0)
    {
        $plan = config('payfast.plans')[$planId];

        $recurringType = Subscription::frequencies($planId);

        ray("billingDate in createOnsitePayment: $billingDate");

        $data = [
            'merchant_id' => $this->merchantId(),
            'merchant_key' => $this->merchantKey(),
            'subscription_type' => 1,
            'm_payment_id' => Order::generate(),
            'amount' => $plan['initial_amount'],
            'recurring_amount' => $plan['recurring_amount'],
            'billing_date' => $billingDate,
            'frequency' => $planId,
            'cycles' => $cycles,
            'custom_str1' => Auth::user()->getMorphClass(),
            'custom_int1' => Auth::user()->getKey(),
            'custom_str2' => $plan['name'],
            'custom_int2' => $planId,            
            'item_name' => config('app.name') . " $recurringType Subscription",
            'email_address' => Auth::user()->email,
        ];

        $data = array_merge($data, $this->urlCollection);

        if ($mergeFields) {
            $data = array_merge($data, $mergeFields);
        }
        
        $message = "The PayFast onsite modal was invoked with these merged values and will now wait for user input:";

        ray($message)->purple();
        ray($data)->green();

        $signature = PayFast::generateApiSignature($data, $this->passphrase());

        $pfData = array_merge($data, ["signature" => $signature]);
        
        return $this->generatePaymentIdentifier($pfData);                
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

    /**
     * Fetch subscription information information from the API.
     */
    public function fetchSubscription($token)
    {
        ray("fetchSubscription is called with this token: $token")->blue();

        $append = ($this->testmode == true ? 'testing=true' : "");

        $response = Http::withHeaders($this->headers())
            ->get("https://api.payfast.co.za/subscriptions/$token/fetch?$append")
            ->json();

        ray($response['data']['response'])->green();

        return $response;
    }

    /**
     * Helper to determine current subscription state of a subscribed user.
     */
    public function getSubscriptionStatus($user) {
        if ($user->subscribed('default')) {
            return "Subscribed";
        }

        return "No data";
    }

    /**
     * Generate Payment Identifier
     *
     * Has different behavior in test versus live. In test
     * mode it returns the HTML processing page, in live
     * mode it returns a payment identifier.
     */
    public function generatePaymentIdentifier($pfParameters)
    {
        $response = Http::post($this->url, $pfParameters);

        if (!isset($response['uuid'])) {
            ray("generatePaymentdentifier failed as response didn't have UUID. Output request parameters and response body(): ", $pfParameters);

            ray($response->body());

            return null;
        }

        return $response['uuid'];
    }

    public function generateApiSignature($pfData, $passPhrase = null)
    {
        if ($passPhrase !== null) {
            $pfData['passphrase'] = $passPhrase;
        }

        // Sort the array alphabetically by key
        ksort($pfData);

        $pfParamString = http_build_query($pfData);

        return md5($pfParamString);
    }

    private function headers()
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

    // Public getters

    public function callbackUrl() {
        if ($this->testmode == 'true') {
            return config('payfast.callback_url_test');
        } else {
            return config('payfast.callback_url');
        }
    }

    public function merchantId()
    {
        return $this->merchant_id;
    }

    public function merchantKey()
    {
        return $this->merchant_key;
    }

    public function passphrase()
    {
        return $this->passphrase;
    }

    public function url()
    {
        return $this->url;
    }

    /**
     * To ensure our tests are working, we do a dependency injection test and simply return true
     */
    public function di()
    {
        return true;
    }

    public function ping()
    {
        return Http::withHeaders($this->headers())
            ->get('https://api.payfast.co.za/ping')
            ->body();
    }
}
