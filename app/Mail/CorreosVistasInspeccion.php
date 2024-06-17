<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorreosVistasInspeccion extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $datosAdicionales;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $datosAdicionales)
    {
        $this->subject = $subject;
        $this->datosAdicionales = $datosAdicionales;
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.creacion_diagnostico')
                    ->with('datos', $this->datosAdicionales);
    }

    /**
     * Get the message envelope.
     */
    /*public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Correos Vistas Inspeccion',
        );
    }*/

    /**
     * Get the message content definition.
     */
    /*public function content(): Content
    {
        return new Content(
            view: 'emails.creacion_diagnostico',
        );
    }*/

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
