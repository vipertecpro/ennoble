# Contributing game content

Ennoble is **100% offline**, so all game content is bundled into the app and
ships to players through app-store updates. Content lives as **plain PHP data
files** in [`resources/game-content/`](resources/game-content) — no database, no
code. Edit a file, open a pull request, and once it's merged into a release
every player gets it on their next update.

These files are compiled into OPcache once and read from memory, so adding
content never slows the game down.

## How it works

- Each file `resources/game-content/<name>.php` `return`s a PHP array of content.
- Games read it at runtime through `App\Domain\Games\Content\GameContentRepository`
  (memoized — the file is only loaded once per run).
- `tests/Unit/GameContentTest.php` validates every file on CI, so a malformed
  edit fails checks instead of shipping.

You do **not** need a database migration to add or remove questions — only to add
a whole new game or change difficulty settings.

## Word Match — `resources/game-content/word-match.php`

Vocabulary, split into three difficulty bands: `beginner`, `intermediate`,
`advanced`. Each band is a list of entries:

```php
['prompt' => 'Vast', 'relation' => 'synonym', 'answer' => 'Immense', 'distractors' => ['Narrow', 'Cramped', 'Tiny']],
```

Rules (enforced by the test):

- `relation` is `'synonym'` or `'antonym'`.
- `answer` is the single correct option; `distractors` is **exactly three**
  plausible-but-wrong options.
- No distractor may equal the answer, and the three distractors must be distinct.
- Prompts must be unique within a band; keep at least 8 entries per band.
- Choose distractors that are clearly wrong to a careful reader — avoid near-synonyms
  of the answer.

## Adding to other games

- **Quick Math** and **Recall** are *procedural* — they generate problems from
  difficulty settings (operations, number ranges, grid size), not a fixed list,
  so there's no content file to edit. To tune them, adjust the level
  configuration in `database/seeders/WordMatchQuickMathSeeder.php` (this is a
  structural change and needs a new seed migration — see below).
- A **future content-based game** gets its own `resources/game-content/<game>.php`
  file and a matching validation block in `GameContentTest.php`.

## Structural changes (games & difficulty)

Adding a new game, a new difficulty level, or changing round counts / timers is a
**structural** change: edit the seeder and add a new dated migration in
`database/migrations/` that re-runs the seeder (the seeder upserts, so existing
rows are untouched). This is required because on-device seeding only happens
through migrations, and an already-applied migration will not re-run.

## Before you open a PR

```bash
php artisan test --filter=GameContent   # validate content
vendor/bin/pint --dirty                 # format any PHP you touched
```
