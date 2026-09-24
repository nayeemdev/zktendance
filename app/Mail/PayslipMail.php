<?php

namespace App\Mail;

use App\Models\Payslip;
use App\Services\PayslipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payslip $payslip) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payslip for '.$this->payslip->payrollRun->month->format('F Y'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payslip');
    }

    public function attachments(): array
    {
        $service = app(PayslipService::class);

        return [
            Attachment::fromData(fn () => $service->pdf($this->payslip)->output(), $service->fileName($this->payslip))
                ->withMime('application/pdf'),
        ];
    }
}
