<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminContactController extends Controller
{
    /**
     * List contact form submissions.
     */
    public function index(Request $request): View
    {
        $query = Contact::with('user')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        $contacts = $query->paginate(20)->withQueryString();

        return view('admin.contacts.index', [
            'contacts' => $contacts,
            'search' => $request->search,
        ]);
    }

    /**
     * Show a single contact submission.
     */
    public function show(Contact $contact): View
    {
        $contact->load('user');

        return view('admin.contacts.show', ['contact' => $contact]);
    }
}
