<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public Company $company;
    public ?string $customMessage;

    public function __construct(Invoice $invoice, Company $company, ?string $customMessage = null)
    {
        $this->invoice = $invoice;
        $this->company = $company;
        $this->customMessage = $customMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} from {$this->company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'company' => $this->company,
                'customMessage' => $this->customMessage,
            ],
        );
    }

    public function attachments(): array
    {
        $this->invoice->load(['customer', 'siteJob', 'items', 'payments']);

        // Get logo as base64 for PDF embedding
        $logoBase64 = null;
        if ($this->company->logo_path && Storage::disk('public')->exists($this->company->logo_path)) {
            $logoContent = Storage::disk('public')->get($this->company->logo_path);
            $mimeType = Storage::disk('public')->mimeType($this->company->logo_path);
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
        }

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $this->invoice,
            'company' => $this->company,
            'logoBase64' => $logoBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return [
            Attachment::fromData(fn () => $pdf->output(), "invoice-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
