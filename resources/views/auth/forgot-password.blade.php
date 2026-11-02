@extends('layouts.kedi-auth')

@section('title', 'Forgot Password')

@section('content')
    <h1>Forgot Password</h1>
    <p class="kedi-help" style="text-align:center; margin-top:-0.75rem;">Enter your email address and we'll send you a link to reset your password.</p>

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

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label>
            <span class="kedi-label">Email</span>
            <span class="kedi-field">
                <span class="material-symbols-outlined lead">mail</span>
                <input class="kedi-input iconed" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autofocus>
            </span>
        </label>
        <div class="kedi-foot-actions" style="margin-top:1rem;">
            <button type="submit" class="kedi-btn primary" style="width:100%; justify-content:center;">Send Reset Link</button>
        </div>
        <div class="kedi-auth-links" style="justify-content:center;">
            <a href="{{ route('login') }}">Back to Login</a>
        </div>
    </form>
@endsection
