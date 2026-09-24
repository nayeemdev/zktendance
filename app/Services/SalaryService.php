<?php

namespace App\Services;

use App\Models\SalaryComponent;
use App\Models\SalaryStructure;

class SalaryService
{
    /**
     * @return array<int, array{component_id: int, name: string, type: string, is_basic: bool, is_taxable: bool, amount: float}>
     */
    public function breakdown(SalaryStructure $structure, float $gross): array
    {
        $items = $structure->items()->with('component')->get()->sortBy(fn ($item) => [
            $item->component->is_basic ? 0 : 1,
            $item->component->sort_order,
        ]);

        $basic = 0;
        $lines = [];

        foreach ($items as $item) {
            $base = match ($item->calculation) {
                'percent_of_gross' => $gross * $item->value / 100,
                'percent_of_basic' => $basic * $item->value / 100,
                default => $item->value,
            };
            $amount = round($base, 2);

            if ($item->component->is_basic) {
                $basic = $amount;
            }

            $lines[] = [
                'component_id' => $item->component->id,
                'name' => $item->component->name,
                'type' => $item->component->type,
                'is_basic' => $item->component->is_basic,
                'is_taxable' => $item->component->is_taxable,
                'amount' => $amount,
            ];
        }

        return $lines;
    }

    public function basic(array $breakdown): float
    {
        return (float) (collect($breakdown)->firstWhere('is_basic', true)['amount'] ?? 0);
    }

    public function totalEarnings(array $breakdown): float
    {
        return round(collect($breakdown)->where('type', SalaryComponent::EARNING)->sum('amount'), 2);
    }
}
