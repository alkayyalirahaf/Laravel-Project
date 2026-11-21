<div class="admin-dashboard">
    <h1>Super Admin Dashboard</h1>
    @php
        $statIcons = [
            'Customers' => '👥',
            'Airlines' => '🏢',
            'Flights' => '✈️',
            'Bookings' => '🎫',
            'Payments' => '💳',
            'Successful payments' => '✅',
        ];
    @endphp
    <dl class="admin-dashboard-stats">
        @foreach($statistics as $label => $count)
            <div class="admin-dashboard-stat">
                <dt><span class="admin-stat-icon" aria-hidden="true">{{ $statIcons[$label] ?? '📊' }}</span>{{ $label }}</dt>
                <dd>
                    <strong class="admin-stat-value">{{ number_format($count) }}</strong>
                @if($label === 'Successful payments' && $statistics['Payments'] > 0)
                    <meter min="0" max="{{ $statistics['Payments'] }}" value="{{ $count }}" aria-label="Successful payments out of all payments"></meter>
                    <small>{{ number_format(100 * $count / $statistics['Payments'], 1) }}% of all payments</small>
                @endif
                </dd>
            </div>
        @endforeach
    </dl>
    <h2>Recent bookings</h2>
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Booking</th><th>Customer</th><th>Status</th><th>Created</th></tr></thead><tbody>
        @forelse($recentBookings as $booking)
            <tr><td>{{ $booking->code }}</td><td>{{ $booking->customer->user->name }}</td><td><span class="admin-flight-status" data-status="{{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td><td>{{ $booking->created_at?->format('Y-m-d H:i') }}</td></tr>
        @empty<tr><td colspan="4">No bookings yet.</td></tr>@endforelse
        </tbody>
    </table></div>
    <h2 class="mt-4">Recent payments</h2>
    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Payment</th><th>Booking</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead><tbody>
        @forelse($recentPayments as $payment)
            <tr><td>{{ $payment->id }}</td><td>{{ $payment->booking->code }}</td><td>{{ $payment->amount }}</td><td><span class="admin-flight-status" data-status="{{ $payment->status }}">{{ ucfirst($payment->status) }}</span></td><td>{{ $payment->created_at?->format('Y-m-d H:i') }}</td></tr>
        @empty<tr><td colspan="5">No payments yet.</td></tr>@endforelse
        </tbody>
    </table></div>
</div>
