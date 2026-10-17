<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RedefinirSenhaMail extends Mailable
{
    public function __construct(
        public string $nome,
        public string $link,
        public int $minutos
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Redefinição de senha - Túnel do Tempo');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.redefinir-senha');
    }
}
