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
    private string $url;
    private array $urlCollection;
    private string $test_mode;
    private string $returnUrl;
    private string $cancelUrl;
    private string $notifyUrl;

    public function __construct($client)
    {
        $this->test_mode = $client['test_mode'] ?? false;

        // TODO There is bad coding with overlap of return URLs and how prepend
        // is used. This needs to be refactored.
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
            $prependUrl = config('payfast.callback_url');
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

        $append = $this->test_mode ? 'testing=true' : "";

        $response = Http::withHeaders($this->headers())
            ->put("https://api.payfast.co.za/subscriptions/$payfast_token/cancel?$append")
            ->json();

        ray($response['data']['message'])->green();

        return $response;
    }

    /**
     * Create a "custom" payment link. This is in contrast to creating a "subscription" payment.
     * This is the simplest form of payment and is used for once-off payments. For complete
     * documentation go here: https://developers.payfast.co.za/docs#quickstart
     *
     * To develop this method, we tried to stick to the order carefully as per the Payfast
     * documentation. The method ends up outputting a "Pay" link.
     *
     */
    public function createCustomPayment($amount, $item, $user = [])
    {
        $data = [
            'merchant_id' => $this->merchantId(), // Required
            'merchant_key' => $this->merchantKey(), // Required

            'name_first' => $user['first_name'] ?? '', // Optional
            'name_last' => $user['last_name'] ?? '', // Optional
            'email_address' => $user['email'] ?? '', // Required
            'cell_number' => $user['mobile_phone_number'] ?? '', // Optional

            'm_payment_id' => Order::generate(), // Optional
            'amount' => $amount, // Required
            'item_name' => $item['name'], // Required
            'item_description' => $item['description'] ?? '', // Optional
        ];

        $data = array_merge($data, $this->urlCollection);

        //        ray($data)->pause();

        //        if ($mergeFields) {
        //            $data = array_merge($data, $mergeFields);
        //        }

        $message = "Payfast Create Custom Payment was invoked with these merged values and will now wait for user input:";

        $this->debug($message, 'createCustomPayment');
        $this->debug($data, 'notice');

        $signature = Payfast::generateApiSignature($data, $this->passphrase());

        $pfData = array_merge($data, ["signature" => $signature]);

        // If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
        //        $testingMode = true;
        $pfHost = $this->test_mode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $htmlForm = '<form action="https://'.$pfHost.'/eng/process" method="post">';
        foreach ($data as $name => $value) {
            $htmlForm .= '<input name="'.$name.'" type="hidden" value=\''.$value.'\' />';
        }
        $htmlForm .= '<input type="submit" value="Pay Now" /></form>';
        echo $htmlForm;

        //        return $pfData;

        //        $paymentIdentifier = $this->generatePaymentIdentifier($pfData);


    }

    /**
     * Create a new subscription using Payfast Onsite Payments. One of the most
     * important aspects is ensuring that the correct billing date is sent
     * with the order, and also on renewals the initial amount is zero.
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
    public function createOnsitePayment($plan, $billingDate = null, array $mergeFields = [], int $cycles = 0)
    {
        ray("createOnsitePayment is called with this plan: $plan");

        $planDetail = $this->getPlanDetail($plan);

        ray("the plan detail is: ", $planDetail);

        $data = [
            'merchant_id' => $this->merchantId(),
            'merchant_key' => $this->merchantKey(),
            'subscription_type' => 1, // required for subscriptions - sets type to a subscription
            'm_payment_id' => Order::generate(),
            'amount' => $planDetail['initial_amount'] / 100,
            'recurring_amount' => $planDetail['recurring_amount'] / 100,
            'billing_date' => $billingDate,
            'frequency' => $planDetail['frequency'],
            'cycles' => $cycles,
            'custom_str1' => Auth::user()->getMorphClass(),
            'custom_int1' => Auth::user()->getKey(),
            'custom_str2' => $plan,
            'item_name' => $planDetail['item_name'],
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
        Log::info("extractErrorMessageFromHtml is called with this html: $html");

        $doc = new DOMDocument();

        // Suppress warnings about duplicate IDs and other HTML parsing issues
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_NOERROR);
        libxml_clear_errors();

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
    public function getPlanDetail($plan): array
    {
        ray("getPlanDetail is called with this plan: $plan");
        list($planId, $frequency) = explode('|', $plan);

        ray($planId);

        $plan = config('payfast.billables.user.plans')[$planId];

        $planDetail = [];
        $recurringType = "";

        if ($frequency == 'monthly') {
            $recurringType = "Monthly";
            $planDetail['frequency'] = 3;
            $planDetail['initial_amount'] = $plan['monthly']['setup_amount'] ?? 0;
            $planDetail['recurring_amount'] = $plan['monthly']['recurring_amount'];
            $planDetail['frequencyName'] = "Monthly";
        }

        if ($frequency == 'yearly') {
            $recurringType = "Yearly";
            $planDetail['frequency'] = 6;
            $planDetail['initial_amount'] = $plan['yearly']['setup_amount'] ?? 0;
            $planDetail['recurring_amount'] = $plan['yearly']['recurring_amount'];
            $planDetail['frequencyName'] = "Yearly";
        }

        $planDetail['item_name'] = $plan['name'] . " $recurringType";

        $planDetail['id'] = $planId;

        ray("Returning planDetail:", $planDetail);

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
