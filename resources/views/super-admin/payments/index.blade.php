@extends('super-admin.management-layout')

@section('admin-title', 'Payments')

@section('admin-content')
    <h1>Payments</h1>

    <form method="GET" action="{{ route('admin.payments') }}" class="row g-2 mb-3 align-items-end">
        <div class="col-md-6">
            <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search booking code or Stripe payment intent">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                @foreach(['pending', 'succeeded', 'failed'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Filter</button></div>
        <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('admin.payments') }}">Clear</a></div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Booking code</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Status</th>
                    <th scope="col">Stripe Payment Intent ID</th>
                    <th scope="col">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <th scope="row">{{ $payment->booking?->code ?? '—' }}</th>
                        <td>{{ $payment->booking?->customer?->user?->name ?? '—' }}</td>
                        <td>JOD {{ number_format((float) $payment->amount, 2) }}</td>
                        <td><span class="badge text-bg-secondary">{{ ucfirst($payment->status) }}</span></td>
                        <td>{{ $payment->stripe_payment_intent_id ?? '—' }}</td>
                        <td>{{ $payment->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $payments->links('super-admin.pagination') }}
@endsection
