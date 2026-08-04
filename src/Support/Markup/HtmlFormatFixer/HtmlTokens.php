<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

class HtmlTokens {
    private array $map = [];
    private int $count = 0;

    /**
     * Gets the total count for the html tokens.
     */
    public function count(): int {
        return $this->count;
    }

    /**
     * Adds a tokens array to the tokens map.
     *
     * @param int $index The index to add the array to.
     * @param array<HtmlToken> $tokens The tokens array
     */
    public function add(int $index, array $tokens): static {
        $this->map[$index] = $tokens;
        ++$this->count;

        return $this;
    }

    /**
     * Gets the mapped tokens for a token index.
     *
     * @param int $index The map index to get the tokens from
     *
     * @return array<HtmlToken>
     */
    public function get(int $index): array {
        $tokens = $this->map[$index] ?? null;

        if (!$tokens) {
            throw new \RuntimeException("HtmlTokens: No tokens array at map position {$index}");
        }

        return $tokens;
    }

    /**
     * Gets a token at a specific position.
     *
     * @param int $tokenIndex The index of the \T_INLINE_HTML php token
     * @param int $htmlTokenIndex The index in the tokens array to get from
     */
    public function getToken(int $tokenIndex, int $htmlTokenIndex): ?HtmlToken {
        return $this->map[$tokenIndex][$htmlTokenIndex] ?? null;
    }
}
