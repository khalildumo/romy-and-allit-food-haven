@extends('admin.layout')
@section('title', 'Manage customers | Romy and Allit Food Haven')
@section('admin-content')
<main class="staff-wrap">
    <a class="back-button" href="{{ route('admin.dashboard') }}">← Back to dashboard</a>
    <p class="eyebrow"><span></span> Customer accounts</p>
    <h1>Manage customers</h1>
    <p class="account-intro">Customer details currently saved in the users database.</p>
    <section class="staff-panel">
        <h2>Registered customers <small>({{ $customers->total() }})</small></h2>
        <div class="orders-table-wrap"><table class="staff-table customer-table">
            <thead><tr><th>Name</th><th>Username</th><th>Institution ID</th><th>Email</th><th>Phone</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td>{{ $customer->full_name }}</td><td>{{ $customer->username }}</td><td>{{ $customer->institution_id ?: '—' }}</td><td>{{ $customer->email ?: '—' }}</td><td>{{ $customer->phone ?: '—' }}</td><td>{{ $customer->created_at->format('M j, Y') }}</td>
                    <td class="customer-actions">
                        <details><summary class="customer-edit-button">Edit</summary>
                            <form method="post" action="{{ route('admin.customers.update', $customer) }}" class="customer-edit-form">@csrf @method('patch')
                                <label>Name<input name="full_name" maxlength="120" required value="{{ $customer->full_name }}"></label>
                                <label>Username<input name="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9_.-]+" required value="{{ $customer->username }}"></label>
                                <label>Institution ID<input name="institution_id" maxlength="80" value="{{ $customer->institution_id }}"></label>
                                <label>Email<input type="email" name="email" maxlength="160" value="{{ $customer->email }}"></label>
                                <label>Phone<input name="phone" maxlength="40" value="{{ $customer->phone }}"></label>
                                <button class="button dark" type="submit">Save changes</button>
                            </form>
                        </details>
                        <form method="post" action="{{ route('admin.customers.delete', $customer) }}" onsubmit="return confirm('Delete this customer account? This cannot be undone.');">@csrf @method('delete')<button class="customer-delete-button" type="submit">Delete</button></form>
                    </td>
                </tr>
            @empty<tr><td colspan="7" class="no-orders">No customer accounts found.</td></tr>@endforelse
            </tbody>
        </table></div>
        {{ $customers->links() }}
    </section>
</main>
@endsection
