@use('App\Domain\Games\Stack\StackPieces')

{{--
    A small flat rendering of one tetromino, for the Hold and Next panels.

    Deliberately NOT a second 3D viewport. Each viewport is its own engine,
    surface and frame loop; spending four of them on thumbnails the size of a
    fingernail would cost more than the board itself. Flat cells read better at
    this size anyway.
--}}
@props([
    'piece' => null,
    'cell' => 7,
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
    $filled = collect($cells)->map(fn (array $c): string => ($c[0] - $minColumn).','.($c[1] - $minRow))->all();
    $color = $piece === null ? null : StackPieces::color($piece);
@endphp

<native:column class="gap-0.5" a11y-label="{{ $piece === null ? 'Empty' : strtoupper($piece).' piece' }}">
    @for ($row = 0; $row < $height; $row++)
        <native:row class="gap-0.5">
            @for ($column = 0; $column < $width; $column++)
                {{-- An arbitrary hex class, because the piece palette is
                     genuine data-driven colour and lives in StackPieces. --}}
                <native:column class="w-[{{ $cell }}px] h-[{{ $cell }}px] rounded-[2px] {{ in_array($column.','.$row, $filled, true) ? 'bg-['.$color.']' : '' }}" />
            @endfor
        </native:row>
    @endfor
</native:column>
