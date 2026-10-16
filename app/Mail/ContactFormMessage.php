<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $messageSubject,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nova mensagem de contato pelo site Administrar',
            replyTo: [new Address($this->email, $this->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'messageSubject' => $this->messageSubject,
                'messageBody' => $this->messageBody,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
