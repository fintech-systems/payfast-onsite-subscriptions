<x-jet-action-section>
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
                            If you subscribe now the first payment is due on the
                            {{ $user->trialEndsAt()->addDay()->format('jS \o\f F Y') }}.
                        </p>
                    </div>
                @else
                {{-- No Subscription --}}
                <h3 class="text-lg font-medium text-gray-900">
                        You are not currently subscribed to a plan.
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        <p>
                            {{ __('Please select from our list of plans below') }}
                        </p>
                    </div>
                @endif
            @else
                @if ($user->subscription('default')->onGracePeriod())
                {{-- Grace period --}}
                    <h3 class="text-lg font-medium text-gray-900">
                        Your subscription was cancelled on the 
                        {{ $user->subscription('default')->cancelled_at->format('jS \o\f F Y \a\t H:i:s') }}.
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        @if (\Carbon\Carbon::now()->diffInDays(
                            $user->subscriptions()->active()->first()->ends_at->format('Y-m-d'),
                        ) != 0)
                            <p>
                                There are
                                {{ \Carbon\Carbon::now()->diffInDays($user->subscription('default')->ends_at) }}
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
                            {{ config('payfast.plans')[$user->subscription('default')->plan_id]['name'] }} plan.
                    </h3>
                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        <p>
                        @if($user->subscription('default')->next_bill_at) 
                            The next payment is due on the                            
                                {{ $user->subscription('default')->next_bill_at->format('jS \o\f F Y') }}.                            
                        @else
                            Cannot determine next_bill_at
                        @endif
                        </p>
                    </div>
                @endif        
            @endif
        </div>

        <!-- Subscription Action Buttons -->
        <div class="mt-5">
            @if ($user->subscribed('default') && !$user->onGenericTrial() && !$user->subscription('default')->onGracePeriod())
                <x-jet-secondary-button style="color: red;" wire:click="confirmCancelSubscription"
                    wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </x-jet-secondary-button>

                <x-jet-secondary-button style="color: blue;" wire:click="updateCard">
                    {{ __('Update Card') }}
                </x-jet-secondary-button>
            @else
                <div class="flex">
                    <select wire:model="plan" name="plan"
                        class="mt-1 block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        @foreach (config('payfast.plans') as $key => $value)
                            <option value="{{ $key }}">{{ $value['name'] }}</option>
                        @endforeach
                    </select>

                    <x-jet-secondary-button class="ml-2 align-middle h-9 mt-2" style="color: green;"
                        wire:click="displayCreateSubscription">
                        @if ($user->subscribed('default') && $user->subscription('default')->onGracePeriod())
                            {{ __('Resubscribe') }}
                        @else
                            {{ __('Subscribe') }}
                        @endif
                    </x-jet-secondary-button>

                    <div wire:loading class="ml-2 align-middle mt-3">
                        Please wait...
                    </div>

                </div>
            @endif
        </div>
        <!-- End Subscription Action Buttons -->

        <!-- Launch PayFast Subscription Modal -->
        @if ($displayingCreateSubscription)
            <script>
                console.log('Launching PayFast onsite payment modal')

                window.payfast_do_onsite_payment({
                    "uuid": "{{ $identifier }}"
                })

                console.log("Adding an event listener to 'message' for when it closes")

                window.addEventListener("message", refreshComponent);
            </script>
        @endif

        @push('payfast-event-listener')
            <script>
                const refreshComponent = () => {
                    console.log('Refreshing subscription status by emitting a billingUpdated event')

                    Livewire.emit('billingUpdated')
                }
            </script>
        @endpush

        <!-- Start Cancel Subscription Confirmation Modal -->
        <x-jet-dialog-modal wire:model="confirmingCancelSubscription">

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

                <x-jet-secondary-button wire:click="$toggle('confirmingCancelSubscription')"
                    wire:loading.attr="disabled">
                    {{ __('Nevermind') }}
                </x-jet-secondary-button>

                <x-jet-danger-button class="ml-2" wire:click="cancelSubscription"
                    wire:loading.attr="disabled">
                    {{ __('Cancel Subscription') }}
                </x-jet-danger-button>
            </x-slot>

        </x-jet-dialog-modal>
        <!-- End Cancel Subscription Confirmation Modal -->

    </x-slot>
</x-jet-action-section>
