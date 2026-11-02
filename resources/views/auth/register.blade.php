@extends('layouts.kedi-auth')

@section('title', 'Register')

@section('content')
    <h1>Registration</h1>
    @if ($errors->any())
        <div class="kedi-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label>
            <span class="kedi-label">User name</span>
            <input class="kedi-input" type="text" name="name" placeholder="User name" value="{{ old('name') }}" required autofocus>
        </label>
        <label>
            <span class="kedi-label">Email</span>
            <input class="kedi-input" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
        </label>
        <label>
            <span class="kedi-label">Password</span>
            <input class="kedi-input" type="password" name="password" placeholder="Password" required>
        </label>
        <label>
            <span class="kedi-label">Confirm password</span>
            <input class="kedi-input" type="password" name="password_confirmation" placeholder="Confirm Password" required>
        </label>
        <div class="kedi-foot-actions" style="margin-top:1rem;">
            <button type="submit" class="kedi-btn primary" style="width:100%; justify-content:center;">Register</button>
        </div>
        <div class="kedi-auth-links" style="justify-content:center;">
            <a href="{{ route('login') }}">Already have an account? Sign in</a>
        </div>
    </form>
@endsection
