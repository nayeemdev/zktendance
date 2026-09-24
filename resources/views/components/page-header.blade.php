@props(['title' => null, 'subtitle' => null])
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        @if ($title)<h2 class="h5 mb-0">{{ $title }}</h2>@endif
        @if ($subtitle)<div class="text-muted small">{{ $subtitle }}</div>@endif
    </div>
    <div class="d-flex flex-wrap gap-2">{{ $slot }}</div>
</div>
