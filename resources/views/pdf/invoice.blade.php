<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
        .invoice {
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
        .company-details {
            color: #6b7280;
            font-size: 11px;
            line-height: 1.6;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 8px;
        }
        .invoice-number {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .invoice-date {
            font-size: 11px;
            color: #9ca3af;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 8px;
        }
        .status-unpaid {
            background: #fef3c7;
            color: #b45309;
        }
        .status-paid {
            background: #d1fae5;
            color: #047857;
        }
        .status-partial {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .status-overdue {
            background: #fee2e2;
            color: #b91c1c;
        }
        .billing-section {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .bill-to {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .customer-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }
        .customer-details {
            color: #6b7280;
            font-size: 11px;
            line-height: 1.6;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .item-description {
            font-weight: normal;
            color: #111827;
        }
        .item-details {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 40px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-row:last-child {
            border-bottom: none;
        }
        .totals-label {
            display: table-cell;
            color: #6b7280;
            font-size: 12px;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: normal;
            color: #111827;
        }
        .totals-row.total {
            padding: 12px 0;
            margin-top: 8px;
            border-top: 2px solid #e5e7eb;
            border-bottom: none;
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
        .notes-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .notes-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .notes-content {
            color: #374151;
            font-size: 11px;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 10px;
        }
        .payment-info {
            margin-bottom: 30px;
        }
        .payment-info-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .payment-details {
            display: table;
            width: 100%;
        }
        .payment-detail {
            display: table-row;
        }
        .payment-detail-label {
            display: table-cell;
            padding: 4px 0;
            color: #6b7280;
            font-size: 11px;
            width: 120px;
        }
        .payment-detail-value {
            display: table-cell;
            padding: 4px 0;
            color: #111827;
            font-size: 11px;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="invoice">
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
                    @if($company->vat_registered && $company->vat_number)VAT No: {{ $company->vat_number }}<br>@endif
                    @if($company->tax_id)Tax ID: {{ $company->tax_id }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div class="invoice-date">
                    Issued: {{ $invoice->issue_date->format('d M Y') }}<br>
                    @if($invoice->due_date)Due: {{ $invoice->due_date->format('d M Y') }}@endif
                </div>
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
        </div>

        <!-- Billing Section -->
        <div class="billing-section">
            <div class="bill-to">
                <div class="section-title">Bill To</div>
                <div class="customer-name">{{ $invoice->customer->name ?? 'N/A' }}</div>
                <div class="customer-details">
                    @if($invoice->customer)
                        @if($invoice->customer->address){{ $invoice->customer->address }}<br>@endif
                        @if($invoice->customer->city || $invoice->customer->state){{ $invoice->customer->city }}@if($invoice->customer->city && $invoice->customer->state), @endif{{ $invoice->customer->state }}<br>@endif
                        @if($invoice->customer->phone)Tel: {{ $invoice->customer->phone }}<br>@endif
                        @if($invoice->customer->email){{ $invoice->customer->email }}<br>@endif
                        @if($invoice->customer->vat_number)VAT No: {{ $invoice->customer->vat_number }}@endif
                    @endif
                </div>
            </div>
            @if($invoice->siteJob)
            <div class="bill-to">
                <div class="section-title">Job Reference</div>
                <div class="customer-name">{{ $invoice->siteJob->title }}</div>
                <div class="customer-details">
                    {{ $invoice->siteJob->job_number }}<br>
                    @if($invoice->siteJob->address){{ $invoice->siteJob->address }}@endif
                </div>
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%;">Qty</th>
                    <th style="width: 15%;">Unit Price</th>
                    <th style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-description">{{ $item->description }}</div>
                        @if($item->notes)<div class="item-details">{{ $item->notes }}</div>@endif
                    </td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>${{ number_format($item->total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #9ca3af; padding: 30px;">
                        No line items
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Subtotal</span>
                <span class="totals-value">${{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->vat_rate > 0)
            <div class="totals-row">
                <span class="totals-label">VAT ({{ number_format($invoice->vat_rate, 0) }}%)</span>
                <span class="totals-value">${{ number_format($invoice->vat_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span class="totals-label">Total</span>
                <span class="totals-value">${{ number_format($invoice->total, 2) }}</span>
            </div>
            @if($invoice->amount_paid > 0)
            <div class="totals-row">
                <span class="totals-label">Amount Paid</span>
                <span class="totals-value" style="color: #047857;">-${{ number_format($invoice->amount_paid, 2) }}</span>
            </div>
            <div class="totals-row">
                <span class="totals-label" style="font-weight: bold;">Balance Due</span>
                <span class="totals-value" style="font-weight: bold; color: #b45309;">${{ number_format($invoice->balance, 2) }}</span>
            </div>
            @endif
        </div>

        <!-- Notes -->
        @if($invoice->notes || $company->invoice_notes)
        <div class="notes-section">
            <div class="notes-title">Notes</div>
            <div class="notes-content">
                @if($invoice->notes){{ $invoice->notes }}@endif
                @if($invoice->notes && $company->invoice_notes)<br><br>@endif
                @if($company->invoice_notes){{ $company->invoice_notes }}@endif
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if($company->invoice_footer)
                {{ $company->invoice_footer }}
            @else
                Thank you for your business!
            @endif
            <br><br>
            {{ $company->name }} &bull; Generated on {{ now()->format('d M Y') }}
        </div>
    </div>
</body>
</html>
