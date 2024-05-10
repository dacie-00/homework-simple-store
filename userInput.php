<?php

function getUserChoiceFromArray(array $choices, string $promptMessage = "input")
{
    while (true) {
        $choice = readline(ucfirst("$promptMessage - "));
        if (!in_array($choice, $choices, true)) {
            echo "Invalid $promptMessage!\n";
            continue;
        }
        return $choice;
    }
}

function getUserChoiceFromRange(int $min, int $max, string $cancel = null, string $promptMessage = "input")
{
    while (true) {
        $choice = readline(ucfirst("$promptMessage - "));
        if ($choice === $cancel) {
            return $choice;
        }
        if (!is_numeric($choice)) {
            echo "Invalid $promptMessage!\n";
            continue;
        }
        $choice = (int)$choice;
        if ($choice < $min || $choice > $max) {
            echo "Invalid $promptMessage!\n";
            continue;
        }
        return $choice;
    }
}
