<form class="card card-body mb-3">
    <div class="row g-2">
        @if (isset($month))
            <div class="col-md-2"><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control"></div>
        @else
            <div class="col-md-2"><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
        @endif
        <x-select name="branch_id" :options="$branches" :value="request('branch_id')" placeholder="All Branches" class="col-md-3" />
        <x-select name="department_id" :options="$departments" :value="request('department_id')" placeholder="All Departments" class="col-md-3" />
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-secondary flex-fill">Show</button>
            <div class="dropdown">
                <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown" title="Export"><i class="hgi-stroke hgi-download-04"></i></button>
                <div class="dropdown-menu dropdown-menu-end">
                    <button class="dropdown-item" name="export" value="xlsx"><i class="hgi-stroke hgi-xls-02 me-2"></i>Excel</button>
                    <button class="dropdown-item" name="export" value="csv"><i class="hgi-stroke hgi-csv-02 me-2"></i>CSV</button>
                </div>
            </div>
        </div>
    </div>
</form>
