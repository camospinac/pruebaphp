<?php
namespace App\Mail;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionPendingEmail extends Mailable
{
    use Queueable, SerializesModels;
    public Subscription $subscription;
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Alerta: Nueva Suscripción Pendiente de Aprobación');
    }
    public function content(): Content
    {
        return new Content(view: 'emails.subscription-pending');
    }
}