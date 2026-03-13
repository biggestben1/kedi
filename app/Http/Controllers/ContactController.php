<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Show the contact form.
     */
    public function show()
    {
        $user = auth()->user();

        return view('contact.show', [
            'name' => old('name', $user?->name ?? ''),
            'email' => old('email', $user?->email ?? ''),
            'phone' => old('phone', $user?->phone ?? ''),
        ]);
    }

    /**
     * Store a contact submission.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        Contact::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?: null,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return back()->with('success', 'Thank you for contacting us. We will get back to you soon.');
    }
}
