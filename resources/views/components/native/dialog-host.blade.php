@props([
    'dialogVisible' => false,
    'bottomSheetVisible' => false,
    'sheetDetents' => 'medium,large',
    'dialogA11yLabel' => 'Dialog',
    'sheetA11yLabel' => 'Bottom sheet',
])

<native:modal
    :visible="$dialogVisible"
    :dismissible="true"
    :a11y-label="$dialogA11yLabel"
    @dismiss="dismissDialog"
>
    {{ $dialog ?? '' }}
</native:modal>

<native:bottom-sheet
    :visible="$bottomSheetVisible"
    :detents="$sheetDetents"
    :a11y-label="$sheetA11yLabel"
    @dismiss="dismissBottomSheet"
>
    {{ $sheet ?? '' }}
</native:bottom-sheet>
