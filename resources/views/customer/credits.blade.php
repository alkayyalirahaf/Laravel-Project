@extends('layouts.app', ['area' => 'my'])

@section('title', $title)

@section('content')
<h1>{{ $title }}</h1>

<section class="panel" aria-labelledby="credit-balance-heading">
    <p class="eyebrow">Current balance</p>
    <h2 id="credit-balance-heading">JOD {{ number_format((float) $customer->credit_balance, 2) }}</h2>
</section>

<section class="panel table-wrap section">
    <table>
        <caption>Credit activity</caption>
        <thead>
            <tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance after</th><th>Note</th></tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at?->format('d M Y H:i') }}</td>
                    <td>{{ str($transaction->type)->replace('_', ' ')->title() }}</td>
                    <td>JOD {{ number_format((float) $transaction->amount, 2) }}</td>
                    <td>JOD {{ number_format((float) $transaction->balance_after, 2) }}</td>
                    <td>{{ $transaction->note ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No credit transactions yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="section">{{ $transactions->links() }}</div>
@endsection
