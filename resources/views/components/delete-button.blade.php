@props(['action', 'message' => 'Are you sure you want to delete this?', 'label' => null])
<form method="POST" action="{{ $action }}" class="d-inline" data-confirm="{{ $message }}">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="hgi-stroke hgi-delete-02"></i>{{ $label ? ' '.$label : '' }}</button>
</form>
