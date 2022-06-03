<?php

namespace FintechSystems\PayFast;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use FintechSystems\PayFast\Contracts\BillingProvider;

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

    private function generateApiSignature($pfData, $passPhrase = null)
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
