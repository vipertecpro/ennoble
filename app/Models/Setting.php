<?php

namespace App\Models;

use App\Enums\ThemePreference;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'theme_preference',
        'sound_enabled',
        'haptics_enabled',
        'reduced_motion',
        'daily_reminder_enabled',
        'accessibility_preferences',
    ];

    protected $attributes = [
        'theme_preference' => 'system',
        'sound_enabled' => true,
        'haptics_enabled' => true,
        'reduced_motion' => false,
        'daily_reminder_enabled' => false,
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    protected function casts(): array
    {
        return [
            'theme_preference' => ThemePreference::class,
            'sound_enabled' => 'boolean',
            'haptics_enabled' => 'boolean',
            'reduced_motion' => 'boolean',
            'daily_reminder_enabled' => 'boolean',
            'accessibility_preferences' => 'array',
        ];
    }
}
