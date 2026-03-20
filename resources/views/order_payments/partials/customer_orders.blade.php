{{-- @if($orders->isEmpty())
    <p>No orders found for this customer.</p>
@else
    <ul class="list-group mb-2">
        @foreach($orders as $order)
            <li class="list-group-item">
                Order #{{ $order->id }} - {{ $order->created_at->format('Y-m-d') }}
            </li>
        @endforeach
    </ul>

    <p><strong>Total Amount:</strong> {{ number_format($orders->sum('grand_total'), 2) }}</p>
    <p><strong>Paid Amount:</strong> {{ number_format($orders->sum('paid_amount'), 2) }}</p>
    <p><strong>Remaining Amount:</strong> {{ number_format($orders->sum('grand_total') - $orders->sum('paid_amount'), 2) }}</p>

@endif --}}


{{-- @if($orders->isEmpty())
    <p>No orders found for this customer.</p>
@else
    <ul class="list-group mb-2">
        @foreach($orders as $order)
        
            <li class="list-group-item">
                Order #{{ $order->id }} - {{ $order->created_at->format('Y-m-d') }}  
                <span class="float-end">Amount: {{ number_format($order->grand_total, 2) }}</span>
            </li>
        @endforeach
    </ul>

    <div class="mt-3">
        <p><strong>Total Orders:</strong> {{ number_format($totalOrders, 2) }}</p>
        <p><strong>Total Paid:</strong> {{ number_format($totalPaid, 2) }}</p>
        <p><strong>Remaining:</strong> {{ number_format($remaining, 2) }}</p>
    </div>
@endif --}}


@if($orders->isEmpty() && (!isset($returns) || $returns->isEmpty()))
    <p>No orders or returns found for this customer.</p>
@else
    <ul class="list-group mb-2">
        @foreach($orders as $order)
            <li class="list-group-item">
                Order #{{ $order->id }} - {{ $order->created_at->format('Y-m-d') }}  
                <span class="float-end">Amount: {{ number_format($order->grand_total, 2) }}</span>
            </li>
        @endforeach

        @if(isset($returns) && !$returns->isEmpty())
            @foreach($returns as $return)
                <li class="list-group-item list-group-item-danger">
                    Return #{{ $return->return_code }} - {{ $return->created_at->format('Y-m-d') }}  
                    <span class="badge bg-danger">Returned</span>
                    <span class="float-end">Amount: -{{ number_format($return->grand_total, 2) }}</span>
                </li>
            @endforeach
        @endif
    </ul>

    <div class="mt-3">
        <p><strong>Total Orders:</strong> {{ number_format($totalOrders, 2) }}</p>
        @if(isset($totalReturns) && $totalReturns > 0)
            <p class="text-danger"><strong>Total Returns (Credit):</strong> -{{ number_format($totalReturns, 2) }}</p>
        @endif
        <p><strong>Total Paid:</strong> {{ number_format($totalPaid, 2) }}</p>
        <p><strong>Remaining:</strong> {{ number_format($remaining, 2) }}</p>
    </div>
@endif

