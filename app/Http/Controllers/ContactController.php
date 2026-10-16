<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::to(config('mail.contact_address'))->send(new ContactFormMessage(
            name: $validated['name'],
            email: $validated['email'],
            messageSubject: $validated['subject'],
            messageBody: $validated['message'],
        ));

        return redirect()
            ->to(route('home').'#contato')
            ->with('contact_success', 'Mensagem enviada com sucesso. Em breve entraremos em contato.');
    }
}
