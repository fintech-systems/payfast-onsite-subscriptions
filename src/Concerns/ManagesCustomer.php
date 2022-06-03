<?php

namespace FintechSystems\PayFast\Concerns;

// use Laravel\Paddle\Cashier;
use FintechSystems\PayFast\Customer;

trait ManagesCustomer
{
    /**
     * Create a customer record for the billable model.
     *
     * @param  array  $attributes
     * @return \Laravel\Paddle\Customer
     */
    public function createAsCustomer(array $attributes = [])
    {
        return $this->customer()->create($attributes);
    }

    /**
     * Get the customer related to the billable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function customer()
    {
        return $this->morphOne(Customer::class, 'billable');
    }

    /**
     * Get prices for a set of product ids for this billable model.
     *
     * @param  array|int  $products
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    // public function productPrices($products, array $options = [])
    // {
    //     $options = array_merge([
    //         'customer_country' => $this->paddleCountry(),
    //     ], $options);

    //     return Cashier::productPrices($products, $options);
    // }

    /**
     * Get the billable model's email address to associate with PayFast.
     *
     * @return string|null
     */
    public function payfastEmail()
    {
        return $this->email;
    }

    /**
     * Get the billable model's country to associate with Paddle.
     *
     * This needs to be a 2 letter code. See the link below for supported countries.
     *
     * @return string|null
     *
     * @link https://developer.paddle.com/reference/platform-parameters/supported-countries
     */
    public function paddleCountry()
    {
        //
    }

    /**
     * Get the billable model's postcode to associate with Paddle.
     *
     * See the link below for countries which require this.
     *
     * @return string|null
     *
     * @link https://developer.paddle.com/reference/platform-parameters/supported-countries#countries-requiring-postcode
     */
    public function paddlePostcode()
    {
        //
    }
}
