<?php

namespace FintechSystems\Payfast\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Billing extends Component
{
    public function render(): View
    {
        return view('vendor.payfast.components.billing');
    }
}
