<?php

/**
 * Word Match content — the curated vocabulary bank, one section per difficulty
 * band. This is a plain PHP data file (no code, just data): edit the arrays to
 * add or remove entries, then open a pull request. It is compiled into OPcache
 * once and read from memory thereafter, so lookups are free at runtime, and it
 * ships inside the app bundle for full offline play.
 *
 * Each entry: a prompt word, one relation ('synonym' | 'antonym'), the single
 * correct answer, and exactly three plausible distractors. See CONTENT.md for
 * the format and rules (validated by tests/Feature/GameContentTest.php).
 *
 * @return array<string, list<array{prompt: string, relation: string, answer: string, distractors: list<string>}>>
 */

return [
    'beginner' => [
        ['prompt' => 'Rapid', 'relation' => 'synonym', 'answer' => 'Fast', 'distractors' => ['Loud', 'Heavy', 'Calm']],
        ['prompt' => 'Happy', 'relation' => 'synonym', 'answer' => 'Glad', 'distractors' => ['Angry', 'Tired', 'Hungry']],
        ['prompt' => 'Big', 'relation' => 'synonym', 'answer' => 'Large', 'distractors' => ['Tiny', 'Thin', 'Short']],
        ['prompt' => 'Cold', 'relation' => 'antonym', 'answer' => 'Hot', 'distractors' => ['Icy', 'Wet', 'Damp']],
        ['prompt' => 'Begin', 'relation' => 'synonym', 'answer' => 'Start', 'distractors' => ['Finish', 'Pause', 'Delay']],
        ['prompt' => 'Silent', 'relation' => 'synonym', 'answer' => 'Quiet', 'distractors' => ['Loud', 'Busy', 'Bright']],
        ['prompt' => 'Empty', 'relation' => 'antonym', 'answer' => 'Full', 'distractors' => ['Hollow', 'Bare', 'Open']],
        ['prompt' => 'Simple', 'relation' => 'synonym', 'answer' => 'Easy', 'distractors' => ['Hard', 'Complex', 'Tricky']],
        ['prompt' => 'Bright', 'relation' => 'antonym', 'answer' => 'Dark', 'distractors' => ['Shiny', 'Vivid', 'Bold']],
        ['prompt' => 'Angry', 'relation' => 'synonym', 'answer' => 'Mad', 'distractors' => ['Happy', 'Calm', 'Kind']],
        ['prompt' => 'Wealthy', 'relation' => 'synonym', 'answer' => 'Rich', 'distractors' => ['Poor', 'Cheap', 'Plain']],
        ['prompt' => 'Ancient', 'relation' => 'synonym', 'answer' => 'Old', 'distractors' => ['New', 'Young', 'Fresh']],
        ['prompt' => 'Brave', 'relation' => 'synonym', 'answer' => 'Bold', 'distractors' => ['Shy', 'Weak', 'Scared']],
        ['prompt' => 'Tidy', 'relation' => 'synonym', 'answer' => 'Neat', 'distractors' => ['Messy', 'Dirty', 'Rough']],
        ['prompt' => 'Fix', 'relation' => 'synonym', 'answer' => 'Repair', 'distractors' => ['Break', 'Damage', 'Ruin']],
        ['prompt' => 'Enormous', 'relation' => 'synonym', 'answer' => 'Huge', 'distractors' => ['Small', 'Narrow', 'Slight']],
        ['prompt' => 'Rare', 'relation' => 'antonym', 'answer' => 'Common', 'distractors' => ['Scarce', 'Unique', 'Odd']],
        ['prompt' => 'Calm', 'relation' => 'antonym', 'answer' => 'Nervous', 'distractors' => ['Peaceful', 'Still', 'Quiet']],
        ['prompt' => 'Rise', 'relation' => 'antonym', 'answer' => 'Fall', 'distractors' => ['Climb', 'Ascend', 'Lift']],
        ['prompt' => 'Reply', 'relation' => 'synonym', 'answer' => 'Answer', 'distractors' => ['Ask', 'Question', 'Ignore']],
    ],
    'intermediate' => [
        ['prompt' => 'Abundant', 'relation' => 'synonym', 'answer' => 'Plentiful', 'distractors' => ['Scarce', 'Meager', 'Sparse']],
        ['prompt' => 'Reluctant', 'relation' => 'synonym', 'answer' => 'Unwilling', 'distractors' => ['Eager', 'Keen', 'Willing']],
        ['prompt' => 'Genuine', 'relation' => 'synonym', 'answer' => 'Authentic', 'distractors' => ['Fake', 'False', 'Forged']],
        ['prompt' => 'Fragile', 'relation' => 'synonym', 'answer' => 'Delicate', 'distractors' => ['Sturdy', 'Tough', 'Solid']],
        ['prompt' => 'Hostile', 'relation' => 'antonym', 'answer' => 'Friendly', 'distractors' => ['Aggressive', 'Bitter', 'Angry']],
        ['prompt' => 'Conceal', 'relation' => 'synonym', 'answer' => 'Hide', 'distractors' => ['Reveal', 'Expose', 'Show']],
        ['prompt' => 'Deliberate', 'relation' => 'synonym', 'answer' => 'Intentional', 'distractors' => ['Accidental', 'Random', 'Careless']],
        ['prompt' => 'Vast', 'relation' => 'synonym', 'answer' => 'Immense', 'distractors' => ['Narrow', 'Cramped', 'Tiny']],
        ['prompt' => 'Trivial', 'relation' => 'synonym', 'answer' => 'Minor', 'distractors' => ['Crucial', 'Vital', 'Major']],
        ['prompt' => 'Prohibit', 'relation' => 'synonym', 'answer' => 'Forbid', 'distractors' => ['Allow', 'Permit', 'Enable']],
        ['prompt' => 'Diligent', 'relation' => 'synonym', 'answer' => 'Hardworking', 'distractors' => ['Lazy', 'Idle', 'Careless']],
        ['prompt' => 'Scarce', 'relation' => 'antonym', 'answer' => 'Plentiful', 'distractors' => ['Rare', 'Limited', 'Sparse']],
        ['prompt' => 'Candid', 'relation' => 'synonym', 'answer' => 'Frank', 'distractors' => ['Secretive', 'Evasive', 'Vague']],
        ['prompt' => 'Obscure', 'relation' => 'antonym', 'answer' => 'Clear', 'distractors' => ['Vague', 'Hidden', 'Murky']],
        ['prompt' => 'Lenient', 'relation' => 'synonym', 'answer' => 'Tolerant', 'distractors' => ['Strict', 'Harsh', 'Severe']],
        ['prompt' => 'Robust', 'relation' => 'synonym', 'answer' => 'Strong', 'distractors' => ['Frail', 'Weak', 'Feeble']],
        ['prompt' => 'Novel', 'relation' => 'synonym', 'answer' => 'New', 'distractors' => ['Ancient', 'Stale', 'Usual']],
        ['prompt' => 'Feasible', 'relation' => 'synonym', 'answer' => 'Possible', 'distractors' => ['Impossible', 'Unlikely', 'Absurd']],
        ['prompt' => 'Meticulous', 'relation' => 'synonym', 'answer' => 'Careful', 'distractors' => ['Sloppy', 'Hasty', 'Reckless']],
        ['prompt' => 'Vivid', 'relation' => 'antonym', 'answer' => 'Dull', 'distractors' => ['Bright', 'Bold', 'Clear']],
    ],
    'advanced' => [
        ['prompt' => 'Ephemeral', 'relation' => 'synonym', 'answer' => 'Fleeting', 'distractors' => ['Eternal', 'Lasting', 'Permanent']],
        ['prompt' => 'Ubiquitous', 'relation' => 'synonym', 'answer' => 'Widespread', 'distractors' => ['Rare', 'Scarce', 'Absent']],
        ['prompt' => 'Ameliorate', 'relation' => 'synonym', 'answer' => 'Improve', 'distractors' => ['Worsen', 'Damage', 'Aggravate']],
        ['prompt' => 'Reticent', 'relation' => 'synonym', 'answer' => 'Reserved', 'distractors' => ['Talkative', 'Candid', 'Blunt']],
        ['prompt' => 'Pragmatic', 'relation' => 'synonym', 'answer' => 'Practical', 'distractors' => ['Idealistic', 'Fanciful', 'Naive']],
        ['prompt' => 'Cacophony', 'relation' => 'antonym', 'answer' => 'Harmony', 'distractors' => ['Noise', 'Racket', 'Clamor']],
        ['prompt' => 'Tenuous', 'relation' => 'synonym', 'answer' => 'Weak', 'distractors' => ['Strong', 'Robust', 'Solid']],
        ['prompt' => 'Verbose', 'relation' => 'antonym', 'answer' => 'Concise', 'distractors' => ['Wordy', 'Lengthy', 'Rambling']],
        ['prompt' => 'Austere', 'relation' => 'synonym', 'answer' => 'Severe', 'distractors' => ['Lavish', 'Ornate', 'Lenient']],
        ['prompt' => 'Placate', 'relation' => 'synonym', 'answer' => 'Appease', 'distractors' => ['Provoke', 'Enrage', 'Annoy']],
        ['prompt' => 'Innocuous', 'relation' => 'synonym', 'answer' => 'Harmless', 'distractors' => ['Toxic', 'Harmful', 'Lethal']],
        ['prompt' => 'Prudent', 'relation' => 'synonym', 'answer' => 'Cautious', 'distractors' => ['Reckless', 'Rash', 'Careless']],
        ['prompt' => 'Lucid', 'relation' => 'synonym', 'answer' => 'Clear', 'distractors' => ['Murky', 'Vague', 'Obscure']],
        ['prompt' => 'Superfluous', 'relation' => 'synonym', 'answer' => 'Excessive', 'distractors' => ['Essential', 'Necessary', 'Vital']],
        ['prompt' => 'Zealous', 'relation' => 'synonym', 'answer' => 'Passionate', 'distractors' => ['Apathetic', 'Indifferent', 'Bored']],
        ['prompt' => 'Candor', 'relation' => 'antonym', 'answer' => 'Deceit', 'distractors' => ['Honesty', 'Frankness', 'Openness']],
        ['prompt' => 'Mitigate', 'relation' => 'synonym', 'answer' => 'Lessen', 'distractors' => ['Intensify', 'Worsen', 'Amplify']],
        ['prompt' => 'Obstinate', 'relation' => 'synonym', 'answer' => 'Stubborn', 'distractors' => ['Flexible', 'Yielding', 'Compliant']],
        ['prompt' => 'Eloquent', 'relation' => 'synonym', 'answer' => 'Articulate', 'distractors' => ['Mumbling', 'Halting', 'Clumsy']],
        ['prompt' => 'Diligence', 'relation' => 'antonym', 'answer' => 'Laziness', 'distractors' => ['Effort', 'Care', 'Rigor']],
    ],
];
