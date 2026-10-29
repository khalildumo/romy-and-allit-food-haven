@extends('layouts.app')
@section('title', 'Reset password | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <p class="eyebrow"><span></span> Romy and Allit Food Haven</p>
    <h1>Choose a new password.</h1>
    <p class="account-intro">Use at least 8 characters for your new password.</p>
    @if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('password.update') }}" class="account-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email address<input type="email" name="email" required autocomplete="email" value="{{ old('email', $email) }}"></label>
        <label>New password<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
        <label>Confirm new password<input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></label>
        <button class="button dark" type="submit">Save new password <span>→</span></button>
    </form>
</main>
@endsection
