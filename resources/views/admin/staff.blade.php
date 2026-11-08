@extends('admin.layout')
@section('title', 'Staff accounts | Romy and Allit Food Haven')
@section('admin-content')
<main class="staff-wrap">
    <a class="back-button" href="{{ route('admin.dashboard') }}">← Back to dashboard</a>
    <p class="eyebrow"><span></span> Access control</p>
    <h1>Staff accounts</h1>
    <section class="staff-panel">
        <h2>Create staff account</h2>
        <form method="post" action="{{ route('admin.staff.create') }}" class="staff-form">@csrf
            <label>Full name<input name="full_name" maxlength="120" required></label>
            <label>Username<input name="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9_.-]+" required></label>
            <label>Account role<select name="role" required><option value="customer">Staff</option><option value="admin">Administrator</option></select></label>
            <label>Temporary password (12+ characters)<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
            <button class="button dark" type="submit">Create account</button>
        </form>
    </section>
    <section class="staff-panel">
        <h2>Accounts from the users database</h2>
        <div class="orders-table-wrap"><table class="staff-table">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse ($staff as $person)
                <tr><td>{{ $person->full_name }}</td><td>{{ $person->username }}</td><td>{{ ucfirst($person->role) }}</td><td>{{ $person->created_at->format('M j, Y') }}</td>
                    <td><details class="reset-password"><summary>Reset password</summary>
                        <form method="post" action="{{ route('admin.staff.password', $person) }}" onsubmit="return confirm('Reset this account password?');">@csrf @method('patch')
                            <input type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="New password">
                            <input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password" placeholder="Confirm password">
                            <button class="action-button" type="submit">Save password</button>
                        </form>
                    </details></td>
                </tr>
            @empty<tr><td colspan="5" class="no-orders">No staff accounts found.</td>@endforelse
            </tbody>
        </table></div>
    </section>
</main>
@endsection
