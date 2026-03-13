@extends('layouts.admin')

@section('title', 'Contact Message')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Contact Message</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.contacts.index') }}">Contact Us</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $contact->subject }}</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Message from {{ $contact->name }}</h3>
            <span class="text-muted small">{{ $contact->created_at->format('F j, Y \a\t g:i A') }}</span>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Name:</strong> {{ $contact->name }}</p>
                    <p class="mb-1"><strong>Email:</strong> <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></p>
                    @if($contact->phone)
                        <p class="mb-1"><strong>Phone:</strong> <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a></p>
                    @endif
                    @if($contact->user)
                        <p class="mb-0"><strong>User account:</strong> <a href="{{ route('admin.users.edit', $contact->user) }}">{{ $contact->user->name }}</a></p>
                    @endif
                </div>
            </div>
            <hr>
            <p class="mb-2"><strong>Subject:</strong> {{ $contact->subject }}</p>
            <p class="mb-0"><strong>Message:</strong></p>
            <div class="bg-light p-3 rounded mt-2" style="white-space: pre-wrap;">{{ $contact->message }}</div>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">Back to list</a>
            <a href="mailto:{{ $contact->email }}?subject=Re: {{ urlencode($contact->subject) }}" class="btn btn-primary">Reply by email</a>
        </div>
    </div>
@endsection
