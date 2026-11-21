@props(['type' => 'info'])
@push('toasts')
    <div class="site-toast site-toast--{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">
        <div>{{ $slot }}</div>
        <button type="button" data-dismiss-toast aria-label="{{ __('Dismiss notification') }}">×</button>
    </div>
@endpush
