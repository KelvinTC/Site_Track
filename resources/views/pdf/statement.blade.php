<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement - {{ $statement['customer']['name'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #374151;
            background: #fff;
        }
        .statement {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .company-logo {
            max-width: 120px;
            max-height: 60px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e1b4b;
            margin-bottom: 8px;
        }
        .company-details,
        .customer-details {
            color: #6b7280;
            font-size: 11px;
            line-height: 1.6;
        }
        .statement-title {
            font-size: 32px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 8px;
        }
        .statement-period {
            font-size: 12px;
            color: #6b7280;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .customer {
            margin-bottom: 32px;
        }
        .customer-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }
        .transactions {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .transactions th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }
        .transactions td {
            padding: 9px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .transactions .num {
            text-align: right;
            white-space: nowrap;
        }
        .transactions .balance-row td {
            background: #f9fafb;
            font-weight: bold;
            color: #111827;
        }
        .muted {
            font-size: 10px;
            color: #9ca3af;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-label {
            display: table-cell;
            color: #6b7280;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: normal;
            color: #111827;
        }
        .totals-row.total {
            border-top: 2px solid #e5e7eb;
            border-bottom: none;
            padding: 12px 0;
        }
        .totals-row.total .totals-label {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }
        .totals-row.total .totals-value {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
        }
    </style>
</head>
<body>
    @php
        $customer = $statement['customer'];
        $money = fn ($amount) => ($amount < 0 ? '-$' : '$') . number_format(abs($amount), 2);
    @endphp
    <div class="statement">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" class="company-logo">
                @endif
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->city || $company->state){{ $company->city }}@if($company->city && $company->state), @endif{{ $company->state }}<br>@endif
                    @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                    @if($company->email){{ $company->email }}<br>@endif
                    @if($company->vat_registered && $company->vat_number)VAT No: {{ $company->vat_number }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="statement-title">STATEMENT</div>
                <div class="statement-period">
                    {{ \Carbon\Carbon::parse($statement['period']['from_date'])->format('d M Y') }}
                    &ndash;
                    {{ \Carbon\Carbon::parse($statement['period']['to_date'])->format('d M Y') }}
                </div>
            </div>
        </div>

        <!-- Customer -->
        <div class="customer">
            <div class="section-title">Statement For</div>
            <div class="customer-name">{{ $customer['name'] }}</div>
            <div class="customer-details">
                @if($customer['address']){{ $customer['address'] }}<br>@endif
                @if($customer['city'] || $customer['state']){{ $customer['city'] }}@if($customer['city'] && $customer['state']), @endif{{ $customer['state'] }}<br>@endif
                @if($customer['phone'])Tel: {{ $customer['phone'] }}<br>@endif
                @if($customer['email']){{ $customer['email'] }}<br>@endif
                @if($customer['vat_number'])VAT No: {{ $customer['vat_number'] }}@endif
            </div>
        </div>

        <!-- Transactions -->
        <table class="transactions">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th class="num">Debit</th>
                    <th class="num">Credit</th>
                    <th class="num">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="balance-row">
                    <td>{{ \Carbon\Carbon::parse($statement['period']['from_date'])->format('d M Y') }}</td>
                    <td></td>
                    <td>Opening Balance</td>
                    <td></td>
                    <td></td>
                    <td class="num">{{ $money($statement['opening_balance']) }}</td>
                </tr>
                @forelse($statement['transactions'] as $transaction)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                        <td>{{ $transaction['ref'] }}</td>
                        <td>
                            {{ $transaction['description'] }}
                            @if(!empty($transaction['invoice_number']))
                                <div class="muted">For {{ $transaction['invoice_number'] }}</div>
                            @endif
                        </td>
                        <td class="num">{{ $transaction['debit'] ? $money($transaction['debit']) : '' }}</td>
                        <td class="num">{{ $transaction['credit'] ? $money($transaction['credit']) : '' }}</td>
                        <td class="num">{{ $money($transaction['balance']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No transactions in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Opening Balance</span>
                <span class="totals-value">{{ $money($statement['opening_balance']) }}</span>
            </div>
            <div class="totals-row">
                <span class="totals-label">Total Invoiced</span>
                <span class="totals-value">{{ $money($statement['total_invoiced']) }}</span>
            </div>
            <div class="totals-row">
                <span class="totals-label">Total Paid</span>
                <span class="totals-value">{{ $money($statement['total_paid']) }}</span>
            </div>
            <div class="totals-row total">
                <span class="totals-label">Balance Due</span>
                <span class="totals-value">{{ $money($statement['closing_balance']) }}</span>
            </div>
        </div>
    </div>
</body>
</html>
