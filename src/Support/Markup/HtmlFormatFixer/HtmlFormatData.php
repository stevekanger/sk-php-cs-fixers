<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

use PhpCsFixer\Tokenizer\Tokens;

class HtmlFormatData {
    public function __construct(
        public readonly Tokens $phpTokens,
        public readonly HtmlTokens $htmlTokens,
        public readonly Indent $indent,
        private int $phpIndex = 0,
        private int $htmlIndex = 0,
        private bool $inHtmlTag = false,
    ) {
    }

    /**
     * Sets the token index.
     *
     * @param int $index The tokens index to set
     */
    public function setPhpIndex(int $index): static {
        $this->phpIndex = $index;

        return $this;
    }

    /**
     * Gets the index of the tokens array.
     */
    public function getPhpIndex(): int {
        return $this->phpIndex;
    }

    /**
     * Sets the html token index.
     *
     * @param int $index The html tokens index to set
     */
    public function setHtmlIndex(int $index): static {
        $this->htmlIndex = $index;

        return $this;
    }

    /**
     * Gets the index of the html tokens array.
     */
    public function getHtmlIndex(): int {
        return $this->htmlIndex;
    }

    /**
     * Set the inHtmlTag value.
     *
     * @param bool $inHtmlTag The value to set
     */
    public function setInHtmlTag(bool $inHtmlTag): static {
        $this->inHtmlTag = $inHtmlTag;

        return $this;
    }

    /**
     * Checks if the current token is inside a tag.
     */
    public function inHtmlTag(): bool {
        return $this->inHtmlTag;
    }
}
