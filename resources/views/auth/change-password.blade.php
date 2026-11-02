@extends('layouts.kedi-auth')

@section('title', 'Change Password')

@section('content')
    <h1>Change Password</h1>
    @if (session('success'))
        <div class="kedi-banner">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="kedi-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('password.update.change') }}">
        @csrf
        <label>
            <span class="kedi-label">Current password</span>
            <input class="kedi-input" type="password" name="current_password" placeholder="Current Password" required autofocus>
        </label>
        <label>
            <span class="kedi-label">New password</span>
            <input class="kedi-input" type="password" name="password" placeholder="New Password" required>
        </label>
        <label>
            <span class="kedi-label">Confirm new password</span>
            <input class="kedi-input" type="password" name="password_confirmation" placeholder="Confirm New Password" required>
        </label>
        <p class="kedi-help">Password must be at least 6 characters.</p>
        <div class="kedi-foot-actions" style="margin-top:1rem;">
            <button type="submit" class="kedi-btn primary" style="width:100%; justify-content:center;">Change Password</button>
        </div>
        <div class="kedi-auth-links" style="justify-content:center;">
            <a href="{{ url()->previous() }}">Back</a>
        </div>
    </form>
@endsection