<!DOCTYPE html>
<html>
<head>
    <title>Payment History</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background: #f2f2f2; }
        .header { margin-bottom: 20px; }
    </style>
</head>
<body>

<h2>Payment History</h2>

<div class="header">
    <p><strong>Party Name:</strong> {{ $invoice->party->first_name }} {{ $invoice->party->last_name }}</p>
    <p><strong>Code:</strong> {{ $invoice->purchase_code }}</p>
    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</p>
</div>

{{-- <table>
    <thead>
        <tr>
            <th>Transaction Date</th>
            <th>Receipt No.</th>
            <th>Payment Type</th>
            <th>Amount</th>
        </tr>
    </thead>

    <tbody>
        @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->transaction_date }}</td>
                <td>{{ $payment->reference_no }}</td>
                <td>{{ $payment->payment_type }}</td>
                <td>{{ $payment->amount }}</td>
            </tr>
        @endforeach

        <tr>
            <th colspan="3" style="text-align:right;">Total Paid:</th>
            <th>{{ number_format($totalAmount, 2) }}</th>
        </tr>
    </tbody>
</table> --}}

<table width="100%" border="1" cellspacing="0" cellpadding="6" style="border-collapse: collapse; margin-top:20px;">
    <thead style="background:#f2f2f2;">
        <tr>
            <th style="text-align:center;">Transaction Date</th>
            <th style="text-align:center;">Receipt No.</th>
            <th style="text-align:center;">Payment Type</th>
            <th style="text-align:center;">Amount</th>
        </tr>
    </thead>

    <tbody>
        @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->transaction_date }}</td>
                <td>{{ $payment->reference_no }}</td>
                <td>{{ $payment->paymentType->name ?? '' }}</td>
                <td style="text-align:right;">{{ number_format($payment->amount, 2) }}</td>
            </tr>
        @endforeach

        <tr style="background:#f9f9f9; font-weight:bold;">
            <td colspan="3" style="text-align:right;">Total Paid:</td>
            <td style="text-align:right;">{{ number_format($totalAmount, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- SUMMARY TABLE --}}
<table width="40%" style="margin-top:30px; border-collapse: collapse;" border="1" cellspacing="0" cellpadding="6">
    <tr style="background:#f2f2f2; font-weight:bold;">
        <th style="text-align:left;">Description</th>
        <th style="text-align:right;">Amount</th>
    </tr>

    <tr>
        <td>Total Invoice Amount:</td>
        <td style="text-align:right;">{{ number_format($grandTotal, 2) }}</td>
    </tr>

    <tr>
        <td>Total Paid Amount:</td>
        <td style="text-align:right;">{{ number_format($paidTotal, 2) }}</td>
    </tr>

    <tr>
        <td>Balance Amount:</td>
        <td style="text-align:right;">{{ number_format($balance, 2) }}</td>
    </tr>
</table>


</body>
</html>
