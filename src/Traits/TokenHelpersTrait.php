<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Traits;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

trait TokenHelpersTrait {
    /**
     * Clears all tokens in a range. Optionally skip indexes.
     *
     * @param Tokens $tokens The tokens array
     * @param int $start The starting index
     * @param int $end The ending index
     * @param array<int> $exclude Optional: exclude indexes
     */
    public function tokensClearRange(Tokens $tokens, int $start, int $end, array $exclude = []): void {
        for ($index = $start; $index <= $end; ++$index) {
            if ($exclude && \in_array($index, $exclude, true)) {
                continue;
            }
            $tokens->clearAt($index);
        }
    }

    /**
     * Replaces a token at an index.
     *
     * @param Tokens $tokens The tokens array
     * @param int $index The index to relplace
     * @param int $key The token type
     * @param string $content The token content
     */
    public function tokensReplaceAt(Tokens $tokens, int $index, int $key, string $content): void {
        if ($tokens[$index]->getContent() === $content) {
            return;
        }

        $tokens[$index] = new Token([$key, $content]);
    }

    /**
     * Inserts a token at an index.
     *
     * @param Tokens $tokens The tokens array
     * @param int $index The index to relplace
     * @param int $key The token type
     * @param string $content The token content
     */
    public function tokensInsertAt(Tokens $tokens, int $index, int $key, string $content): void {
        if ($tokens[$index]->getContent() === $content) {
            return;
        }

        $tokens->insertAt($index, new Token([$key, $content]));
    }

    /**
     * Checks if a token is a new line character.
     *
     * @param Token $token The token to check
     */
    public function tokenIsNewline(Token $token): bool {
        return $token->isWhitespace() && str_contains($token->getContent(), "\n");
    }

    /**
     * Finds the prev token of a specific kind.
     *
     * @param Tokens $tokens The tokens array
     * @param int $kind The token kind to look for
     * @param int $index The index to start at
     *
     * @return array{Token, int} Token, Index
     */
    public function findPrevTokenOfKind(Tokens $tokens, int $kind, int $index): ?array {
        while ($index >= 0) {
            if ($tokens[$index]->isGivenKind($kind)) {
                return [$tokens[$index], $index];
            }

            --$index;
        }

        return null;
    }

    /**
     * Finds the next token of a specific kind.
     *
     * @param Tokens $tokens The tokens array
     * @param int $kind The token kind to look for
     * @param int $index The index to start at
     *
     * @return array{Token, int} Token, Index
     */
    public function findNextTokenOfKind(Tokens $tokens, int $kind, int $index): ?array {
        $count = $tokens->count();

        while ($index < $count) {
            if ($tokens[$index]->isGivenKind($kind)) {
                return [$tokens[$index], $index];
            }

            ++$index;
        }

        return null;
    }
}
