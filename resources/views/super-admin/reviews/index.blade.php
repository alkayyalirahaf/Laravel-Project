@extends('super-admin.management-layout')
@section('admin-title', 'Review moderation')
@section('admin-content')
    <h1>Review moderation</h1>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Customer</th><th>Flight</th><th>Rating</th><th>Comment</th><th>Status</th><th>Save</th></tr></thead><tbody>
        @forelse($reviews as $review)<tr><td>{{ $review->customer->user->name }}</td><td>{{ $review->bookingFlight->flight->flight_number }}</td><td>{{ $review->rating }}/5</td><td>{{ $review->comment ?: '—' }}</td>
            <td colspan="2"><form class="d-flex gap-2" method="POST" action="{{ route('admin.reviews.update', $review) }}">@csrf @method('PATCH')<select class="form-select" name="status"><option value="visible" @selected($review->status === 'visible')>Visible</option><option value="hidden" @selected($review->status === 'hidden')>Hidden</option></select><button class="btn btn-primary">Save</button></form></td></tr>
        @empty<tr><td colspan="6">No reviews found.</td></tr>@endforelse
    </tbody></table></div>{{ $reviews->links('super-admin.pagination') }}
@endsection
