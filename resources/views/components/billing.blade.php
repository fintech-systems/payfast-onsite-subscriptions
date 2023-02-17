<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Billing
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">

            <!-- Subscriptions -->
            <div class="mt-10 sm:mt-0">
                @livewire('subscriptions', ['mergeFields' => [
                        'name_first' => Auth()->user()->first_name ?? Auth()->user()->name,
                        'name_last' => Auth()->user()->last_name ?? Auth()->user()->name,
                        'item_description' => config('app.name') . " Subscription",
                    ]] )
            </div>
            <!-- End Subscriptions -->

            <x-section-border />

            <!-- Receipts -->
            <div class="mt-10 sm:mt-0">
                @livewire('receipts')
            </div>
            <!-- End Receipts -->

        </div>
    </div>
</div>
