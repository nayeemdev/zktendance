<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use Auditable, HasFactory;

    public const MODE_PULL = 'pull';

    public const MODE_PUSH = 'push';

    public const MODES = [
        self::MODE_PULL => 'Pull (server connects to device on port 4370)',
        self::MODE_PUSH => 'Push (device sends data to server, ADMS)',
    ];

    public const MODELS = [
        'K40' => 'pull', 'K50' => 'pull', 'K14' => 'pull',
        'MB10' => 'pull', 'MB20' => 'pull', 'MB160' => 'pull', 'MB360' => 'pull', 'MB460' => 'pull', 'MB560' => 'push',
        'iFace702' => 'pull', 'iFace880' => 'pull',
        'F18' => 'pull', 'F22' => 'push', 'X628' => 'pull', 'U160' => 'pull', 'UFace800' => 'push',
        'SpeedFace-V5L' => 'push', 'SpeedFace-V4L' => 'push', 'ProFace X' => 'push', 'Horus E1' => 'push',
        'SenseFace 2A' => 'push', 'SenseFace 4A' => 'push', 'G4' => 'push',
        'Other' => 'pull',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'offline_notified_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function isPush(): bool
    {
        return $this->connection_mode === self::MODE_PUSH;
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subMinutes(10));
    }
}
