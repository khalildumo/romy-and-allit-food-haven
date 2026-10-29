@extends('layouts.app')
@section('title', 'Verify OTP | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <p class="eyebrow"><span></span> Email verification</p>
    <h1>Enter your OTP.</h1>
    <p class="account-intro">A six-digit verification code was sent to <strong>{{ $email }}</strong>. It expires in five minutes.</p>
    @if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('otp.verify.store') }}" class="account-form">
        @csrf
        <label>Six-digit code<input type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required></label>
        <button class="button dark" type="submit">Verify OTP <span>→</span></button>
    </form>
    <p class="account-switch"><a href="{{ route('otp.send') }}">Request a new OTP</a></p>
</main>
@endsection
