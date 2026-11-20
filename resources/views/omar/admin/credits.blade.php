@extends('omar.layout', ['title' => 'Customer credits'])

@section('omar-content')
    <h1>Customer credits</h1>
    <p class="alert alert-warning">Balance changes are currently unavailable. Balances and recorded history are read-only.</p>
    <form method="GET" action="{{ route('admin.credits') }}" class="mb-3">
        <label class="form-label" for="search">Customer name or email</label>
        <input class="form-control mb-2" id="search" name="search" value="{{ $search }}" maxlength="100">
        <button class="btn btn-primary">Search</button>
    </form>
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Customer</th><th>Email</th><th>Credit balance</th></tr></thead><tbody>
        @forelse($customers as $customer)
            <tr><td>{{ $customer->user->name }}</td><td>{{ $customer->user->email }}</td><td>{{ $customer->credit_balance }}</td></tr>
        @empty<tr><td colspan="3">No customers found.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $customers->links('pagination::bootstrap-5') }}
    <h2>Transaction history</h2>
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Customer</th><th>Type</th><th>Amount</th><th>Balance after</th><th>Booking</th><th>Note</th><th>Date</th></tr></thead><tbody>
        @forelse($transactions as $transaction)
            <tr><td>{{ $transaction->customer->user->name }}</td><td>{{ $transaction->type }}</td><td>{{ $transaction->amount }}</td><td>{{ $transaction->balance_after }}</td><td>{{ $transaction->booking?->code ?? '—' }}</td><td>{{ $transaction->note }}</td><td>{{ $transaction->created_at?->format('Y-m-d H:i') }}</td></tr>
        @empty<tr><td colspan="7">No transactions found.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $transactions->links('pagination::bootstrap-5') }}
@endsection
