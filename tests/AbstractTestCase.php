<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Tests;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * AbstractTestCase class.
 *
 * We do this just so we can include php-cs-fixer tests without
 * having to look at intelephense errors in our tests since
 * intelephense by default excludes test directories.
 */
abstract class AbstractTestCase extends AbstractFixerTestCase {
    abstract protected function createCustomFixer(): FixerInterface;

    abstract public static function provideFixCases(): iterable;

    protected function createFixer(): FixerInterface {
        return $this->createCustomFixer();
    }

    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $config = null): void {
        if ($config) {
            $this->fixer->configure($config);
        }

        $this->doTest($expected, $input);
    }
}
