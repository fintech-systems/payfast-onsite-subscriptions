<?php

namespace FintechSystems\Payfast;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Exception;
use FintechSystems\Payfast\Contracts\BillingProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Payfast implements BillingProvider
{
    private string $merchant_id;
    private string $merchant_key;
    private string $passphrase;

    private string $test_mode;

    private string $returnUrl;
    private string $cancelUrl;
    private string $notifyUrl;

    public function __construct($client)
    {
        $this->test_mode = $client['test_mode'] ?? false;

        $prependUrl = "";

        if ($this->test_mode) {
            $this->merchant_id = $client['merchant_id_test'];
            $this->merchant_key = $client['merchant_key_test'];
            $this->passphrase = $client['passphrase_test'];
            $this->url = 'https://sandbox.payfast.co.za​/onsite/process';
            $prependUrl = config('payfast.test_mode_callback_url');
        } else {
            $this->merchant_id = $client['merchant_id'];
            $this->merchant_key = $client['merchant_key'];
            $this->passphrase = $client['passphrase'];
            $this->url = 'https://www.payfast.co.za/onsite/process';
            //            $prependUrl = config('payfast.callback_url');
        }

        if (config('payfast.debug') == true) {
            //            Log::debug("In Payfast API constructor, test_mode: $this->test_mode, URL: $this->url");
        }

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

        $append = ($this->test_mode == true ? 'testing=true' : "");

        $response = Http::withHeaders($this->headers())
            ->put("https://api.payfast.co.za/subscriptions/$payfast_token/cancel?$append")
            ->json();

        ray($response['data']['message'])->green();

        return $response;
    }

    /**
     * Create a new subscription using Payfast Onsite Payments. One of the most
     * important aspects is ensuring that the correct billing date is sent
     * with the order, and also on renewals the initial amount is zero
     *
     * @param $planId
     * @param null $billingDate
     * @param array $mergeFields
     * @param int $cycles
     *
     * $mergeFields may be used to overwrite values, for example, to make the amount R 0 for subscription renewals
     *
     * @return mixed
     * @throws Exception
     */
    public function createOnsitePayment($planId, $billingDate = null, array $mergeFields = [], int $cycles = 0)
    {
        $plan = $this->getPlanDetail($planId);

        $data = [
            'merchant_id' => $this->merchantId(),
            'merchant_key' => $this->merchantKey(),
            'subscription_type' => 1, // required for subscriptions - sets type to a subscription
            'm_payment_id' => Order::generate(),
            'amount' => $plan['initial_amount'],
            'recurring_amount' => $plan['recurring_amount'],
            'billing_date' => $billingDate,
            'frequency' => $plan['frequency'],
            'cycles' => $cycles,
            'custom_str1' => Auth::user()->getMorphClass(),
            'custom_int1' => Auth::user()->getKey(),
            'custom_str2' => $plan['name'],
            'custom_int2' => $planId + 1, // Array index starts at zero but this might be plan #1
            'item_name' => $plan['item_name'],
            'email_address' => Auth::user()->email,
        ];

        $data = array_merge($data, $this->urlCollection);

        if ($mergeFields) {
            $data = array_merge($data, $mergeFields);
        }

        $message = "The Payfast onsite modal was invoked with these merged values and will now wait for user input:";

        $this->debug($message, 'displayPayfastModal');
        $this->debug($data, 'notice');

        $signature = Payfast::generateApiSignature($data, $this->passphrase());

        $pfData = array_merge($data, ["signature" => $signature]);

        return $this->generatePaymentIdentifier($pfData);
    }

    public function dataToString($dataArray): string
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
     * A simple debugger that combines what Ray can do and built-in Laravel logging.
     * Defaults to debug and purple if logging is anything except the defaults.
     * Won't log to local in the application isn't in production.
     */
    public function debug($message, $level = 'debug'): void
    {
        $color = match ($level) {
            'debug' => 'gray',
            'info' => 'blue',
            'notice' => 'green',
            'warning' => 'orange',
            'error', 'critical', 'alert', 'emergency' => 'red',
            default => 'purple',
        };
        ray($message)->$color();

        if ($color == 'purple') {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

            $line = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['line'];

            $level = 'debug';

            $message = $caller . "#$line|" . $message;
        }

        if ($level == 'debug' && ! config('payfast.debug')) {
            return;
        }

        if (config('app.env') == 'production') {
            Log::$level($message);
        }
    }

    /**
     * Fetch subscription information from the API.
     */
    public function fetchSubscription($token)
    {
        ray("fetchSubscription is called with this token: $token")->blue();

        $append = ($this->test_mode ? 'testing=true' : "");

        $response = Http::withHeaders($this->headers())
            ->get("https://api.payfast.co.za/subscriptions/$token/fetch?$append")
            ->json();

        ray($response['data']['response'])->green();

        return $response;
    }

    /**
     * When the Payfast Onsite Payments modal fails to load with a 404, the underlying HTML
     * sometimes contains an error message. By investigating the response body from the
     * Laravel HTTP Post request, we can traverse the DOM to obtain the error.
     *
     * @param $html
     * @return string|false
     */
    private function extractErrorMessageFromHtml($html): string|bool
    {
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $result = $xpath->query('//span[@class="err-msg"]');

        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        }

        return false;
    }

    /**
     * To use a Spark compatible configuration, we need to convert the UI select box
     * plan ID to the correct setup and recurring amounts. We do this by dividing
     * the ID of the plan by two and then choosing the monthly or yearly values.
     *
     * @param $planId
     * @return array
     */
    public function getPlanDetail($planId): array
    {
        $id = (int) $planId / 3;

        $plan = config('payfast.billables.user.plans')[$id];

        $planDetail = [];
        $recurringType = "";

        if ($planId % 3 == 0) {
            $recurringType = "Daily";
            $planDetail['frequency'] = 3;
            $planDetail['initial_amount'] = $plan['daily']['setup_amount'] ?? 0;
            $planDetail['recurring_amount'] = $plan['daily']['recurring_amount'];
        }

        if ($planId % 3 == 1) {
            $recurringType = "Monthly";
            $planDetail['frequency'] = 3;
            $planDetail['initial_amount'] = $plan['monthly']['setup_amount'] ?? 0;
            $planDetail['recurring_amount'] = $plan['monthly']['recurring_amount'];
        }

        if ($planId % 3 == 2) {
            $recurringType = "Yearly";
            $planDetail['frequency'] = 6;
            $planDetail['initial_amount'] = $plan['yearly']['setup_amount'] ?? 0;
            $planDetail['recurring_amount'] = $plan['yearly']['recurring_amount'];
        }

        $planDetail['name'] = $plan['name'];
        $planDetail['item_name'] = config('app.name') . " $recurringType Subscription";

        return $planDetail;
    }

    /**
     * Helper to determine the current subscription state of a subscribed user.
     */
    public function getSubscriptionStatus($user): array
    {
        return SubscriptionStatus::for($user);
    }

    /**
     * Generate Payment Identifier
     *
     * Has different behavior in test versus live. In test
     * mode it returns the HTML processing page, in live
     * mode it returns a payment identifier.
     *
     * @throws Exception
     */
    public function generatePaymentIdentifier($pfParameters)
    {
        ray("generatePaymentIdentifier() URL: $this->url");

        $response = Http::withOptions(["verify" => false])
        ->post($this->url, $pfParameters);

        if (! isset($response['uuid'])) {
            ray("generatePaymentIdentifier failed as response didn't have UUID. Output request parameters and response body(): ", $pfParameters);

            ray($response->body());

            $html = $response->body();

            ray(strlen($html));

            if ($result = $this->extractErrorMessageFromHtml($html)) {
                throw new Exception($result);
            }

            throw new Exception("generatePaymentIdentifier failed as response didn't have UUID. Output request parameters and response body(): ");
        }

        ray("generatePaymentIdentifier result: $response[uuid]");

        return $response['uuid'];
    }

    public function generateApiSignature($pfData, $passPhrase = null): string
    {
        if ($passPhrase !== null) {
            $pfData['passphrase'] = $passPhrase;
        }

        // Sort the array alphabetically by key
        ksort($pfData);

        $pfParamString = http_build_query($pfData);

        return md5($pfParamString);
    }

    private function headers(): array
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

    public function updateCardCallbackUrl()
    {
        if ($this->test_mode == 'true') {
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

    public function url(): string
    {
        return $this->url;
    }

    /**
     * To ensure our tests are working, we do a dependency injection test and simply return true
     */
    public function di(): bool
    {
        return true;
    }

    public function ping(): string
    {
        return Http::withHeaders($this->headers())
            ->get('https://api.payfast.co.za/ping')
            ->body();
    }
}
