@extends('layouts.app')
@section('title', 'Send OTP | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <p class="eyebrow"><span></span> Email verification</p>
    <h1>Verify your email.</h1>
    <p class="account-intro">Enter your email address to receive a six-digit verification code.</p>
    @if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('otp.send.store') }}" class="account-form">
        @csrf
        <label>Email address<input type="email" name="email" maxlength="255" value="{{ old('email') }}" required autocomplete="email"></label>
        <button class="button dark" type="submit">Send OTP <span>→</span></button>
    </form>
    <p class="account-switch"><a href="{{ route('home') }}">← Back to menu</a></p>
</main>
@endsection
