<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class Receipts extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \FintechSystems\PayFast\Receipt::class;

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
        'merchant_payment_id',
        'payfast_payment_id',
        'payfast_token',
        'order_id',
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

            Text::make('Merchant Payment ID')->readonly(),

            Number::make('PayFast Payment Id')->readonly(),

            Text::make('Payment Status')->readonly(),

            Text::make('Item Name')->readonly(),

            Text::make('Item Description')->readonly(),

            Number::make('Amount Gross')->readonly(),

            Number::make('Amount Fee')->readonly(),

            Number::make('Amount Net')->readonly(),

            Text::make('PayFast Token')->readonly(),

            Text::make('Order ID')->readonly(),

            DateTime::make('Paid At')->readonly(),
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
        return [];
    }
}
