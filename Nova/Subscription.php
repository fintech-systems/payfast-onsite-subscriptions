<?php

namespace App\Nova;

use App\Nova\Actions\ChangeEndsAt;
use App\Nova\Actions\CancelSubscription;
use App\Nova\Actions\FetchSubscriptionInformation;
use App\Nova\Actions\OverridePayFastStatus;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Stack;
use Laravel\Nova\Http\Requests\NovaRequest;

class Subscription extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \FintechSystems\PayFast\Subscription::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The grouping for the resources navigation menu
     */
    public static $group = "Subscriptions";

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'billable_id',
        'payfast_token',
        'merchant_payment_id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            Text::make('Subscriber', function() {
                $url = config('nova.path') . '/resources/users/' . $this->billable->id;
                $url = "<a href='$url' class='no-underline dim text-primary font-bold'>{$this->billable->email}</a>";
                return $url;
            })->asHtml(),

            Text::make('Merchant Payment ID'),

            Text::make('PayFast Token'),

            Text::make('PayFast Status'),

            Date::make('Next Bill At'),

            Date::make('Ends At'),

            DateTime::make('Cancelled At'),

            DateTime::make('Trial Ends At'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            new ChangeEndsAt,
            new FetchSubscriptionInformation,
            new CancelSubscription,
            new OverridePayFastStatus,
        ];
    }
}
