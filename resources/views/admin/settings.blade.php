@extends('admin.layout')
@section('title', 'Canteen settings | Romy and Allit Food Haven')
@section('admin-content')
<main class="settings">
    <a class="back-button" href="{{ route('admin.dashboard') }}">← Back to dashboard</a>
    <h1>Canteen settings</h1>
    <section class="settings-card">
        <h2>Canteen profile</h2>
        <form class="settings-form" method="post" action="{{ route('admin.settings.profile') }}">@csrf
            <label>Canteen name<input name="canteen_name" required maxlength="160" value="{{ old('canteen_name', $profile->canteen_name) }}"></label>
            <label>Operating hours<input name="operating_hours" required maxlength="160" value="{{ old('operating_hours', $profile->operating_hours) }}"></label>
            <label>Contact info<input name="contact_info" maxlength="255" value="{{ old('contact_info', $profile->contact_info) }}"></label>
            <label>Pickup location / counter<input name="pickup_location" required maxlength="255" value="{{ old('pickup_location', $profile->pickup_location) }}"></label>
            <button class="button dark" type="submit">Save profile</button>
        </form>
    </section>
    <section class="settings-card">
        <h2>Categories</h2>
        <form class="settings-form" method="post" action="{{ route('admin.settings.categories.create') }}">@csrf
            <label>New category<input name="name" maxlength="80" required placeholder="Desserts"></label>
            <button class="button dark" type="submit">Add category</button>
        </form>
        @foreach ($categories as $category)
            <div class="category-row"><span>{{ $category->name }}</span>
                <form method="post" action="{{ route('admin.settings.categories.delete', $category->name) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('delete')<button class="text-link" type="submit">Delete</button></form>
            </div>
        @endforeach
    </section>
</main>
@endsection
