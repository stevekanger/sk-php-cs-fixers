<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

class CloseTag {
    private string $name = '';

    /**
     * Gets the tag name.
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Sets the name value.
     *
     * @param string $name The value to set
     */
    public function setName(string $name): static {
        $this->name = $name;

        return $this;
    }

    /**
     * Appends a char to the end tag name.
     *
     * @param string $char The character to append
     */
    public function appendName(string $char): static {
        $this->name .= $char;

        return $this;
    }

    /**
     * Checks if the start tag and end tag match.
     */
    public function isMatch(string $startTagName): bool {
        return $startTagName === $this->name;
    }
}
