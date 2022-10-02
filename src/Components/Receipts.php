<?php

namespace FintechSystems\PayFast\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Receipts extends Component
{
    public $user;

    public $receipts;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount()
    {
        $this->user = Auth::user();
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $this->receipts = $this->user->receipts;

        return view('vendor.payfast.components.receipts');
    }
}
