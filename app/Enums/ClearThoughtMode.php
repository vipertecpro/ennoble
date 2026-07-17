<?php

namespace App\Enums;

enum ClearThoughtMode: string
{
    case RemoveUnnecessaryWords = 'remove_unnecessary_words';
    case ReorderSentence = 'reorder_sentence';
    case ChooseClearestSentence = 'choose_clearest_sentence';
}
