<?php

namespace App\Domain\Games\Stack;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline piece sequence for Stack.
 *
 * Pieces come from a SHUFFLED BAG, not from independent random draws. With
 * independent draws a player can wait twenty pieces for the one they need, or
 * be handed four S pieces in a row — both are miserable and neither is skill.
 * A bag of all seven, shuffled and dealt out before the next bag starts, keeps
 * the sequence unpredictable while bounding the wait for any piece to at most
 * twelve.
 *
 * Everything derives from a stable seed, so a session replays identically with
 * no bundled content.
 */
final class StackGenerator
{
    /**
     * @return list<string>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $sequence = [];
        $bagIndex = 0;

        while (count($sequence) < $count) {
            foreach ($this->bag($seed.':bag:'.$bagIndex) as $piece) {
                $sequence[] = $piece;

                if (count($sequence) === $count) {
                    break 2;
                }
            }

            $bagIndex++;
        }

        return $sequence;
    }

    /**
     * One shuffled bag of all seven pieces.
     *
     * Shuffled by sorting on a per-piece hash rather than with shuffle(): the
     * sequence has to be reproducible from the seed alone, and shuffle() draws
     * on global randomness that no seed here controls.
     *
     * @return list<string>
     */
    private function bag(string $seed): array
    {
        $pieces = StackPieces::keys();

        usort($pieces, static fn (string $a, string $b): int => strcmp(
            hash('sha256', $seed.':'.$a),
            hash('sha256', $seed.':'.$b),
        ));

        return $pieces;
    }
}
