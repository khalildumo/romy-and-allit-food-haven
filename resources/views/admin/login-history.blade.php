
@extends('admin.layout')

@section('title', 'Admin Login History | Romy and Allit Food Haven')

@section('admin-content')
<main class="staff-wrap">
    <a class="back-button" href="{{ route('admin.dashboard') }}">← Back to dashboard</a>

    <p class="eyebrow"><span></span> Security monitoring</p>
    <h1>Admin login history</h1>

    <section class="staff-panel">
        <h2>Recent login activity</h2>
        <p>Review administrator login attempts and the devices used to access the system.</p>

        <div class="orders-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Device</th>
                        <th>Browser</th>
                        <th>Operating system</th>
                        <th>IP address</th>
                        <th>Login date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($histories as $history)
                        <tr>
                            <td>{{ $history->username }}</td>
                            <td>
                                @if ($history->status === 'success')
                                    <span class="account-success">Successful</span>
                                @else
                                    <span class="account-message">Failed</span>
                                @endif
                            </td>
                            <td>{{ $history->device_type ?? 'Unknown' }}</td>
                            <td>{{ $history->browser ?? 'Unknown' }}</td>
                            <td>{{ $history->operating_system ?? 'Unknown' }}</td>
                            <td>{{ $history->ip_address ?? 'Unavailable' }}</td>
                            <td>
                                {{ $history->logged_in_at
                                    ? $history->logged_in_at->format('M j, Y h:i A')
                                    : 'Unavailable' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="no-orders">
                                No login history records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $histories->links() }}
        </div>
    </section>
</main>
@endsection
