<?php

namespace Tests\Fixtures;

use FintechSystems\PayFast\Billable;
use Illuminate\Foundation\Auth\User as Model;

class User extends Model
{
    use Billable;

    protected $guarded = [];
}
