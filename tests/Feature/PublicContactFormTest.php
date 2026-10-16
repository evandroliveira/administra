<?php

namespace Tests\Feature;

use App\Mail\ContactFormMessage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicContactFormTest extends TestCase
{
    public function test_contact_form_sends_message_to_configured_contact_address(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.store'), [
            'name' => 'Ana Silva',
            'email' => 'ana@example.com',
            'subject' => 'Dúvida sobre o plano',
            'message' => 'Gostaria de saber mais sobre o sistema.',
        ]);

        $response->assertRedirect(route('home').'#contato')
            ->assertSessionHas('contact_success');

        Mail::assertSent(ContactFormMessage::class, function (ContactFormMessage $mail): bool {
            return $mail->hasTo('administrativo@lojagerencia.com.br')
                && $mail->name === 'Ana Silva'
                && $mail->email === 'ana@example.com'
                && $mail->messageSubject === 'Dúvida sobre o plano'
                && $mail->messageBody === 'Gostaria de saber mais sobre o sistema.';
        });
    }

    public function test_contact_form_rejects_invalid_input_without_sending_email(): void
    {
        Mail::fake();

        $response = $this->from(route('home').'#contato')->post(route('contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => '',
            'message' => '',
        ]);

        $response->assertRedirect(route('home').'#contato')
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        Mail::assertNothingOutgoing();
    }
}
