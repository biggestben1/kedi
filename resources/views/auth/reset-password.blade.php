@extends('layouts.kedi-auth')

@section('title', 'Reset Password')

@section('content')
    <h1>Reset Password</h1>
    @if (session('status'))
        <div class="kedi-banner">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="kedi-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>
            <span class="kedi-label">Email</span>
            <input class="kedi-input" type="email" name="email" placeholder="Email" value="{{ old('email', $email) }}" required autofocus>
        </label>
        <label>
            <span class="kedi-label">New password</span>
            <input class="kedi-input" type="password" name="password" placeholder="New Password" required>
        </label>
        <label>
            <span class="kedi-label">Confirm password</span>
            <input class="kedi-input" type="password" name="password_confirmation" placeholder="Confirm Password" required>
        </label>
        <div class="kedi-foot-actions" style="margin-top:1rem;">
            <button type="submit" class="kedi-btn primary" style="width:100%; justify-content:center;">Reset Password</button>
        </div>
        <div class="kedi-auth-links" style="justify-content:center;">
            <a href="{{ route('login') }}">Back to Login</a>
        </div>
    </form>
@endsection
