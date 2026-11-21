@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="admin-pagination-disabled" aria-disabled="true">‹ Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Previous</a>
        @endif

        <span class="admin-pagination-count">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
        @else
            <span class="admin-pagination-disabled" aria-disabled="true">Next ›</span>
        @endif
    </nav>
@endif
