<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

final class HtmlToken {
    public function __construct(
        public readonly HT $kind,
        public readonly string $content,
    ) {
    }

    /**
     * Checks if an html token is a given kind.
     *
     * @param HT|array<HT> $possibleKind The kind or kinds of html token you want to check against
     */
    public function isGivenKind(HT|array $possibleKind): bool {
        return \is_array($possibleKind) ? \in_array($this->kind, $possibleKind, true) : $this->kind === $possibleKind;
    }
}
