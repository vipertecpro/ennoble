<?php

namespace App\NativeUI\Home;

/**
 * The gaming quotes that run across the top of Home.
 *
 * WHY EACH QUOTE CARRIES A WIDTH. The ticker scrolls by setting a translate-x
 * target in PHP, so PHP has to know exactly how wide the strip is — and PHP
 * cannot measure rendered text. The fix is to stop guessing and start
 * dictating: every quote is rendered inside a container of exactly the width
 * computed here, so the estimate becomes the truth and the scroll offset can
 * never drift out of step with what is on screen.
 *
 * HOW THE SPACING IS TUNED. A slot is wider than its text by whatever the
 * estimate over-shoots, and that slack is what the eye reads as the gap between
 * quotes — so the gap is set EXPLICITLY by {@see self::TRAILING_GAP} rather
 * than left as a by-product of a padding fudge. Keeping the per-character
 * factor close to the font's real average advance is what makes that work; the
 * view pins each line with `max-lines="1"`, so if a wide-glyph quote does
 * overrun the estimate it truncates instead of wrapping and breaking the strip.
 *
 * FONT_SIZE lives here, not in the view, because the width factor is calibrated
 * against it — changing one without the other silently drifts the spacing.
 */
final class GamingQuotes
{
    /** Type size of the ticker. The view reads this; don't hardcode it there. */
    public const FONT_SIZE = 15;

    /**
     * Average glyph advance at FONT_SIZE, with deliberate headroom. The excess
     * over the real advance IS the visible gap between quotes — and it is now
     * load-bearing, not cosmetic: the separator cell occupies part of every
     * slot, so a slot whose text overruns its estimate pushes the diamond out
     * and it overlaps the following quote. Keep this generous.
     */
    private const WIDTH_PER_CHARACTER = 8.1;

    /** The single gap between quote and source, which the text does not cover. */
    private const GAP_ALLOWANCE = 8;

    /**
     * Width of the fixed cell that OPENS every slot and holds the glowing
     * diamond. Leading, not trailing: a slot's leftover slack collects at its
     * end, so a leading diamond sits a predictable few points before the quote
     * it introduces, while a trailing one would drift with the slack and, if
     * the estimate ever ran short, land on top of the next quote.
     */
    public const SEPARATOR_CELL = 30;

    private const MINIMUM_SLOT = 160;

    /**
     * @var list<array{text: string, source: string}>
     */
    private const QUOTES = [
        ['text' => 'It’s dangerous to go alone! Take this.', 'source' => 'The Legend of Zelda'],
        ['text' => 'War. War never changes.', 'source' => 'Fallout'],
        ['text' => 'A man chooses, a slave obeys.', 'source' => 'BioShock'],
        ['text' => 'The cake is a lie.', 'source' => 'Portal'],
        ['text' => 'Stay awhile and listen.', 'source' => 'Diablo'],
        ['text' => 'Nothing is true; everything is permitted.', 'source' => 'Assassin’s Creed'],
        ['text' => 'Do a barrel roll!', 'source' => 'Star Fox 64'],
        ['text' => 'Praise the sun!', 'source' => 'Dark Souls'],
        ['text' => 'Hey! Listen!', 'source' => 'Ocarina of Time'],
        ['text' => 'Wake up, Mr. Freeman.', 'source' => 'Half-Life 2'],
        ['text' => 'The right man in the wrong place makes all the difference.', 'source' => 'Half-Life 2'],
        ['text' => 'Our princess is in another castle!', 'source' => 'Super Mario Bros.'],
        ['text' => 'You have died of dysentery.', 'source' => 'The Oregon Trail'],
        ['text' => 'Endure. In enduring, grow strong.', 'source' => 'Diablo III'],
        ['text' => 'Get over here!', 'source' => 'Mortal Kombat'],
        ['text' => 'Finish him!', 'source' => 'Mortal Kombat'],
        ['text' => 'It’s-a me, Mario!', 'source' => 'Super Mario 64'],
        ['text' => 'Would you kindly?', 'source' => 'BioShock'],
        ['text' => 'Snake? Snake! Snaaake!', 'source' => 'Metal Gear Solid'],
        ['text' => 'Slow is smooth, and smooth is fast.', 'source' => 'Speedrunner’s rule'],
        ['text' => 'Every expert was once a beginner.', 'source' => 'Player’s rule'],
        ['text' => 'Practice makes permanent — so practise it right.', 'source' => 'Player’s rule'],
        ['text' => 'Focus beats speed, and speed follows focus.', 'source' => 'Player’s rule'],
        ['text' => 'The only run that counts is the next one.', 'source' => 'Player’s rule'],
    ];

    /**
     * Every quote with the exact slot width the ticker will render it at.
     *
     * @return list<array{text: string, source: string, label: string, width: int}>
     */
    public static function all(): array
    {
        return array_map(static function (array $quote): array {
            return [
                ...$quote,
                'label' => $quote['text'].' — '.$quote['source'],
                // Measured against what is actually drawn: the quote, the
                // source, and the fixed chrome between them.
                'width' => self::slotWidth($quote['text'].$quote['source']),
            ];
        }, self::QUOTES);
    }

    public static function count(): int
    {
        return count(self::QUOTES);
    }

    /**
     * Total width of one full pass. The ticker wraps on exactly this distance,
     * which is seamless because the strip repeats its opening quotes.
     */
    public static function stripWidth(): int
    {
        return array_sum(array_column(self::all(), 'width'));
    }

    private static function slotWidth(string $drawnText): int
    {
        return max(
            self::MINIMUM_SLOT,
            (int) ceil(mb_strlen($drawnText) * self::WIDTH_PER_CHARACTER)
                + self::GAP_ALLOWANCE
                + self::SEPARATOR_CELL,
        );
    }
}
