<x-jet-action-section>
    <x-slot name="title">
        {{ __('Receipt Information') }}
    </x-slot>

    <x-slot name="description">
        {{ __('A list of transaction receipts.') }}
    </x-slot>

    <x-slot name="content">
    
        <div class="text-gray-600">
            <table width="100%">
                <thead class="bg-gray-50">
                    <tr>
                        <td nowrap><strong>ID</strong></th>
                        <td><strong>Item</strong></td>
                        <td style="text-align:right"><strong>Amount</strong></th>
                        <td style="padding-left:10px"><strong>Status</strong></th>                        
                        <td style="text-align:center"><strong>Date</strong></th>
                    </tr>
                </thead>
                @foreach($receipts as $receipt)
                <tr>
                    <td>{{ $receipt->payfast_payment_id }}</td>
                    <td nowrap>{{ $receipt->item_name }}</td>
                    <td style="text-align:right">R {{ $receipt->amount_gross }}</td>
                    <td style="padding-left:10px">{{ $receipt->payment_status }}</td>                    
                    <td nowrap>{{ $receipt->paid_at }}</td>
                </tr>
                @endforeach
            </table>
        </div>
                                        
    </x-slot>
</x-jet-action-section>
