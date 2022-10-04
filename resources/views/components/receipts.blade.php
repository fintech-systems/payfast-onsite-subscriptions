<x-jet-action-section>

    <x-slot name="title">
        {{ __('Receipts') }}
    </x-slot>

    <x-slot name="description">
        {{ __('A list of transactions and receipts.') }}
    </x-slot>

    <x-slot name="content">    
        <div class="text-gray-600">
            <table width="100%" class="table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <td nowrap><strong>ID</strong></th>
                        <td><strong>Item</strong></td>
                        <td style="text-align:right"><strong>Amount</strong></th>
                        <td style="padding-left:10px"><strong>Status</strong></th>                        
                        <td style="text-align:center"><strong>Billing Date</strong></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipts as $receipt)                        
                        <tr class="odd:bg-white even:bg-gray-50">
                            <td>{{ $receipt->payfast_payment_id }}</td>
                            <td>{{ $receipt->item_name }}</td>
                            <td style="text-align:right">R {{ $receipt->amount_gross }}</td>
                            <td style="padding-left:10px">{{ $receipt->payment_status }}</td>                    
                            <td nowrap>{{ $receipt->billing_date->format('Y-m-d') ?? 'n/a' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>                                        
    </x-slot>

</x-jet-action-section>