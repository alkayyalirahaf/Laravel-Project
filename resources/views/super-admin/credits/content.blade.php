
    <p>Adjust customer credit with a reason. Every change is recorded in the transaction history.</p>
    <form method="GET" action="{{ route('admin.credits') }}" class="section">
        <label class="form-label" for="search">Customer name or email</label>
        <input class="input-control mb-2" id="search" name="search" value="{{ $search }}" maxlength="100">
        <button class="button">Search</button>
    </form>
    <div class="table-wrap panel section"><table class="table table-striped">
        <thead><tr><th>Customer</th><th>Email</th><th>Credit balance</th></tr></thead><tbody>
        @forelse($customers as $customer)
            <tr><td>{{ $customer->user->name }}</td><td>{{ $customer->user->email }}</td><td>{{ $customer->credit_balance }}
                <form method="POST" action="{{ route('admin.credits.update', $customer) }}" class="stack">
                    @csrf
                    <input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <x-travel-field name="action" label="Action" :options="['add' => 'Add credit', 'deduct' => 'Deduct credit', 'adjust' => 'Set balance']" />
                    <x-travel-field name="amount" label="Amount" type="number" min="0" max="99999999.99" step="0.01" required />
                    <x-travel-field name="reason" label="Reason" maxlength="200" required />
                    <button class="button" type="submit">Update credit</button>
                </form>
            </td></tr>
        @empty<tr><td colspan="3">No customers found.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $customers->links('super-admin.pagination') }}
    <h2>Transaction history</h2>
    <div class="table-wrap panel section"><table class="table table-striped">
        <thead><tr><th>Customer</th><th>Type</th><th>Amount</th><th>Balance after</th><th>Booking</th><th>Note</th><th>Date</th></tr></thead><tbody>
        @forelse($transactions as $transaction)
            <tr><td>{{ $transaction->customer->user->name }}</td><td>{{ $transaction->type }}</td><td>{{ $transaction->amount }}</td><td>{{ $transaction->balance_after }}</td><td>{{ $transaction->booking?->code ?? '—' }}</td><td>{{ $transaction->note }}</td><td>{{ $transaction->created_at?->format('Y-m-d H:i') }}</td></tr>
        @empty<tr><td colspan="7">No transactions found.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $transactions->links('super-admin.pagination') }}