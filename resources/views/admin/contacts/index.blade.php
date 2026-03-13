@extends('layouts.admin')

@section('title', 'Contact Us Messages')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Contact Us Messages</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Contact Form Submissions</h3>
            <form method="GET" action="{{ route('admin.contacts.index') }}" class="d-flex gap-2">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Search name, email, subject..." value="{{ $search }}" style="min-width: 200px;">
                <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
                @if($search)
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $contact)
                            <tr>
                                <td>{{ $contact->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $contact->name }}</td>
                                <td>
                                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                                    @if($contact->phone)
                                        <br><small class="text-muted">{{ $contact->phone }}</small>
                                    @endif
                                </td>
                                <td>{{ Str::limit($contact->subject, 30) }}</td>
                                <td>{{ Str::limit($contact->message, 50) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.contacts.show', $contact) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No contact messages yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($contacts->hasPages())
            <div class="card-footer">
                {{ $contacts->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
