<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Tests\Fixer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tests\Test\AbstractFixerTestCase as AbstractPhpCsFixerTestCase;

/**
 * AbstractFixerTestCase class.
 *
 * We do this just so we can include php-cs-fixer tests without
 * having to look at intelephense errors in our tests since
 * intelephense by default excludes test directories.
 */
abstract class AbstractFixerTestCase extends AbstractPhpCsFixerTestCase {
    abstract protected function createCustomFixer(): FixerInterface;

    abstract public static function provideFixCases(): iterable;

    protected function createFixer(): FixerInterface {
        return $this->createCustomFixer();
    }

    /**
     * @dataProvider provideFixCases
     *
     * @param string $expected
     * @param ?string $input
     * @param ?array $config
     */
    public function testFix(string $expected, ?string $input = null, ?array $config = null): void {
        if ($config) {
            $this->fixer->configure($config);
        }

        $this->doTest($expected, $input);
    }
}
