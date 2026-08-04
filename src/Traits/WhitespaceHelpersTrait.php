<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Traits;

trait WhitespaceHelpersTrait {
    /**
     * Checks if a string has a new line.
     *
     * @param string $str The string to check against
     */
    public function hasNewline(string $str) {
        return str_contains($str, "\n");
    }
}
