<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

class OpenTag {
    private string $name = '';
    private bool $selfClosing = false;
    private bool $hasIndent = false;

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
     * Sets the self closing value.
     *
     * @param bool $value The value to set
     */
    public function setSelfClosing(bool $value): static {
        $this->selfClosing = $value;

        return $this;
    }

    /**
     * Checks whether the tag is self closing or not.
     */
    public function isSelfClosing(): bool {
        return $this->selfClosing;
    }

    /**
     * Sets whether the tag should have indent afterwards.
     *
     * @param bool $hasIndent Whether the tag should have an indent afterwards
     */
    public function setHasIndent(bool $hasIndent): static {
        $this->hasIndent = $hasIndent;

        return $this;
    }

    /**
     * Whether the tag should have an indent.
     */
    public function hasIndent(): bool {
        return $this->hasIndent;
    }
}
