@extends('layouts.app')
@section('title', 'Email verified | Romy and Allit Food Haven')
@section('body-class', 'account-page')
@section('content')
<main class="account-card">
    <p class="receipt-icon">✓</p>
    <h1>Email verified!</h1>
    <p class="account-success">Your email address has been verified successfully.</p>
    <p><strong>{{ $email }}</strong></p>
    <p class="account-intro">You can now continue to the next step in your application.</p>
    <p class="account-switch"><a href="{{ route('home') }}">Back to Romy and Allit Food Haven</a></p>
</main>
@endsection
