<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer\TreeBuilder;

use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\Tokenizer\HtmlToken;

final class HtmlTreeBuilder {
    public array $tokens = [];

    /**
     * Add a token to the html tree.
     *
     * @param HtmlToken $token
     */
    public function add(HtmlToken $token): void {
        $this->tokens[] = $token;
    }
}
