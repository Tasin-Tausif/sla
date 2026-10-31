<?php

namespace App\Support;

final class FormFieldName
{
    public static function toDotNotation(string $name): string
    {
        return trim((string) preg_replace('/\[([^\]]*)\]/', '.$1', $name), '.');
    }
}
