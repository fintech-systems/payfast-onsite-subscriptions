<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\ActionFields;
use FintechSystems\Payfast\Subscription;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Nova\Actions\DestructiveAction;
use Laravel\Nova\Http\Requests\NovaRequest;

class OverridePayFastStatus extends DestructiveAction
{
    use InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * TODO Beware duplicate code also exists in Subscription.php
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $subscription) {
            ray($fields);

            $subscription->payfast_status = $fields->payfast_status;

            if ($subscription->payfast_status == Subscription::STATUS_DELETED && !$subscription->cancelled_at) {
                $message = ("Subscription status at PayFast is cancelled but no cancelled at date exists. Saving now() as cancelled and ended at as dates.");

                Log::warning($message);

                ray($message)->orange();

                $subscription->cancelled_at = now();

                $subscription->ends_at = now();
            }

            $subscription->save();
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Select::make('Payfast Status')
                ->options(Subscription::uiOptions())
                ->displayUsingLabels()
        ];
    }
}
