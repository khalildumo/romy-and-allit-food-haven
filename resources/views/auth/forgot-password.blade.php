@extends('layouts.app')
@section('title', 'Forgot password | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <p class="eyebrow"><span></span> Romy and Allit Food Haven</p>
    <h1>Forgot your password?</h1>
    <p class="account-intro">Enter the email address on your account and we’ll send a password reset link.</p>
    @if (session('status'))<p class="account-success">{{ session('status') }}</p>@endif
    @if (session('error'))<p class="account-message">{{ session('error') }}</p>@endif
    @if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('password.email') }}" class="account-form">
        @csrf
        <label>Email address<input type="email" name="email" maxlength="160" required autocomplete="email" value="{{ old('email') }}"></label>
        <button class="button dark" type="submit">Send reset link <span>→</span></button>
    </form>
    <p class="account-switch"><a href="{{ route('login') }}">Back to log in</a></p>
    <p class="account-switch"><a href="{{ route('home') }}">← Back to menu</a></p>
</main>
@endsection
