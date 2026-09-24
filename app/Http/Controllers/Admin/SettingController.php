<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Models\TaxSlab;
use App\Services\SettingService;
use App\Services\SetupService;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function edit(SettingService $settings)
    {
        return view('admin.settings', [
            'settings' => $settings->all(),
            'countries' => SetupService::COUNTRIES,
            'timezones' => timezone_identifiers_list(),
            'slabs' => TaxSlab::orderBy('sort_order')->get(),
        ]);
    }

    public function update(SettingRequest $request, SettingService $settings)
    {
        $data = $request->validated();

        if ($request->hasFile('company_logo')) {
            $data['company_logo'] = $request->file('company_logo')->store('logos', 'public');
        } else {
            unset($data['company_logo']);
        }

        $slabs = $data['slabs'] ?? [];
        unset($data['slabs']);

        DB::transaction(function () use ($settings, $data, $slabs) {
            $settings->set($data);

            TaxSlab::query()->delete();
            foreach (array_values($slabs) as $i => $slab) {
                if (isset($slab['rate']) && $slab['rate'] !== '') {
                    TaxSlab::create(['amount' => $slab['amount'] ?: null, 'rate' => $slab['rate'], 'sort_order' => $i + 1]);
                }
            }
        });

        return back()->with('success', 'Settings saved.');
    }
}
