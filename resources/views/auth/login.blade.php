@extends('layouts.app')
@section('title', 'Log in | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <a class="brand" href="{{ route('home') }}">Romy and Allit Food Haven</a>
    <p class="eyebrow"><span></span> Customer portal</p>
    <h1>Welcome back.</h1>
    <p class="account-intro">Log in to continue to your Romy and Allit Food Haven account.</p>
    @if (session('status'))<p class="account-success">{{ session('status') }}</p>@endif
    @if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('login.store') }}" class="account-form" autocomplete="on">
        @csrf
        <label>Username<input name="username" maxlength="80" required autocomplete="username" value="{{ old('username') }}"></label>
        <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
        <label class="checkbox-label" for="remember-me">
            <input id="remember-me" type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Remember me</span>
        </label>
        <p class="account-switch account-forgot"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
        <button class="button dark" type="submit">Log in <span>→</span></button>
    </form>
    <p class="account-switch">New here? <a href="{{ route('register') }}">Create an account</a></p>
    <p class="account-switch"><a href="{{ route('home') }}">← Back to menu</a></p>
</main>
@endsection
