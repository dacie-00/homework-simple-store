<?php

function tableDisplay(array $columns): void
{
    $padString = " ";
    $separator = "|";

    $titles = [];
    foreach ($columns as $column) {
        $titles[] = $column->title;
    }

    $widths = tableCalculateColumnWidths($columns);
    $totalWidth = array_sum($widths) + count($columns) * strlen($padString) * 2 + count($columns) + 1;

    echo $separator;
    tableDisplayRow($titles, $widths, $padString, $separator);
    echo "\n";
    echo str_repeat("=", $totalWidth);
    echo "\n";

    $rowCount = count($columns[0]->content);
    for ($i = 0; $i < $rowCount; $i++) {
        $row = [];
        foreach ($columns as $column) {
            $row[] = $column->content[$i];
        }
        echo $separator;
        tableDisplayRow($row, $widths, $padString, $separator);
        echo "\n";
    }
}

function tableCalculateColumnWidths(array $columns): array
{
    $widths = [];
    foreach ($columns as $column) {
        $titleWidth = strlen($column->title);
        $maxWidth = array_reduce($column->content, function ($carry, $item) {
            return max(strlen($item), $carry);
        }, 0);
        $widths[] = max($maxWidth, $titleWidth);
    }
    return $widths;
}

function tableDisplayRow(array $elements, array $widths, string $padString, $separator): void
{
    foreach ($elements as $index => $element) {
        $maxLength = $widths[$index];
        $wordLength = strlen($element);
        if ($wordLength != $maxLength) {
            $padLeft = str_repeat($padString, (int)floor(($maxLength - $wordLength) / 2 + 1));
            $padRight = str_repeat($padString, (int)ceil(($maxLength - $wordLength) / 2 + 1));
            echo "{$padLeft}$element{$padRight}$separator";
            continue;
        }
        echo "{$padString}$element{$padString}$separator";
    }
}

function tableCreateColumn($title, $content): stdClass
{
    $column = new stdClass();
    $column->title = $title;
    $column->content = $content;
    return $column;
}