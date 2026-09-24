<div class="row">
    <x-input name="start_date" type="date" label="From" class="col-md-6 mb-3" required />
    <x-input name="end_date" type="date" label="To" class="col-md-6 mb-3" />
</div>
<x-checkbox name="is_half_day" label="Half day (uses From date only)" :checked="false" />
<script>
    document.getElementById('start_date').addEventListener('change', function () {
        const end = document.getElementById('end_date');
        if (!end.value || end.value < this.value) end.value = this.value;
    });
</script>
