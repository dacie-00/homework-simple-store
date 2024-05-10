<?php

class Table
{
    static function display(array $columns): void
    {
        $padString = " ";
        $separator = "|";

        $titles = [];
        foreach ($columns as $column) {
            $titles[] = $column->title;
        }

        $widths = self::calculateColumnWidths($columns);
        $totalWidth = array_sum($widths) + count($columns) * strlen($padString) * 2 + count($columns) + 1;

        echo $separator;
        self::displayRow($titles, $widths, $padString, $separator);
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
            self::displayRow($row, $widths, $padString, $separator);
            echo "\n";
        }
    }

    static function calculateColumnWidths(array $columns): array
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

    static function displayRow(array $elements, array $widths, string $padString, $separator): void
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

    static function createColumn($title, $content): stdClass
    {
        $column = new stdClass();
        $column->title = $title;
        $column->content = $content;
        return $column;
    }
}
