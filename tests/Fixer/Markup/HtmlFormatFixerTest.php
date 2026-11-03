<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Tests\Fixer\Markup;

use PhpCsFixer\Fixer\FixerInterface;
use SkPhpCsFixers\Fixer\Markup\HtmlFormatFixer;
use SkPhpCsFixers\Tests\Fixer\AbstractFixerTestCase;

final class HtmlFormatFixerTest extends AbstractFixerTestCase {
    protected function createCustomFixer(): FixerInterface {
        return new HtmlFormatFixer();
    }

    public static function provideFixCases(): iterable {
        yield 'Things are fine' => [
            <<<'EXPECTED'
                <html>
                    <body>
                        <p><?php echo "hello" ?></p>
                    </body>
                </html>
            EXPECTED,
            <<<'INPUT'
                <html>
                    <body>
                        <p><?php echo "hello" ?> </p>
                    </body>
                </html>
            INPUT,
        ];
    }
}
