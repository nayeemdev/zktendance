<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Shift extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function isOvernight(): bool
    {
        return $this->end_time <= $this->start_time;
    }

    public function startsAt(Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($this->start_time);
    }

    public function endsAt(Carbon $date): Carbon
    {
        $end = $date->copy()->setTimeFromTimeString($this->end_time);

        return $this->isOvernight() ? $end->addDay() : $end;
    }

    public function durationMinutes(): int
    {
        $date = Carbon::today();

        return (int) $this->startsAt($date)->diffInMinutes($this->endsAt($date)) - $this->break_minutes;
    }

    public function label(): string
    {
        return $this->name.' ('.substr($this->start_time, 0, 5).' - '.substr($this->end_time, 0, 5).')';
    }
}
