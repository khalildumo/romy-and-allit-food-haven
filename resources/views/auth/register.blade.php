@extends('layouts.app')
@section('title', 'Create account | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <a class="brand" href="{{ route('home') }}">Romy and Allit Food Haven</a>
    <p class="eyebrow"><span></span> Customer portal</p>
    <h1>Create your account.</h1>
    <p class="account-intro">Register to order faster next time.</p>
    @if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('register.store') }}" class="account-form" autocomplete="on">
        @csrf
        <label>Full name<input name="full_name" maxlength="120" required autocomplete="name" value="{{ old('full_name') }}"></label>
        <label>Username<input name="username" maxlength="80" pattern="[A-Za-z0-9_.-]+" required autocomplete="username" value="{{ old('username') }}"></label>
        <label>Institution ID<input name="institution_id" maxlength="80" required value="{{ old('institution_id') }}"></label>
        <label>Email<input type="email" name="email" maxlength="160" required autocomplete="email" value="{{ old('email') }}"></label>
        <label>Phone<input name="phone" maxlength="40" required autocomplete="tel" value="{{ old('phone') }}"></label>
        <label>Password<input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
        <label>Confirm password<input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></label>
        <button class="button dark" type="submit">Create account <span>→</span></button>
    </form>
    <p class="account-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    <p class="account-switch"><a href="{{ route('home') }}">← Back to menu</a></p>
</main>
@endsection
