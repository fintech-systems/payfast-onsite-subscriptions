<?php

namespace FintechSystems\Payfast;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public static function generate()
    {
        $newRecord = self::create([
            'billable_id' => Auth::user()->getKey(),
            'billable_type' => Auth::user()->getMorphClass(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ]);

        return $newRecord->id . '-' . Carbon::now()->format('YmdHis');
    }
}
