<?php

namespace App\Services;

use App\Models\TaxSlab;

class TaxService
{
    public function annualTax(float $annualIncome): float
    {
        $exempt = min($annualIncome * (float) setting('tax_exempt_fraction', 0), (float) setting('tax_exempt_cap', 0));
        $taxable = max(0, $annualIncome - $exempt);

        $tax = 0;
        $remaining = $taxable;

        foreach (TaxSlab::orderBy('sort_order')->orderBy('id')->get() as $slab) {
            if ($remaining <= 0) {
                break;
            }

            $portion = $slab->amount === null ? $remaining : min($remaining, $slab->amount);
            $tax += $portion * $slab->rate / 100;
            $remaining -= $portion;
        }

        if ($tax > 0) {
            $tax = max($tax, (float) setting('minimum_tax', 0));
        }

        return round($tax, 2);
    }

    public function monthlyTax(float $monthlyTaxableIncome): float
    {
        return round($this->annualTax($monthlyTaxableIncome * 12) / 12, 2);
    }
}
