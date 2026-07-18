<?php

namespace Database\Seeders;

use App\Enums\ClearThoughtMode;
use App\Enums\Difficulty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClearThoughtChallengeSeeder extends Seeder
{
    /**
     * Seed the bundled original Clear Thought editorial content idempotently.
     */
    public function run(): void
    {
        $game = DB::table('games')->where('slug', 'clear-thought')->first();

        if ($game === null) {
            throw new RuntimeException('The Clear Thought game definition must be seeded first.');
        }

        $levels = DB::table('game_levels')
            ->where('game_id', $game->id)
            ->get()
            ->keyBy('difficulty');
        $now = now();
        $rows = [];

        foreach ($this->challenges() as $challenge) {
            $level = $levels->get($challenge['difficulty']->value);

            if ($level === null) {
                throw new RuntimeException('Clear Thought levels must be seeded first.');
            }

            $rows[] = [
                'game_id' => $game->id,
                'game_level_id' => $level->id,
                'slug' => $challenge['slug'],
                'mode' => $challenge['mode']->value,
                'content_version' => 1,
                'prompt' => $challenge['prompt'],
                'payload' => json_encode($challenge['payload'], JSON_THROW_ON_ERROR),
                'accepted_answers' => json_encode($challenge['accepted'], JSON_THROW_ON_ERROR),
                'explanation' => $challenge['explanation'],
                'hint' => $challenge['hint'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('challenges')->upsert(
            $rows,
            ['game_id', 'slug'],
            [
                'game_level_id',
                'mode',
                'content_version',
                'prompt',
                'payload',
                'accepted_answers',
                'explanation',
                'hint',
                'is_active',
                'updated_at',
            ],
        );
    }

    /**
     * @return list<array{
     *     difficulty: Difficulty,
     *     slug: string,
     *     mode: ClearThoughtMode,
     *     prompt: string,
     *     payload: array<string, mixed>,
     *     accepted: list<mixed>,
     *     explanation: string,
     *     hint: string
     * }>
     */
    private function challenges(): array
    {
        return [
            // Beginner — Direct
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-noon-meeting',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Trim this message so only the fact remains.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'The'],
                        ['id' => 'w2', 'text' => 'meeting'],
                        ['id' => 'w3', 'text' => 'is'],
                        ['id' => 'w4', 'text' => 'basically'],
                        ['id' => 'w5', 'text' => 'just'],
                        ['id' => 'w6', 'text' => 'at'],
                        ['id' => 'w7', 'text' => 'noon'],
                        ['id' => 'w8', 'text' => 'today'],
                    ],
                    'answer_text' => 'The meeting is at noon today.',
                ],
                'accepted' => [['w4', 'w5']],
                'explanation' => 'Softeners like “basically” and “just” blur a plain fact without adding meaning.',
                'hint' => 'Two softeners add nothing to the time.',
            ],
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-market-open',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'A neighbor asks when the market opens. Which reply is clearest?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'It could be said that the market tends to open at around eight.'],
                        ['id' => 'b', 'text' => 'The market opens at eight.'],
                        ['id' => 'c', 'text' => 'Opening of the market happens to take place at eight in the morning.'],
                    ],
                    'answer_text' => 'The market opens at eight.',
                ],
                'accepted' => ['b'],
                'explanation' => 'A direct subject and verb deliver the fact without hedging or ceremony.',
                'hint' => 'Fewest words, same fact.',
            ],
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-water-plants',
                'mode' => ClearThoughtMode::ReorderSentence,
                'prompt' => 'Rebuild this request so it reads naturally.',
                'payload' => [
                    'segments' => [
                        ['id' => 's1', 'text' => 'water the plants'],
                        ['id' => 's2', 'text' => 'before you leave'],
                        ['id' => 's3', 'text' => 'Please'],
                    ],
                    'answer_text' => 'Please water the plants before you leave.',
                ],
                'accepted' => [['s3', 's1', 's2']],
                'explanation' => 'Requests read best ask-first: the courtesy, the action, then the timing.',
                'hint' => 'Start with the polite word.',
            ],
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-soup-opinion',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Cut what this opinion repeats.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'In'],
                        ['id' => 'w2', 'text' => 'my'],
                        ['id' => 'w3', 'text' => 'own'],
                        ['id' => 'w4', 'text' => 'personal'],
                        ['id' => 'w5', 'text' => 'opinion,'],
                        ['id' => 'w6', 'text' => 'the'],
                        ['id' => 'w7', 'text' => 'soup'],
                        ['id' => 'w8', 'text' => 'needs'],
                        ['id' => 'w9', 'text' => 'salt'],
                    ],
                    'answer_text' => 'In my opinion, the soup needs salt.',
                ],
                'accepted' => [['w3', 'w4']],
                'explanation' => '“Own” and “personal” repeat what “my opinion” already says.',
                'hint' => 'An opinion is already personal.',
            ],
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-keys-note',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'Which note leaves no doubt about the keys?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'The keys are on the hook by the door.'],
                        ['id' => 'b', 'text' => 'The keys should be somewhere around the door area, probably.'],
                        ['id' => 'c', 'text' => 'Regarding the keys, their location is the general vicinity of the door.'],
                    ],
                    'answer_text' => 'The keys are on the hook by the door.',
                ],
                'accepted' => ['a'],
                'explanation' => 'A concrete place beats vague hedges like “somewhere around.”',
                'hint' => 'Look for an exact place.',
            ],
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-oven-timer',
                'mode' => ClearThoughtMode::ReorderSentence,
                'prompt' => 'Rebuild this kitchen note so it reads naturally.',
                'payload' => [
                    'segments' => [
                        ['id' => 's1', 'text' => 'turn off the oven'],
                        ['id' => 's2', 'text' => 'When the timer rings,'],
                        ['id' => 's3', 'text' => 'and let it cool'],
                    ],
                    'answer_text' => 'When the timer rings, turn off the oven and let it cool.',
                ],
                'accepted' => [['s2', 's1', 's3']],
                'explanation' => 'Time cue first, then the action, then what follows.',
                'hint' => 'Lead with the moment.',
            ],
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-early-bus',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Let the verb do the work.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'She'],
                        ['id' => 'w2', 'text' => 'quickly'],
                        ['id' => 'w3', 'text' => 'ran'],
                        ['id' => 'w4', 'text' => 'fast'],
                        ['id' => 'w5', 'text' => 'to'],
                        ['id' => 'w6', 'text' => 'catch'],
                        ['id' => 'w7', 'text' => 'the'],
                        ['id' => 'w8', 'text' => 'early'],
                        ['id' => 'w9', 'text' => 'bus'],
                    ],
                    'answer_text' => 'She ran to catch the early bus.',
                ],
                'accepted' => [['w2', 'w4']],
                'explanation' => '“Quickly” and “fast” restate the speed that “ran” already shows.',
                'hint' => 'Running is already fast.',
            ],
            [
                'difficulty' => Difficulty::Beginner,
                'slug' => 'ct-direct-umbrella',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'Which reminder is easiest to act on?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'Don’t forget about maybe bringing the umbrella if rain seems possible.'],
                        ['id' => 'b', 'text' => 'Take the umbrella; rain is likely.'],
                        ['id' => 'c', 'text' => 'It would be advisable to consider the taking of an umbrella today.'],
                    ],
                    'answer_text' => 'Take the umbrella; rain is likely.',
                ],
                'accepted' => ['b'],
                'explanation' => 'A short instruction with one reason is easier to act on than padded advice.',
                'hint' => 'Command plus reason.',
            ],

            // Intermediate — Refined
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-schedule-update',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Trim this status line for a busy reader.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'We'],
                        ['id' => 'w2', 'text' => 'are'],
                        ['id' => 'w3', 'text' => 'currently'],
                        ['id' => 'w4', 'text' => 'in'],
                        ['id' => 'w5', 'text' => 'the'],
                        ['id' => 'w6', 'text' => 'process'],
                        ['id' => 'w7', 'text' => 'of'],
                        ['id' => 'w8', 'text' => 'updating'],
                        ['id' => 'w9', 'text' => 'the'],
                        ['id' => 'w10', 'text' => 'schedule'],
                        ['id' => 'w11', 'text' => 'right'],
                        ['id' => 'w12', 'text' => 'now'],
                    ],
                    'answer_text' => 'We are updating the schedule.',
                ],
                'accepted' => [['w3', 'w4', 'w5', 'w6', 'w7', 'w11', 'w12']],
                'explanation' => '“Currently,” “in the process of,” and “right now” all repeat the present tense the verb already carries.',
                'hint' => 'The verb already shows it is happening now.',
            ],
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-demo-feedback',
                'mode' => ClearThoughtMode::ReorderSentence,
                'prompt' => 'Rebuild this recap in the order a reader expects.',
                'payload' => [
                    'segments' => [
                        ['id' => 's1', 'text' => 'the team gathered feedback'],
                        ['id' => 's2', 'text' => 'After the demo,'],
                        ['id' => 's3', 'text' => 'and shipped a fix'],
                    ],
                    'answer_text' => 'After the demo, the team gathered feedback and shipped a fix.',
                ],
                'accepted' => [['s2', 's1', 's3']],
                'explanation' => 'Context, action, result — the order a reader expects.',
                'hint' => 'Set the scene before the actions.',
            ],
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-order-delay',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'Which status update respects the reader’s time?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'It has come to our attention that there may be delays potentially affecting some orders.'],
                        ['id' => 'b', 'text' => 'Some orders are delayed by two days; we will email affected customers today.'],
                        ['id' => 'c', 'text' => 'Delays, in terms of orders, are a thing that is currently happening.'],
                    ],
                    'answer_text' => 'Some orders are delayed by two days; we will email affected customers today.',
                ],
                'accepted' => ['b'],
                'explanation' => 'The clear version states the delay, its size, and the next step — nothing else.',
                'hint' => 'Facts plus the next step.',
            ],
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-unanimous-vote',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Remove the degrees from words that have none.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'The'],
                        ['id' => 'w2', 'text' => 'final'],
                        ['id' => 'w3', 'text' => 'outcome'],
                        ['id' => 'w4', 'text' => 'of'],
                        ['id' => 'w5', 'text' => 'the'],
                        ['id' => 'w6', 'text' => 'vote'],
                        ['id' => 'w7', 'text' => 'was'],
                        ['id' => 'w8', 'text' => 'completely'],
                        ['id' => 'w9', 'text' => 'unanimous'],
                        ['id' => 'w10', 'text' => 'in'],
                        ['id' => 'w11', 'text' => 'the'],
                        ['id' => 'w12', 'text' => 'end'],
                    ],
                    'answer_text' => 'The outcome of the vote was unanimous.',
                ],
                'accepted' => [['w2', 'w8', 'w10', 'w11', 'w12']],
                'explanation' => '“Final,” “completely,” and “in the end” add intensity to words that cannot be partial.',
                'hint' => 'Unanimous cannot be partial.',
            ],
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-ridge-path',
                'mode' => ClearThoughtMode::ReorderSentence,
                'prompt' => 'Rebuild this trail note so the logic stays visible.',
                'payload' => [
                    'segments' => [
                        ['id' => 's1', 'text' => 'we took the ridge path'],
                        ['id' => 's2', 'text' => 'Because the trail was flooded,'],
                        ['id' => 's3', 'text' => 'and reached camp by dusk'],
                    ],
                    'answer_text' => 'Because the trail was flooded, we took the ridge path and reached camp by dusk.',
                ],
                'accepted' => [['s2', 's1', 's3']],
                'explanation' => 'Cause, choice, outcome keeps the reasoning readable in one pass.',
                'hint' => 'Reason first.',
            ],
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-overbooked-room',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'Which apology reads as sincere?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'Mistakes were made in the handling of your booking.'],
                        ['id' => 'b', 'text' => 'We overbooked your room, and we’re sorry; tonight’s stay is free.'],
                        ['id' => 'c', 'text' => 'We regret any inconvenience that may possibly have occurred.'],
                    ],
                    'answer_text' => 'We overbooked your room, and we’re sorry; tonight’s stay is free.',
                ],
                'accepted' => ['b'],
                'explanation' => 'Naming the mistake and the remedy is clearer than passive distance.',
                'hint' => 'Ownership, then the remedy.',
            ],
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-rsvp',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Halve the doubled ideas.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'Each'],
                        ['id' => 'w2', 'text' => 'and'],
                        ['id' => 'w3', 'text' => 'every'],
                        ['id' => 'w4', 'text' => 'attendee'],
                        ['id' => 'w5', 'text' => 'should'],
                        ['id' => 'w6', 'text' => 'RSVP'],
                        ['id' => 'w7', 'text' => 'in'],
                        ['id' => 'w8', 'text' => 'advance'],
                        ['id' => 'w9', 'text' => 'beforehand'],
                    ],
                    'answer_text' => 'Every attendee should RSVP in advance.',
                ],
                'accepted' => [['w1', 'w2', 'w9'], ['w1', 'w2', 'w7', 'w8', 'w9']],
                'explanation' => '“Each and every” doubles one idea, and a reply is early by definition.',
                'hint' => 'Pairs that mean the same thing can lose a half.',
            ],
            [
                'difficulty' => Difficulty::Intermediate,
                'slug' => 'ct-refined-demo-order',
                'mode' => ClearThoughtMode::ReorderSentence,
                'prompt' => 'Rebuild this advice so its purpose leads.',
                'payload' => [
                    'segments' => [
                        ['id' => 's1', 'text' => 'show the result first,'],
                        ['id' => 's2', 'text' => 'To keep the demo short,'],
                        ['id' => 's3', 'text' => 'then the method'],
                    ],
                    'answer_text' => 'To keep the demo short, show the result first, then the method.',
                ],
                'accepted' => [['s2', 's1', 's3']],
                'explanation' => 'Purpose first tells the reader why the order that follows matters.',
                'hint' => 'Purpose, then priorities.',
            ],

            // Advanced — Exact
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-report-summary',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Edit this line as a careful editor would.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'The'],
                        ['id' => 'w2', 'text' => 'report'],
                        ['id' => 'w3', 'text' => 'summarizes'],
                        ['id' => 'w4', 'text' => 'briefly,'],
                        ['id' => 'w5', 'text' => 'in'],
                        ['id' => 'w6', 'text' => 'short,'],
                        ['id' => 'w7', 'text' => 'the'],
                        ['id' => 'w8', 'text' => 'quarter’s'],
                        ['id' => 'w9', 'text' => 'key'],
                        ['id' => 'w10', 'text' => 'results'],
                        ['id' => 'w11', 'text' => 'and'],
                        ['id' => 'w12', 'text' => 'findings'],
                    ],
                    'answer_text' => 'The report summarizes the quarter’s key results.',
                ],
                'accepted' => [['w4', 'w5', 'w6', 'w11', 'w12']],
                'explanation' => '“Summarizes” already contains brevity, and “findings” restates “results.”',
                'hint' => 'A summary is already brief.',
            ],
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-grant-line',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'Which sentence would you keep in a grant application?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'Our program, which is innovative in many exciting ways, could be argued to improve literacy outcomes.'],
                        ['id' => 'b', 'text' => 'Our program raised third-grade reading scores by 12% in one year.'],
                        ['id' => 'c', 'text' => 'Literacy improvement is something our innovative program is in the business of driving.'],
                    ],
                    'answer_text' => 'Our program raised third-grade reading scores by 12% in one year.',
                ],
                'accepted' => ['b'],
                'explanation' => 'One measured result carries more force than any self-description.',
                'hint' => 'Evidence outranks adjectives.',
            ],
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-prototype',
                'mode' => ClearThoughtMode::ReorderSentence,
                'prompt' => 'Rebuild this update so the turn lands where it should.',
                'payload' => [
                    'segments' => [
                        ['id' => 's1', 'text' => 'the third test confirmed the design,'],
                        ['id' => 's2', 'text' => 'Although the prototype failed twice,'],
                        ['id' => 's3', 'text' => 'so production begins Monday'],
                    ],
                    'answer_text' => 'Although the prototype failed twice, the third test confirmed the design, so production begins Monday.',
                ],
                'accepted' => [['s2', 's1', 's3']],
                'explanation' => 'Concession first frames the success; the consequence lands last.',
                'hint' => 'Concession, turn, consequence.',
            ],
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-consolidation',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Strip every word that restates a neighbor.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'In'],
                        ['id' => 'w2', 'text' => 'order'],
                        ['id' => 'w3', 'text' => 'to'],
                        ['id' => 'w4', 'text' => 'fully'],
                        ['id' => 'w5', 'text' => 'maximize'],
                        ['id' => 'w6', 'text' => 'efficiency,'],
                        ['id' => 'w7', 'text' => 'we'],
                        ['id' => 'w8', 'text' => 'consolidated'],
                        ['id' => 'w9', 'text' => 'together'],
                        ['id' => 'w10', 'text' => 'the'],
                        ['id' => 'w11', 'text' => 'two'],
                        ['id' => 'w12', 'text' => 'separate'],
                        ['id' => 'w13', 'text' => 'duplicate'],
                        ['id' => 'w14', 'text' => 'systems'],
                    ],
                    'answer_text' => 'To maximize efficiency, we consolidated the two duplicate systems.',
                ],
                'accepted' => [['w1', 'w2', 'w4', 'w9', 'w12']],
                'explanation' => '“In order to,” “fully,” “together,” and “separate” each restate a word beside them.',
                'hint' => 'Maximize is already full; consolidation already joins.',
            ],
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-client-close',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'Which closing line best ends a difficult client email?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'We hope this goes some way toward addressing the various concerns you may have raised.'],
                        ['id' => 'b', 'text' => 'If Thursday’s fix doesn’t resolve the issue, reply and I’ll call you the same day.'],
                        ['id' => 'c', 'text' => 'Assuring you of our fullest attention at all times, we remain committed to excellence.'],
                    ],
                    'answer_text' => 'If Thursday’s fix doesn’t resolve the issue, reply and I’ll call you the same day.',
                ],
                'accepted' => ['b'],
                'explanation' => 'A concrete next step with a deadline is the only version a reader can hold you to.',
                'hint' => 'A testable promise.',
            ],
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-sofa-sequence',
                'mode' => ClearThoughtMode::ReorderSentence,
                'prompt' => 'Rebuild this plan; its own markers show the order.',
                'payload' => [
                    'segments' => [
                        ['id' => 's1', 'text' => 'then order the sofa,'],
                        ['id' => 's2', 'text' => 'First measure the doorway,'],
                        ['id' => 's3', 'text' => 'and only then schedule delivery'],
                    ],
                    'answer_text' => 'First measure the doorway, then order the sofa, and only then schedule delivery.',
                ],
                'accepted' => [['s2', 's1', 's3']],
                'explanation' => '“First,” “then,” and “only then” dictate the sentence’s shape by themselves.',
                'hint' => 'The sequence words carry the order.',
            ],
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-deadline',
                'mode' => ClearThoughtMode::RemoveUnnecessaryWords,
                'prompt' => 'Reduce this reminder to its only fact.',
                'payload' => [
                    'words' => [
                        ['id' => 'w1', 'text' => 'It'],
                        ['id' => 'w2', 'text' => 'is'],
                        ['id' => 'w3', 'text' => 'important'],
                        ['id' => 'w4', 'text' => 'to'],
                        ['id' => 'w5', 'text' => 'note'],
                        ['id' => 'w6', 'text' => 'that'],
                        ['id' => 'w7', 'text' => 'the'],
                        ['id' => 'w8', 'text' => 'deadline,'],
                        ['id' => 'w9', 'text' => 'as'],
                        ['id' => 'w10', 'text' => 'previously'],
                        ['id' => 'w11', 'text' => 'mentioned'],
                        ['id' => 'w12', 'text' => 'before,'],
                        ['id' => 'w13', 'text' => 'is'],
                        ['id' => 'w14', 'text' => 'Friday'],
                    ],
                    'answer_text' => 'The deadline is Friday.',
                ],
                'accepted' => [['w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w9', 'w10', 'w11', 'w12']],
                'explanation' => 'Announcing importance and repeating the reminder both delay the only fact.',
                'hint' => 'The fact needs four words.',
            ],
            [
                'difficulty' => Difficulty::Advanced,
                'slug' => 'ct-exact-latency',
                'mode' => ClearThoughtMode::ChooseClearestSentence,
                'prompt' => 'Which definition belongs in a glossary?',
                'payload' => [
                    'options' => [
                        ['id' => 'a', 'text' => 'Latency is basically when things take kind of a while to happen.'],
                        ['id' => 'b', 'text' => 'Latency: the delay between a request and its response.'],
                        ['id' => 'c', 'text' => 'Latency is a phenomenon characterized by the presence of temporal delay in systems.'],
                    ],
                    'answer_text' => 'Latency: the delay between a request and its response.',
                ],
                'accepted' => ['b'],
                'explanation' => 'A glossary defines with the fewest exact words — no hedges, no ornament.',
                'hint' => 'Term, colon, essence.',
            ],
        ];
    }
}
