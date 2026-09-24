<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetupRequest;
use App\Services\SetupService;
use Illuminate\Support\Facades\Auth;

class SetupController extends Controller
{
    public function create()
    {
        return view('setup', [
            'countries' => SetupService::COUNTRIES,
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function store(SetupRequest $request, SetupService $service)
    {
        $admin = $service->run($request->validated());
        Auth::login($admin);

        return redirect()->route('admin.dashboard')->with('success', 'Setup completed. Add your branches, devices and employees next.');
    }
}
