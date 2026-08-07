<?php

namespace App\Domain\Games\Stack;

/**
 * The playfield: which cells are filled, and every rule about moving a piece
 * through them.
 *
 * Immutable — each move returns a new board — so the screen can test a move
 * before committing to it ("would this collide?") without any chance of
 * half-applying it. That is what makes rotation-against-a-wall safe to
 * attempt and reject.
 *
 * Rows are indexed from the TOP down, matching how the piece tables are
 * written and how the board is drawn. Row 0 is where pieces enter.
 */
final class StackBoard
{
    /**
     * @param  array<int, array<int, string|null>>  $cells  [row][column] => piece colour, or null
     */
    public function __construct(
        public readonly int $columns,
        public readonly int $rows,
        private readonly array $cells,
    ) {}

    public static function empty(int $columns, int $rows): self
    {
        return new self($columns, $rows, array_fill(0, $rows, array_fill(0, $columns, null)));
    }

    public function isFilled(int $column, int $row): bool
    {
        return ($this->cells[$row][$column] ?? null) !== null;
    }

    public function colorAt(int $column, int $row): ?string
    {
        return $this->cells[$row][$column] ?? null;
    }

    /**
     * Every filled cell, for rendering.
     *
     * @return list<array{column: int, row: int, color: string}>
     */
    public function filledCells(): array
    {
        $filled = [];

        foreach ($this->cells as $row => $columns) {
            foreach ($columns as $column => $color) {
                if ($color !== null) {
                    $filled[] = ['column' => $column, 'row' => $row, 'color' => $color];
                }
            }
        }

        return $filled;
    }

    /**
     * Whether a piece can occupy this position.
     *
     * Cells ABOVE the board are allowed. A piece enters partly off the top and
     * would otherwise be rejected at spawn on a nearly-full board — which
     * would end the run one piece early, before the stack had actually reached
     * the ceiling.
     *
     * @param  list<array{0: int, 1: int}>  $cells
     */
    public function accepts(array $cells, int $offsetColumn, int $offsetRow): bool
    {
        foreach ($cells as [$cellColumn, $cellRow]) {
            $column = $offsetColumn + $cellColumn;
            $row = $offsetRow + $cellRow;

            if ($column < 0 || $column >= $this->columns || $row >= $this->rows) {
                return false;
            }

            if ($row >= 0 && $this->isFilled($column, $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Settle a piece into the board.
     *
     * @param  list<array{0: int, 1: int}>  $cells
     */
    public function lock(array $cells, int $offsetColumn, int $offsetRow, string $color): self
    {
        $next = $this->cells;

        foreach ($cells as [$cellColumn, $cellRow]) {
            $row = $offsetRow + $cellRow;
            $column = $offsetColumn + $cellColumn;

            // A cell above the ceiling has nowhere to go; the caller has
            // already decided this lock tops the run out.
            if ($row < 0 || $row >= $this->rows || $column < 0 || $column >= $this->columns) {
                continue;
            }

            $next[$row][$column] = $color;
        }

        return new self($this->columns, $this->rows, $next);
    }

    /**
     * Remove every full row, dropping what was above it.
     *
     * @return array{0: self, 1: int} the new board and how many rows went
     */
    public function clearFullRows(): array
    {
        $kept = [];
        $cleared = 0;

        foreach ($this->cells as $row) {
            if (! in_array(null, $row, true)) {
                $cleared++;

                continue;
            }

            $kept[] = $row;
        }

        if ($cleared === 0) {
            return [$this, 0];
        }

        // Refill from the top, so everything above a cleared row falls by
        // exactly the number of rows removed beneath it.
        $empty = array_fill(0, $cleared, array_fill(0, $this->columns, null));

        return [new self($this->columns, $this->rows, [...$empty, ...$kept]), $cleared];
    }

    /**
     * Empty cells with something filled above them — the classic measure of a
     * bad placement, since nothing can reach them again until the rows above
     * are cleared. Counting these is how a placement is judged, rather than
     * whether it happened to clear a line.
     */
    public function holeCount(): int
    {
        $holes = 0;

        for ($column = 0; $column < $this->columns; $column++) {
            $covered = false;

            for ($row = 0; $row < $this->rows; $row++) {
                if ($this->isFilled($column, $row)) {
                    $covered = true;

                    continue;
                }

                if ($covered) {
                    $holes++;
                }
            }
        }

        return $holes;
    }

    /** The highest occupied row, or null when the board is empty. */
    public function highestFilledRow(): ?int
    {
        foreach ($this->cells as $row => $columns) {
            if (array_filter($columns) !== []) {
                return $row;
            }
        }

        return null;
    }
}
