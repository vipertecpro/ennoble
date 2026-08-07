<?php

use Database\Seeders\WordMatchQuickMathSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Re-point the `vertex` game at its rebuilt concept, Barrage.
     *
     * The slug is unchanged on purpose, so recorded sessions and best scores
     * stay attached to the game the player already knows. What DID change is
     * its name and, more importantly, every level's configuration — the old
     * keys (flight_ms, go_ratio, key_hold) describe a game that no longer
     * exists, and a device still holding them would generate empty waves.
     *
     * The earlier games migrations already ran on installed devices, so they
     * won't re-invoke the seeder; this migration does. The seeder upserts and
     * lists `name`, `description` and `configuration` among its update columns,
     * so existing rows are corrected rather than duplicated.
     */
    public function up(): void
    {
        (new WordMatchQuickMathSeeder)->run();
    }

    public function down(): void
    {
        // Irreversible by design: the previous configuration described a game
        // concept that no longer has any code to run it.
    }
};
