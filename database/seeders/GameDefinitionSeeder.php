<?php

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Enums\SkillKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $games = [
            [
                'type' => GameType::SignalShift->value,
                'slug' => 'signal-shift',
                'name' => 'Signal Shift',
                'description' => 'Respond accurately as visual rules shift.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 1,
                'skill_keys' => [
                    SkillKey::Focus->value,
                    SkillKey::Speed->value,
                    SkillKey::Precision->value,
                    SkillKey::Adaptability->value,
                ],
                'configuration' => ['content_version' => 1, 'mistake_allowance' => 3],
            ],
            [
                'type' => GameType::ClearThought->value,
                'slug' => 'clear-thought',
                'name' => 'Clear Thought',
                'description' => 'Strengthen clear, concise written reasoning.',
                'status' => GameStatus::Playable->value,
                'sort_order' => 2,
                'skill_keys' => [
                    SkillKey::Clarity->value,
                    SkillKey::Structure->value,
                    SkillKey::CriticalReading->value,
                ],
                'configuration' => ['content_version' => 1, 'hint_penalty' => 50],
            ],
        ];

        $rows = array_map(static fn (array $game): array => [
            ...$game,
            'skill_keys' => json_encode($game['skill_keys'], JSON_THROW_ON_ERROR),
            'configuration' => json_encode($game['configuration'], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ], $games);

        DB::table('games')->upsert(
            $rows,
            ['slug'],
            ['type', 'name', 'description', 'status', 'sort_order', 'skill_keys', 'configuration', 'updated_at'],
        );
    }
}
