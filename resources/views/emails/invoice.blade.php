<x-mail::message>
# Invoice {{ $invoice->invoice_number }}

Dear {{ $invoice->customer->name ?? 'Valued Customer' }},

@if($customMessage)
{{ $customMessage }}

@else
Please find attached your invoice from **{{ $company->name }}**.
@endif

## Invoice Details

| Description | Amount |
|:------------|-------:|
| Invoice Number | {{ $invoice->invoice_number }} |
| Issue Date | {{ $invoice->issue_date->format('d M Y') }} |
@if($invoice->due_date)
| Due Date | {{ $invoice->due_date->format('d M Y') }} |
@endif
| **Total Amount** | **${{ number_format($invoice->total, 2) }}** |
@if($invoice->amount_paid > 0)
| Amount Paid | ${{ number_format($invoice->amount_paid, 2) }} |
| **Balance Due** | **${{ number_format($invoice->balance, 2) }}** |
@endif

@if($invoice->status !== 'paid')
<x-mail::panel>
Please ensure payment is made by the due date to avoid any late fees.
</x-mail::panel>
@else
<x-mail::panel>
This invoice has been paid in full. Thank you for your business!
</x-mail::panel>
@endif

@if($company->phone || $company->email)
If you have any questions, please don't hesitate to contact us:
@if($company->phone)
- Phone: {{ $company->phone }}
@endif
@if($company->email)
- Email: {{ $company->email }}
@endif
@endif

Thank you for your business!

Best regards,<br>
**{{ $company->name }}**
</x-mail::message>
