@extends('layouts.app')
@section('title', 'Verify email | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <p class="eyebrow"><span></span> Customer portal</p>
    <h1>Check your email.</h1>
    <p class="account-intro">We sent a 6-digit verification code to {{ $user->email }}. It expires in 10 minutes.</p>
    @if (session('notice'))<p class="account-success">{{ session('notice') }}</p>@endif
    @if (session('error'))<p class="account-message">{{ session('error') }}</p>@endif
    @if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('verification.verify') }}" class="account-form">
        @csrf
        <label>Verification code<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></label>
        <button class="button dark" type="submit">Verify email <span>→</span></button>
    </form>
    <form method="post" action="{{ route('verification.resend') }}" class="account-form" style="margin-top:14px">@csrf<button class="button" type="submit">Send a new code</button></form>
    <p class="account-switch"><a href="{{ route('login') }}">Back to log in</a></p>
</main>
@endsection
