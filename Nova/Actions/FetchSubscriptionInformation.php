<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\ActionFields;
use FintechSystems\PayFast\Subscription;
use Illuminate\Queue\InteractsWithQueue;
use FintechSystems\PayFast\Facades\PayFast;
use Illuminate\Contracts\Queue\ShouldQueue;

class FetchSubscriptionInformation extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        Log::debug('Looping through subscriptions and fetching information...');

        foreach($models as $subscription) {            
            $result = PayFast::fetchSubscription($subscription->payfast_token);

            Log::debug($result);

            $subscription->updatePayFastSubscription($result);
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
