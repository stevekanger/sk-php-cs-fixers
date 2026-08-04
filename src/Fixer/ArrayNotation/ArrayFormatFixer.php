<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Fixer\ArrayNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\IndentationTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SkPhpCsFixers\Traits\TokenHelpersTrait;

final class ArrayFormatFixer extends AbstractFixer implements WhitespacesAwareFixerInterface, ConfigurableFixerInterface {
    use ConfigurableFixerTrait;
    use IndentationTrait;
    use TokenHelpersTrait;

    private const FORMAT_TYPE_IGNORE = 'ignore';
    private const FORMAT_TYPE_MULTILINE = 'multiline';
    private const FORMAT_TYPE_EMPTY_SINGLELINE = 'empty_singleline';
    private const FORMAT_TYPE_EMPTY_MULTILINE = 'empty_multiline';

    private $array_opening_tags = [
        \T_ARRAY,
        \T_LIST,
        CT::T_ARRAY_BRACKET_OPEN,
        CT::T_DESTRUCTURING_BRACKET_OPEN,
    ];

    public function getDefinition(): FixerDefinitionInterface {
        return new FixerDefinition(
            'Arrays that contain any line breaks must have each element on its own line.',
            [
                new CodeSample(
                    "<?php\n\$a = [1,\n    2, 3];\n",
                ),
                new CodeSample(
                    "<?php\n\$a = [1, 2, 3];\n",
                    ['on_singleline' => 'ensure_multiline'],
                ),
                new CodeSample(
                    "<?php\n\$a = [\n];\n",
                    ['on_empty' => 'ensure_singleline'],
                ),
                new CodeSample(
                    "<?php\n\$a = [];\n",
                    ['on_empty' => 'ensure_multiline'],
                ),
            ],
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolverInterface {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder(
                'on_singleline',
                'Defines how to handle arrays that do not contain newlines.'
            ))
                ->setAllowedTypes(['string'])
                ->setAllowedValues(['ensure_multiline', 'ignore'])
                ->setDefault('ignore')
                ->getOption(),
            (new FixerOptionBuilder(
                'on_empty',
                'Defines how to handle arrays that do not contain any elements (including comments).'
            ))
                ->setAllowedTypes(['string'])
                ->setAllowedValues(['ensure_multiline', 'ensure_singleline', 'ignore'])
                ->setDefault('ensure_singleline')
                ->getOption(),
        ]);
    }

    public function getPriority(): int {
        return 28;
    }

    public function getName(): string {
        return 'SkPhpCsFixers/array_format';
    }

    public function supports(\SplFileInfo $file): bool {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool {
        return $tokens->isAnyTokenKindsFound($this->array_opening_tags);
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder(
                'on_singleline',
                'Defines how to handle arrays that do not contain newlines.'
            ))
                ->setAllowedTypes(['string'])
                ->setAllowedValues(['ensure_multiline', 'ignore'])
                ->setDefault('ignore')
                ->getOption(),
            (new FixerOptionBuilder(
                'on_empty',
                'Defines how to handle arrays that do not contain any elements (including comments).'
            ))
                ->setAllowedTypes(['string'])
                ->setAllowedValues(['ensure_multiline', 'ensure_singleline', 'ignore'])
                ->setDefault('ensure_singleline')
                ->getOption(),
        ]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void {
        for ($index = 0; $index < $tokens->count(); ++$index) {
            if ($this->isArrayOpenToken($tokens[$index])) {
                $index = $this->ensureArrayFormat($tokens, $index);
            }
        }
    }

    /**
     * Checks if the token is a possible opening for an array.
     */
    private function isArrayOpenToken(Token $token): bool {
        return $token->isGivenKind($this->array_opening_tags);
    }

    /**
     * Ensures the correct format for the array.
     *
     * @return int The updated index for the loop
     */
    private function ensureArrayFormat(Tokens $tokens, int $index): int {
        [$openIndex, $closeIndex] = $this->getArrayBounds($tokens, $index);

        if (null === $openIndex || null === $closeIndex) {
            return $index;
        }

        $formatType = $this->getFormatType($tokens, $openIndex, $closeIndex);

        switch ($formatType) {
            case self::FORMAT_TYPE_MULTILINE:
                return $this->ensureMultiline($tokens, $openIndex, $closeIndex);
            case self::FORMAT_TYPE_EMPTY_SINGLELINE:
                return $this->ensureEmptySingleline($tokens, $openIndex, $closeIndex);
            case self::FORMAT_TYPE_EMPTY_MULTILINE:
                return $this->ensureEmptyMultiline($tokens, $openIndex, $closeIndex);
            case self::FORMAT_TYPE_IGNORE:
                return $closeIndex;
            default:
                return $closeIndex;
        }
    }

    /**
     * Gets the format type based on the array type.
     */
    private function getFormatType(Tokens $tokens, int $openIndex, int $closeIndex): string {
        $isSingleline = true;
        $isEmpty = true;

        for ($index = $openIndex + 1; $index < $closeIndex; ++$index) {
            if ($this->tokenIsNewline($tokens[$index])) {
                $isSingleline = false;
            } elseif (!$tokens[$index]->isWhitespace()) {
                $isEmpty = false;
            }

            if (!$isEmpty && !$isSingleline) {
                return self::FORMAT_TYPE_MULTILINE;
            }
        }

        if ($isEmpty) {
            if ('ensure_singleline' === $this->configuration['on_empty']) {
                return self::FORMAT_TYPE_EMPTY_SINGLELINE;
            }

            if ('ensure_multiline' === $this->configuration['on_empty']) {
                return self::FORMAT_TYPE_EMPTY_MULTILINE;
            }

            return self::FORMAT_TYPE_IGNORE;
        }

        return 'ensure_multiline' === $this->configuration['on_singleline']
            ? self::FORMAT_TYPE_MULTILINE
            : self::FORMAT_TYPE_IGNORE;
    }

    /**
     * Ensures an empty array is multiline with no spaces.
     */
    private function ensureEmptyMultiline(Tokens $tokens, int $openIndex, int $closeIndex): int {
        [$baseLineEndIndent] = $this->getLineEndIndents($tokens, $openIndex);

        $this->insertLineEndIndentAfter(
            $tokens,
            $openIndex,
            $closeIndex,
            $baseLineEndIndent,
        );

        return $closeIndex;
    }

    /**
     * Ensures an empty array is on a single line with no spaces.
     */
    private function ensureEmptySingleline(Tokens $tokens, int $openIndex, int $closeIndex): int {
        $this->tokensClearRange($tokens, $openIndex + 1, $closeIndex - 1);

        return $closeIndex;
    }

    /**
     * Ensures the multiline array is in the correct format.
     */
    private function ensureMultiline(Tokens $tokens, int $openIndex, int $closeIndex): int {
        [$baseLineEndIndent, $elementLineEndIndent] = $this->getLineEndIndents($tokens, $openIndex);

        // Insert new line indent after the opening tag
        $startIndex = $this->insertLineEndIndentAfter(
            $tokens,
            $openIndex,
            $closeIndex,
            $elementLineEndIndent,
        );

        // Loop through elements stop before the closing bracket
        for ($index = $startIndex; $index < $closeIndex - 1; ++$index) {
            // Handle nested arrays
            if ($this->isArrayOpenToken($tokens[$index])) {
                $oldCount = $tokens->count();
                $index = $this->ensureArrayFormat($tokens, $index);
                $closeIndex += $tokens->count() - $oldCount;

                continue;
            }

            // Insert new line indent after commas and comments
            if ($tokens[$index]->equals(',') || $tokens[$index]->isComment()) {
                if ($tokens->getNextMeaningfulToken($index) === $closeIndex) {
                    continue;
                }

                $index = $this->insertLineEndIndentAfter(
                    $tokens,
                    $index,
                    $closeIndex,
                    $elementLineEndIndent,
                );
            }
        }

        // Insert new line indent after the closing tag
        $this->insertLineEndIndentBefore(
            $tokens,
            $closeIndex,
            $closeIndex,
            $baseLineEndIndent,
        );

        return $closeIndex;
    }

    /**
     * Inserts the new line and indent after a specified index.
     *
     * @return int The updated index for the loop
     */
    private function insertLineEndIndentAfter(Tokens $tokens, int $index, int &$closeIndex, string $lineEndIndent): int {
        $targetIndex = $index + 1;

        if (!$tokens[$targetIndex]->isWhitespace() && !$tokens[$targetIndex]->isGivenKind(\T_COMMENT)) {
            $this->tokensInsertAt($tokens, $targetIndex, \T_WHITESPACE, $lineEndIndent);
            ++$closeIndex;

            return $targetIndex + 1;
        }

        $nextMeaningful = $tokens->getNextMeaningfulToken($targetIndex);
        $inlineComments = $this->findInlineComments($tokens, $targetIndex, $nextMeaningful);

        if ($inlineComments) {
            $targetIndex = end($inlineComments) + 1;
        }

        if ($tokens[$targetIndex]->isWhitespace()) {
            $this->tokensReplaceAt($tokens, $targetIndex, \T_WHITESPACE, $lineEndIndent);
        } else {
            $this->tokensInsertAt($tokens, $targetIndex, \T_WHITESPACE, $lineEndIndent);
            ++$closeIndex;
        }

        $nextNonWhitespace = $tokens->getTokenNotOfKindsSibling(
            $targetIndex,
            1,
            [\T_WHITESPACE],
        );

        $this->tokensClearRange($tokens, $targetIndex + 1, $nextNonWhitespace - 1);

        return $nextNonWhitespace - 1;
    }

    /**
     * Inserts the new line and indent before a specified index.
     *
     * @return int The updated index for the loop
     */
    private function insertLineEndIndentBefore(Tokens $tokens, int $index, int &$closeIndex, string $lineEndIndent): int {
        $targetIndex = $index - 1;

        if (!$tokens[$targetIndex]->isWhitespace()) {
            $this->tokensInsertAt($tokens, $index, \T_WHITESPACE, $lineEndIndent);
            ++$closeIndex;

            return $index + 1;
        }

        $this->tokensReplaceAt($tokens, $targetIndex, \T_WHITESPACE, $lineEndIndent);

        $prevNonWhitespace = $tokens->getTokenNotOfKindsSibling(
            $targetIndex,
            -1,
            [\T_WHITESPACE],
        );

        $this->tokensClearRange($tokens, $prevNonWhitespace + 1, $targetIndex - 1);

        return $index;
    }

    /**
     * Checks if there is a comment immediately after the given index before any meaningful or \n characters.
     *
     * @returns ?array
     */
    private function findInlineComments(Tokens $tokens, int $start, int $end): ?array {
        $indexes = [];

        for ($index = $start; $index < $end; ++$index) {
            $token = $tokens[$index];

            if ($token->isGivenKind(\T_COMMENT)) {
                $indexes[] = $index;

                continue;
            }

            if (!$token->isWhitespace() || $this->tokenIsNewline($token)) {
                break;
            }
        }

        return \count($indexes) ? $indexes : null;
    }

    /**
     * Gets the opening and ending index for the array.
     *
     * @returns ?array
     */
    private function getArrayBounds(Tokens $tokens, int $openIndex): ?array {
        if ($tokens[$openIndex]->isGivenKind(CT::T_ARRAY_BRACKET_OPEN)) {
            return [
                $openIndex,
                $tokens->findBlockEnd(
                    Tokens::BLOCK_TYPE_ARRAY_BRACKET,
                    $openIndex,
                ),
            ];
        }

        if ($tokens[$openIndex]->isGivenKind(CT::T_DESTRUCTURING_BRACKET_OPEN)) {
            return [
                $openIndex,
                $tokens->findBlockEnd(
                    Tokens::BLOCK_TYPE_DESTRUCTURING_BRACKET,
                    $openIndex,
                ),
            ];
        }

        if ($tokens[$openIndex]->isGivenKind([\T_ARRAY, \T_LIST])) {
            $startIdx = $tokens->getNextTokenOfKind($openIndex, ['(']);

            if (null === $startIdx) {
                return null;
            }

            return [
                $startIdx,
                $tokens->findBlockEnd(
                    Tokens::BLOCK_TYPE_PARENTHESIS,
                    $startIdx,
                ),
            ];
        }

        return null;
    }

    /**
     * Get the line end plus indent strings for both base and elements.
     *
     * @return array {0: string, 1: string} baseLineEndIndent, elementLineEndIndent
     */
    private function getLineEndIndents(Tokens $tokens, int $openIndex): array {
        $baseIndent = $this->getLineIndentation($tokens, $openIndex);
        $elementIndent = $baseIndent . $this->whitespacesConfig->getIndent();
        $lineEnd = $this->whitespacesConfig->getLineEnding();

        $baseLineEndIndent = $lineEnd . $baseIndent;
        $elementLineEndIndent = $lineEnd . $elementIndent;

        return [$baseLineEndIndent, $elementLineEndIndent];
    }
}
