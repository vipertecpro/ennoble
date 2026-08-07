@use('App\Domain\Games\Stack\StackPieces')

{{--
    A small flat rendering of one tetromino, for the Hold and Next panels.

    Deliberately NOT a second 3D viewport. Each viewport is its own engine,
    surface and frame loop; four of them on thumbnails this size would cost
    more than the board.

    A STACK with each cell transform-offset from the centre — no nested flex,
    no absolute coordinates. Both alternatives were tried on a device first:

      row > rect                 renders
      row > column > rect        renders
      row > column > row > rect  VANISHES

    A preview lives inside the Next row, so any row of its own is a row nested
    along the same axis. iOS measures those with an unbounded width proposal,
    they collapse to zero and take their contents with them — the trap already
    documented in game-hud. Explicit pixel widths do not save it.

    A `canvas` with `left`/`top` rects was the next attempt: the cells appeared
    but every one landed in the same column, so the horizontal coordinate never
    reached the renderer. `translate-x` / `translate-y` are core transform props
    that do, and a stack overlays its children rather than flowing them.

    Offsets are measured from the CENTRE, because that is where a stack places
    every child before transforms are applied.

    Only FILLED cells are emitted. An empty cell in a grid has to be drawn to
    hold its place; overlaid, it simply is not there.
--}}
@props([
    'piece' => null,
    'cell' => 9,
    'gap' => 2,
])

@php
    // Trimmed to the piece's own bounding box so every preview is centred in
    // its panel, rather than each sitting wherever it happens to fall inside
    // the 4x4 box the rotation tables are written in.
    $cells = $piece === null ? [] : StackPieces::cells($piece, 0);
    $columns = array_column($cells, 0);
    $rows = array_column($cells, 1);
    $minColumn = $cells === [] ? 0 : min($columns);
    $minRow = $cells === [] ? 0 : min($rows);
    $width = $cells === [] ? 0 : max($columns) - $minColumn + 1;
    $height = $cells === [] ? 0 : max($rows) - $minRow + 1;

    $step = $cell + $gap;
    $pixelWidth = $width === 0 ? 0 : ($width * $cell) + (($width - 1) * $gap);
    $pixelHeight = $height === 0 ? 0 : ($height * $cell) + (($height - 1) * $gap);
    $color = $piece === null ? '#000000' : StackPieces::color($piece);
@endphp

<native:stack
    class="w-[{{ $pixelWidth }}px] h-[{{ $pixelHeight }}px]"
    a11y-label="{{ $piece === null ? 'Nothing held' : strtoupper($piece).' piece' }}"
>
    @foreach ($cells as [$cellColumn, $cellRow])
        {{-- An arbitrary hex class, because the piece palette is genuine
             data-driven colour and lives in StackPieces. --}}
        <native:rect
            class="w-[{{ $cell }}px] h-[{{ $cell }}px] rounded-[2px] bg-[{{ $color }}]"
            :translate-x="(($cellColumn - $minColumn) - ($width - 1) / 2) * $step"
            :translate-y="(($cellRow - $minRow) - ($height - 1) / 2) * $step"
        />
    @endforeach
</native:stack>
