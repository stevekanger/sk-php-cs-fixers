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
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\HT;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\HtmlFormatData;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\HtmlFormatter;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\HtmlTokenizer;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\HtmlTokens;
use SkPhpCsFixers\Support\Markup\HtmlFormatFixer\Indent;
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

    protected function applyFix(\SplFileInfo $file, Tokens $phpTokens): void {
        $tokenizer = new HtmlTokenizer();
        $htmlTokens = new HtmlTokens();
        $htmlFormatter = new HtmlFormatter();
        $data = new HtmlFormatData(
            phpTokens: $phpTokens,
            htmlTokens: $htmlTokens,
            indent: new Indent(whitespacesConfig: $this->whitespacesConfig),
        );

        // Tokenize the inline html
        foreach ($phpTokens as $i => $token) {
            if ($token->isGivenKind(\T_INLINE_HTML)) {
                $htmlTokens->add($i, $tokenizer->tokenize($token->getContent()));
            }
        }

        // Format the tokens
        foreach ($phpTokens as $phpIndex => $phpToken) {
            if ($phpToken->isGivenKind(\T_INLINE_HTML)) {
                $formatted = '';

                foreach ($htmlTokens->get($phpIndex) as $htmlIndex => $htmlToken) {
                    $data
                        ->setPhpIndex($phpIndex)
                        ->setHtmlIndex($htmlIndex);

                    if ($htmlToken->isGivenKind([HT::HtmlOpenTagStart, HT::HtmlCloseTagStart])) {
                        $data->setInHtmlTag(true);
                    }

                    if ($htmlToken->isGivenKind([HT::HtmlOpenTagEnd, HT::HtmlCloseTagEnd])) {
                        $data->setInHtmlTag(false);
                    }

                    $formatted .= $htmlFormatter->handleToken($htmlToken, $data);
                }

                $this->tokensReplaceAt($phpTokens, $phpIndex, \T_INLINE_HTML, $formatted);
            }
        }
    }
}
