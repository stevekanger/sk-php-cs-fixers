<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

/**
 * RawtextEndTag class.
 *
 * Accumulates the rawtext end tag content while in the rawtext states of the html tokenizer
 */
class EndTag {
    private string $lessThanSign = '';
    private string $greaterThanSign = '';
    private string $solidus = '';
    private string $startTagName;
    private string $endTagName = '';
    private string $precedingWhitespace = '';
    private string $succeedingWhitespace = '';

    /**
     * Creates the RawtextEndTag class.
     *
     * @param $startTagName The tag name of the rawtext start tag
     */
    public function __construct(string $startTagName) {
        $this->startTagName = $startTagName;
    }

    /**
     * Gets the tag lessThanSign.
     */
    public function getLessThanSign(): string {
        return $this->lessThanSign;
    }

    /**
     * Sets the tag lessThanSign.
     */
    public function setLessThanSign() {
        $this->lessThanSign = '<';
    }

    /**
     * Gets the tag greaterThanSign.
     */
    public function getGreaterThanSign(): string {
        return $this->greaterThanSign;
    }

    /**
     * Sets the tag greaterThanSign.
     */
    public function setGreaterThanSign() {
        $this->greaterThanSign = '>';
    }

    /**
     * Gets the solidus.
     */
    public function getSolidus(): string {
        return $this->solidus;
    }

    /**
     * Sets the solidus.
     */
    public function setSolidus() {
        $this->solidus = '/';
    }

    /**
     * Gets the preceding whitespace.
     */
    public function getPrecedingWhitespace(): string {
        return $this->precedingWhitespace;
    }

    /**
     * Appends a whitespace char to the preceding whitespace.
     *
     * @param string $char The character to append
     */
    public function appendPrecedingWhitespace(string $char) {
        $this->precedingWhitespace .= $char;
    }

    /**
     * Gets the succeeding whitespace.
     */
    public function getSucceedingWhitespace(): string {
        return $this->succeedingWhitespace;
    }

    /**
     * Appends a whitespace char to the succeeding whitespace.
     *
     * @param string $char The character to append
     */
    public function appendSucceedingWhitespace(string $char) {
        $this->succeedingWhitespace .= $char;
    }

    /**
     * Gets the tag name.
     */
    public function getEndTagName(): string {
        return $this->endTagName;
    }

    /**
     * Appends a char to the end tag name.
     *
     * @param string $char The character to append
     */
    public function appendEndTagname(string $char) {
        $this->endTagName .= $char;
    }

    /**
     * Checks if the start tag and end tag match.
     */
    public function isMatch(): bool {
        return $this->startTagName === $this->endTagName;
    }

    /**
     * Returns all the accumulated characters.
     */
    public function flush(): string {
        return $this->lessThanSign . $this->solidus . $this->precedingWhitespace . $this->endTagName . $this->succeedingWhitespace;
    }
}
