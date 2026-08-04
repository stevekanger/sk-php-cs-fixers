<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

use PhpCsFixer\WhitespacesFixerConfig;

class Indent {
    private array $tags = [];
    private string $baseIndent = '';

    public function __construct(
        private WhitespacesFixerConfig $whitespacesConfig,
        private int $depth = 0,
    ) {
    }

    /**
     * Pushes to the tag stack.
     *
     * @param bool $val The value to push
     */
    public function tagsPush(bool $val): static {
        array_push($this->tags, $val);

        return $this;
    }

    /**
     * Pops from the tag stack.
     */
    public function tagsPop(): bool {
        return array_pop($this->tags);
    }

    /**
     * Sets the indent depth;.
     */
    public function setDepth(int $depth): static {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Gets the indent depth.
     */
    public function getDepth(): int {
        return $this->depth;
    }

    /**
     * Increases the indent depth by 1;.
     */
    public function increaseDepth(): static {
        ++$this->depth;

        return $this;
    }

    /**
     * Decreases the indent depth by 1 (Min 0);.
     */
    public function decreaseDepth(): static {
        $this->depth = max(0, $this->depth - 1);

        return $this;
    }

    /**
     * Sets the base indent.
     *
     * @param string $baseIndent The base indent
     */
    public function setBaseIndent(string $baseIndent): static {
        $this->baseIndent = $baseIndent;

        return $this;
    }

    /**
     * Gets the indent characters based on the depth.
     */
    public function getIndent(): string {
        return $this->baseIndent . str_repeat($this->whitespacesConfig->getIndent(), $this->depth);
    }

    /**
     * Gets the line end indent characters based on indent depth.
     */
    public function getLineEndIndent(): string {
        $indentChars = $this->getIndent();
        $lineEndChars = $this->whitespacesConfig->getLineEnding();

        return $lineEndChars . $indentChars;
    }
}
