<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Traits;

/**
 * AsciiHelpersTrait.
 *
 * Checks if a character code is a specific type of ascii code.
 *
 * @see https://infra.spec.whatwg.org/#code-points
 */
trait AsciiHelpersTrait {
    /**
     * Checks if is CO control.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiCOControl(string $char): bool {
        $code = ord($char);

        return $code >= 0x0000 && $code <= 0x001F;
    }

    /**
     * Checks if is CO control or space.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiCOControlOrSpace(string $char): bool {
        $code = ord($char);

        return $this->isAsciiCOControl($char) || 0x0020 === $code;
    }

    /**
     * Checks if is ascii control.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiControl(string $char): bool {
        $code = ord($char);

        return $this->isAsciiCOControl($char) || ($code >= 0x007F && $code <= 0x009F);
    }

    /**
     * Checks if is ascii digit.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiDigit(string $char): bool {
        $code = ord($char);

        return $code >= 0x0030 && $code <= 0x0039;
    }

    /**
     * Checks if is ascii upper hex digit.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiUpperHexDigit(string $char): bool {
        $code = ord($char);

        return $this->isAsciiDigit($char) || ($code >= 0x0041 && $code <= 0x0046);
    }

    /**
     * Checks if is ascii lower hex digit.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiLowerHexDigit(string $char): bool {
        $code = ord($char);

        return $this->isAsciiDigit($char) || ($code >= 0x0061 && $code <= 0x0066);
    }

    /**
     * Checks if is ascii hex digit.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiHexDigit(string $char): bool {
        return $this->isAsciiUpperHexDigit($char) || $this->isAsciiLowerHexDigit($char);
    }

    /**
     * Checks if is ascii upper alpha.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiUpperAlpha(string $char): bool {
        $code = ord($char);

        return $code >= 0x0041 && $code <= 0x005A;
    }

    /**
     * Checks if is ascii lower alpha.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiLowerAlpha(string $char): bool {
        $code = ord($char);

        return $code >= 0x0061 && $code <= 0x007A;
    }

    /**
     * Checks if is ascii alpha.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiAlpha(string $char): bool {
        return $this->isAsciiUpperAlpha($char) || $this->isAsciiLowerAlpha($char);
    }

    /**
     * Checks if is ascii alphanumeric.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiAlphanumeric(string $char): bool {
        return $this->isAsciiAlpha($char) || $this->isAsciiDigit($char);
    }

    /**
     * Checks if leading surrogate.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiLeadingSurrogate(string $char): bool {
        $code = mb_ord($char, 'UTF-8');

        return false !== $code && $code >= 0xD800 && $code <= 0xDBFF;
    }

    /**
     * Checks if trailing surrogate.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiTrailingSurrogate(string $char): bool {
        $code = mb_ord($char, 'UTF-8');

        return false !== $code && $code >= 0xDC00 && $code <= 0xDFFF;
    }

    /**
     * Checks if is surrogate.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiSurrogate(string $char): bool {
        return $this->isAsciiLeadingSurrogate($char) || $this->isAsciiTrailingSurrogate($char);
    }

    /**
     * Checks if is a non character.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiNonCharacter(string $char): bool {
        $code = mb_ord($char, 'UTF-8');

        return (false !== $code && $code >= 0xFDD0 && $code <= 0xFDEF)
            || 0xFFFE === $code
            || 0xFFFF === $code
            || 0x1FFFE === $code
            || 0x1FFFF === $code
            || 0x2FFFE === $code
            || 0x2FFFF === $code
            || 0x3FFFE === $code
            || 0x3FFFF === $code
            || 0x4FFFE === $code
            || 0x4FFFF === $code
            || 0x5FFFE === $code
            || 0x5FFFF === $code
            || 0x6FFFE === $code
            || 0x6FFFF === $code
            || 0x7FFFE === $code
            || 0x7FFFF === $code
            || 0x8FFFE === $code
            || 0x8FFFF === $code
            || 0x9FFFE === $code
            || 0x9FFFF === $code
            || 0xAFFFE === $code
            || 0xAFFFF === $code
            || 0xBFFFE === $code
            || 0xBFFFF === $code
            || 0xCFFFE === $code
            || 0xCFFFF === $code
            || 0xDFFFE === $code
            || 0xDFFFF === $code
            || 0xEFFFE === $code
            || 0xEFFFF === $code
            || 0xFFFFE === $code
            || 0xFFFFF === $code
            || 0x10FFFE === $code
            || 0x10FFFF === $code;
    }

    /**
     * Checks if is whitespace.
     *
     * @param string $char The character to test against
     */
    protected function isAsciiWhitespace(string $char): bool {
        $code = ord($char);

        return 0x0009 === $code
            || 0x000A === $code
            || 0x000C === $code
            || 0x000D === $code
            || 0x0020 === $code;
    }

    /**
     * Gets the ascii replacement char for control-character-reference parse error.
     *
     * @param string $char The character to test against
     *
     * @see https://html.spec.whatwg.org/multipage/parsing.html#numeric-character-reference-end-state
     */
    protected function getAsciiReplacementChar(string $char): ?int {
        $code = ord($char);

        $table = [
            0x80 => 0x20AC,
            0x82 => 0x201A,
            0x83 => 0x0192,
            0x84 => 0x201E,
            0x85 => 0x2026,
            0x86 => 0x2020,
            0x87 => 0x2021,
            0x88 => 0x02C6,
            0x89 => 0x2030,
            0x8A => 0x0160,
            0x8B => 0x2039,
            0x8C => 0x0152,
            0x8E => 0x017D,
            0x91 => 0x2018,
            0x92 => 0x2019,
            0x93 => 0x201C,
            0x94 => 0x201D,
            0x95 => 0x2022,
            0x96 => 0x2013,
            0x97 => 0x2014,
            0x98 => 0x02DC,
            0x99 => 0x2122,
            0x9A => 0x0161,
            0x9B => 0x203A,
            0x9C => 0x0153,
            0x9E => 0x017E,
            0x9F => 0x0178,
        ];

        return $table[$code] ?? null;
    }
}
