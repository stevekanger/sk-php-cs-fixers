<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Fixer\Markup;

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
use PhpCsFixer\Tokenizer\Tokens;
use SkPhpCsFixers\Debug;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\Tokenizer\HtmlParseException;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\Tokenizer\HtmlTokenizer;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\TreeBuilder\HtmlTreeBuilder;
use SkPhpCsFixers\Traits\TokenHelpersTrait;
use SkPhpCsFixers\Traits\WhitespaceHelpersTrait;

final class HtmlFormatFixer extends AbstractFixer implements WhitespacesAwareFixerInterface, ConfigurableFixerInterface {
    use ConfigurableFixerTrait;
    use TokenHelpersTrait;
    use IndentationTrait;
    use WhitespaceHelpersTrait;

    public function getDefinition(): FixerDefinitionInterface {
        return new FixerDefinition(
            'Html should be correctly indented and formatted.',
            [
                new CodeSample(
                    <<<PHP
                        <html>
                            <body> 
                                <p><?php echo "Properly Formatted HTML" ?></p>
                            </body>
                        </html>\n
                    PHP
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
        ]);
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
        ]);
    }

    public function getPriority(): int {
        return 1000;
    }

    public function getName(): string {
        return 'SkPhpCsFixers/html_format';
    }

    public function supports(\SplFileInfo $file): bool {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool {
        return $tokens->isAnyTokenKindsFound([\T_INLINE_HTML]);
    }

    // todo: remove when done developing
    private function logHtmlTokens(array $htmlTokensMap) {
        foreach ($htmlTokensMap as $i => $htmlTokens) {
            Debug::log('Index: ', $i);
            foreach ($htmlTokens as $token) {
                Debug::log($token->kind->name, ' ' . Debug::convertWhitespace($token->content));
            }
            Debug::log('');
        }
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void {
        try {
            $whispaceConfig = $this->whitespacesConfig;
            $treeBulder = new HtmlTreeBuilder();
            $tokenizer = new HtmlTokenizer(
                $treeBulder->add(...)
            );

            // Tokenize the inline html tokens and send to the tree builder
            foreach ($tokens as $i => $token) {
                if ($token->isGivenKind(\T_INLINE_HTML)) {
                    $tokenizer->tokenize($token->getContent());
                }
            }
        } catch (\Throwable $err) {
            if ($err instanceof HtmlParseException) {
                error_log(' HtmlParseError: ' . $err->getMessage());
            }
        }
    }
}
