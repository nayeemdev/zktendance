@extends('layouts.app')

@section('title', $device->exists ? 'Edit Device' : 'Add Device')

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $device->exists ? route('admin.devices.update', $device) : route('admin.devices.store') }}">
                    @csrf
                    @if ($device->exists) @method('PUT') @endif
                    <div class="row">
                        <x-input name="name" label="Device Name" :value="$device->name" placeholder="Main Gate" class="col-md-6 mb-3" required />
                        <x-select name="branch_id" label="Branch" :options="$branches" :value="$device->branch_id" placeholder="Select" class="col-md-6 mb-3" required />
                        <x-select name="model" label="Device Model" :options="array_combine(array_keys(\App\Models\Device::MODELS), array_keys(\App\Models\Device::MODELS))" :value="$device->model" placeholder="Select model" class="col-md-6 mb-3" help="Selecting a model suggests the usual connection mode." />
                        <x-select name="connection_mode" label="Connection Mode" :options="\App\Models\Device::MODES" :value="$device->connection_mode" class="col-md-6 mb-3" required />
                    </div>
                    <div class="row" id="pullFields">
                        <x-input name="ip_address" label="IP Address" :value="$device->ip_address" placeholder="192.168.1.201" class="col-md-8 mb-3" />
                        <x-input name="port" type="number" label="Port" :value="$device->port" class="col-md-4 mb-3" required />
                    </div>
                    <x-input name="serial_number" label="Serial Number" :value="$device->serial_number" help="Required for push mode. Find it in the device menu: System Info > Device Info." />
                    <x-checkbox name="is_active" label="Active" :checked="$device->is_active" />
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.devices.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Which mode should I use?</div>
            <div class="card-body small">
                <p><strong>Pull mode</strong> works with almost every ZKTeco terminal (K40, MB460, iFace, F18, X628, and so on). The server connects to the device on UDP port 4370. The server and the device must be on the same network or reachable by VPN. Keep the device Comm Key as 0.</p>
                <p><strong>Push mode (ADMS)</strong> is for newer devices (SpeedFace, ProFace, SenseFace, MB560, UFace800). On the device open <em>Comm. &gt; Cloud Server Setting</em> and set:</p>
                <ul>
                    <li>Server address: <code>{{ request()->getHost() }}</code></li>
                    <li>Server port: <code>{{ request()->getPort() }}</code></li>
                    <li>HTTPS: match your server</li>
                </ul>
                <p class="mb-0">The device then calls <code>{{ url('/iclock/cdata') }}</code>. Use push mode when the server is in the cloud.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const models = @json(\App\Models\Device::MODELS);
    document.getElementById('model').addEventListener('change', function () {
        if (models[this.value]) document.getElementById('connection_mode').value = models[this.value];
    });
</script>
@endsection
