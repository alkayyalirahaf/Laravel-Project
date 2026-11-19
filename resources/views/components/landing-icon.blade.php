@props(['name'])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('search') <circle cx="10.5" cy="10.5" r="6"/><path d="m15 15 5 5"/> @break
        @case('plane') <path d="m22 2-7 20-4-9-9-4 20-7M11 13l5-5"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('seat') <path d="m5 4 3 10h10a2 2 0 0 1 2 2v2H7L3 5m6 13v3m8-3v3"/> @break
        @case('card') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18m-14 5h3"/> @break
        @case('ticket') <path d="M3 7h18v3a2 2 0 0 0 0 4v3H3v-3a2 2 0 0 0 0-4zm12 0v2m0 2v2m0 2v2"/> @break
    @endswitch
</svg>
