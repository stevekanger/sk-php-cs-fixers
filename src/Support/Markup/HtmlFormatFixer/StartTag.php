<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

/**
 * Html start tag class.
 *
 * Wrapper for the start tag information
 */
class StartTag {
    private string $name = '';
    private bool $selfClosing = false;

    /**
     * Gets the tag name.
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Appends a character to the tag name.
     *
     * @param string $char The character to append
     */
    public function appendName(string $char) {
        $this->name .= $char;
    }

    /**
     * Sets the self closing value.
     *
     * @param bool $value The value to set
     */
    public function setSelfClosing(bool $value) {
        $this->selfClosing = $value;
    }

    /**
     * Checks whether the tag is self closing or not.
     */
    public function isSelfClosing(): bool {
        return $this->selfClosing;
    }
}
