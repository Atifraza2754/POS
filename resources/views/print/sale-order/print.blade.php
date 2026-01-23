<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoiceData['name'] ?? 'Invoice' }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        body {
            width: 80mm;
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 5px;
            direction: ltr;
            color: #000;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        hr {
            border: none;
            border-top: 1px solid #000;
            margin: 4px 0;
        }

        table {
            width: 98%;
            border-collapse: collapse;
        }

        th, td {
            font-size: 11px;
            padding: 3px 2px;
            border: 1px solid #000;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        td {
            vertical-align: top;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .no-border td {
            border: none;
            padding: 2px 0;
        }

        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    {{-- ====== HEADER ====== --}}
    <div class="center bold" style="font-size: 13px;">{{ app('company')['name'] }}</div>
    <div class="center" style="font-size: 10px;">{{ app('company')['address'] }}</div>
    <div class="center" style="font-size: 10px;">Shahid Ali:  03453737702 / 03323737702</div>

    <hr>

    <table class="no-border">
        <tr>
            <td><b>{{ __('app.bill') }}:</b> {{ $order->order_code }}</td>
            <td class="text-end"><b>{{ __('order.date') }}:</b> {{ $order->formatted_order_date }}</td>
        </tr>
        <tr>
            <td><b>{{ __('app.time') }}:</b> {{ $order->format_created_time }}</td>
            <td class="text-end"><b>Customer:</b> {{ $order->party->first_name }} {{ $order->party->last_name }}</td>
            <td class="text-end"><b>Created By:</b> {{ $order->user->first_name }} {{ $order->user->last_name }}</td>
        </tr>
    </table>

    <hr>

    {{-- ====== ITEMS TABLE ====== --}}
    <table>
        <thead>
            <tr class="bold">
                <th>#</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Dis</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach($order->itemTransaction as $transaction)
                <tr>
                    <td class="text-center">{{ $i++ }}</td>
                    <td>{{ Str::limit($transaction->item->name, 22) }}</td>
                    <td class="text-center">{{ $formatNumber->formatQuantity($transaction->quantity) }}</td>
                    <td class="text-end">{{ $formatNumber->formatWithPrecision($transaction->unit_price) }}</td>
                    <td class="text-end">{{ $formatNumber->formatWithPrecision($order->discount ?? 0) }}</td>
                    <td class="text-end">{{ $formatNumber->formatWithPrecision($transaction->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    {{-- ====== TOTALS SECTION ====== --}}
    <table class="no-border">
        <tr>
            <td><b>Bill Amount</b></td>
            <td class="text-end">{{ $formatNumber->formatWithPrecision($order->itemTransaction->sum('total')) }}</td>
        </tr>
        <tr>
            <td><b>Discount</b></td>
            <td class="text-end">{{ $formatNumber->formatWithPrecision($order->discount) }}</td>
        </tr>
       
        <tr>
            <td><b>Total Balance</b></td>
            <td class="text-end">{{ $formatNumber->formatWithPrecision($order->grand_total) }}</td>
        </tr>
        <tr>
            <td><b>Cash Paid</b></td>
            <td class="text-end">{{ $formatNumber->formatWithPrecision($order->paid_amount) }}</td>
        </tr>
        <tr>
            <td><b>Current Balance</b></td>
            <td class="text-end bold">{{ $formatNumber->formatWithPrecision($order->grand_total - $order->paid_amount) }}</td>
        </tr>
       
    </table>

    <div class="footer">
        Software By: <b>IEB</b> &nbsp; Contact: <b>03363033782 / 03133228979</b>
    </div>
</body>
</html>
