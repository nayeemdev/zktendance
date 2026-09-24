<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayslipItemRequest;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Services\PayslipService;

class PayslipItemController extends Controller
{
    public function __construct(private PayslipService $service) {}

    public function store(PayslipItemRequest $request, Payslip $payslip)
    {
        $this->service->addItem($payslip, $request->validated());

        return back()->with('success', 'Line added.');
    }

    public function update(PayslipItemRequest $request, PayslipItem $item)
    {
        $this->service->updateItem($item, $request->validated());

        return back()->with('success', 'Line updated.');
    }

    public function destroy(PayslipItem $item)
    {
        $this->service->removeItem($item);

        return back()->with('success', 'Line removed.');
    }
}
