@extends('layouts.app')
@section('body-class', 'dashboard-page')
@section('content')
<header class="dashboard-header">
    <a class="brand" href="{{ route('admin.dashboard') }}">Romy and Allit Food Haven · Admin</a>
    <div class="dashboard-actions">
        <a class="text-link" href="{{ route('home') }}">Customer site</a>
        <a class="text-link" href="{{ route('admin.products') }}">Products</a>
        <a class="text-link" href="{{ route('admin.customers') }}">Customers</a>
        <a class="text-link" href="{{ route('admin.staff') }}">Staff</a>
        <a class="text-link" href="{{ route('admin.settings') }}">Settings</a>
        <span>Hi, {{ Auth::user()->full_name }}</span>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="button dark" type="submit">Log out</button></form>
    </div>
</header>
@if (session('status'))<p class="account-success">{{ session('status') }}</p>@endif
@if ($errors->any())<p class="account-message">{{ $errors->first() }}</p>@endif
@yield('admin-content')
@endsection
