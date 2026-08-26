<?php

namespace App\Http\Controllers;

use App\Mail\EnterpriseEnquiryEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function showEnterprise(): View
    {
        return view('contact.enterprise');
    }

    public function submitEnterprise(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::to(config('valecheck.enterprise_contact_email'))->send(new EnterpriseEnquiryEmail($validated));

        return back()->with('status', "Thanks — we'll be in touch shortly.");
    }
}
