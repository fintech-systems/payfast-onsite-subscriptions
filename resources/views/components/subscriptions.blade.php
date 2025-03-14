<x-action-section>
    <x-slot name="title">
        {{ __('Subscription Information') }}
    </x-slot>

    <x-slot name="description">
        {{ __('View/Update subscription information.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600">

            <!-- Check if the current logged in user is subscribed to a plan -->
            @if (!$user->subscribed('default'))
                {{-- Trial --}}
                @if ($user->onGenericTrial())
                    <h3 class="text-lg font-medium text-gray-900">
                        You are currently on trial till the {{ $user->trialEndsAt()->format('jS \o\f F Y') }}
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        <p>
                            If you subscribe now next payment will be due on the
                            {{ $this->afterTrialNextDueDate }}
                        </p>
                    </div>
                @elseif($user->hasExpiredGenericTrial())
                {{-- Expired Generic Trial --}}
                    <h3 class="text-lg font-medium text-gray-900">
                        Your trial has expired.
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        <p>
                            {{ __('Please select from our plans below:') }}
                        </p>
                    </div>
                @else
                {{-- No Subscription --}}
                <h3 class="text-lg font-medium text-gray-900">
                        You are not currently subscribed to a plan.
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        <p>
                            {{ __('Please select from our plans below:') }}
                        </p>
                    </div>
                @endif
            @else
                @if ($user->subscription('default')->onGracePeriod())
                {{-- Grace period --}}
                    <h3 class="text-lg font-medium text-gray-900">
                        Your subscription was cancelled
                        {{ $user->subscription('default')->cancelled_at->format('j F Y \a\t H:i:s') }}.
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        @if (\Carbon\Carbon::now()->diffInDays(
                            $user->subscriptions()->active()->first()->ends_at->format('Y-m-d'),
                        ) != 0)
                            <p>
                                There are
                                {{ (int) \Carbon\Carbon::now()->diffInDays($user->subscription('default')->ends_at) }}
                                days left of your subscription and the last day is the
                                {{ $user->subscription('default')->ends_at->format('jS \o\f F Y') }}.
                            </p>
                        @else
                            <p>
                                Today is the last day of your subscription.
                            </p>
                        @endif
                    </div>
                @else
                    {{-- Subscribed --}}
                    <h3 class="text-lg font-medium text-gray-900">
                        You are subscribed to the
                        {{ 
                            config('payfast.billables.user.plans')
                            [explode('|', $user->subscription('default')->plan)
                            [0]]
                            ['name'] 
                        }} 
                        {{ 
                            explode('|', $user->subscription('default')->plan)[1] 
                        }} plan.
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        <p>
                            The next payment will go off on the
                                {{ $user->subscription('default')->next_bill_at->format('jS \o\f F Y') }}.
                        </p>
                    </div>
                @endif
            @endif
        </div>

        <!-- Subscription Action Buttons -->
        <div class="mt-5">
            {{-- @if ($user->subscribed('default') && !$user->onGenericTrial() && !$user->subscription('default')->onGracePeriod())                             --}}
            @if ($user->subscribed('default')  && !$user->subscription('default')->onGracePeriod())
                <x-secondary-button style="color: blue;" wire:click="updateCard">
                    {{ __('Update Card Information') }}
                </x-secondary-button>

                <x-secondary-button style="color: red;" wire:click="confirmCancelSubscription"
                    wire:loading.attr="disabled">
                    {{ __('Cancel Subscription') }}
                </x-secondary-button>
            @else
                <div class="flex">
                    <select wire:model="plan" name="plan"
                        class="mt-1 block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach (config('payfast.billables.user.plans') as $index => $plan)
                            <option value="{{ $index }}|monthly">{{ $plan['name'] }} Monthly - {{ config('payfast.billables.user.currency_prefix') }}{{ number_format($plan['monthly']['recurring_amount'] / 100, 2) }}</option>
                            <option value="{{ $index }}|yearly">{{ $plan['name'] }} Yearly - {{ config('payfast.billables.user.currency_prefix') }}{{ number_format($plan['yearly']['recurring_amount'] / 100, 2) }}</option>
                        @endforeach
                    </select>

                    {{-- This is the main button that gets clicked to subscribe to a plan. It calls displayCreateSubscription(). --}}
                    <x-secondary-button class="ml-2 align-middle h-9 mt-2" style="color: green;"
                        wire:click="displayCreateSubscription">
                        @if ($user->subscribed('default') && $user->subscription('default')->onGracePeriod())
                            {{ __('Resubscribe') }}
                        @else
                            {{ __('Subscribe') }}
                        @endif
                    </x-secondary-button>

                    <div wire:loading class="ml-2 align-middle mt-3">
                        Please wait...
                    </div>

                </div>
            @endif
        </div>
        <!-- End Subscription Action Buttons -->

        <!-- Launch PayFast Subscription Modal -->
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('launchPayfast', ({ identifier }) => {
                    console.log('Launching PayFast onsite payment modal');
                    console.log('identifier: ' + identifier)
                    window.payfast_do_onsite_payment({
                        uuid: identifier
                    });
                    window.addEventListener("message", refreshComponent);
                });
            });
        </script>

        @push('payfast-event-listener')
            <script>
                const refreshComponent = () => {
                    console.log('Refreshing subscription status by emitting a billingUpdated event')

                    window.Livewire.dispatch('billingUpdated')
                }
            </script>
        @endpush

        <!-- Start Cancel Subscription Confirmation Modal -->
        <x-dialog-modal wire:model="confirmingCancelSubscription">

            <x-slot name="title">
                {{ __('Cancel Subscription') }}
            </x-slot>

            <x-slot name="content">
                {{ __('Are you sure you want to cancel your subscription?') }}
            </x-slot>

            <x-slot name="footer">
                <div wire:loading class="mr-2 align-middle mt-3">
                    Please wait...
                </div>

                <x-button wire:click="$toggle('confirmingCancelSubscription')"
                    wire:loading.attr="disabled">
                    {{ __('Keep Subscription') }}
                </x-button>

                <x-danger-button class="ml-2" wire:click="cancelSubscription"
                    wire:loading.attr="disabled">
                    {{ __('Cancel Subscription') }}
                </x-danger-button>

            </x-slot>

        </x-dialog-modal>
        <!-- End Cancel Subscription Confirmation Modal -->

    </x-slot>
</x-action-section>
