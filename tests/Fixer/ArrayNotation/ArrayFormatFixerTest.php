<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Tests\Fixer\ArrayFormat;

use PhpCsFixer\Fixer\FixerInterface;
use SkPhpCsFixers\Fixer\ArrayNotation\ArrayFormatFixer;
use SkPhpCsFixers\Tests\AbstractTestCase;

final class ArrayFormatFixerTest extends AbstractTestCase {
    protected function createCustomFixer(): FixerInterface {
        return new ArrayFormatFixer();
    }

    public static function provideFixCases(): iterable {
        yield from self::withAlternateSyntaxCases(
            'Long Array',
            [
                '[' => 'array(',
                ']' => ')',
            ],
            (static function() {
                yield 'Single line array remains untouched.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [ 1, 2, 3 ];
                        EXPECTED,
                ];

                yield 'Single line nested array remains untouched.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [ "one" => [ 1, 2 ] ];
                        EXPECTED,
                ];

                yield 'Multiline array gets formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            1,
                            2,
                            3
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [1, 2, 3
                                    ];
                        INPUT,
                ];

                yield 'Multiline nested array gets formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            1,
                            2,
                            3 => [
                                "one" => 1
                            ]
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [1, 2, 3 => [
                                    "one" => 1 ]
                                    ];
                        INPUT,
                ];

                yield 'Multiline nested array with siblings gets formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            "a" => [
                                "a_1" => "val_a"
                            ],
                            "b" => [
                                "b_1" => "val_b"
                            ],
                            "c" => [
                                "c_1" => "val_c"
                            ]
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [
                            "a" => [ "a_1" => "val_a"
                        ],
                        "b" => [
                                "b_1" => "val_b" ],
                                        "c" => [
                                            "c_1" => "val_c" ]
                        ];
                        INPUT,
                ];

                yield 'Multiline deeply nested array gets formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            "level_1" => [
                                "level_2" => [
                                    "level_3" => [
                                        "level_4" => [
                                            "level_5" => [
                                                "level_6" => [
                                                    "level_7" => [
                                                        "level_8" => [
                                                            "level_9" => [
                                                                "level_10" => [
                                                                    "deep_value" => "You found me!",
                                                                    "deep_array" => [1, 2, 3, 4, 5],
                                                                    "deep_assoc" => ["key" => "value"],
                                                                    "deep_bool" => true,
                                                                    "deep_null" => null,
                                                                    "deep_float" => 3.14159
                                                                ]
                                                            ]
                                                        ]
                                                    ],
                                                    "branch_a" => [
                                                        "sub_branch_1" => [
                                                            "sub_sub_1" => [
                                                                "data" => "nested data 1",
                                                                "number" => 42,
                                                                "active" => true,
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [
                            "level_1" => [
                              "level_2" => [
                            "level_3" => [
                                "level_4" => [
                              "level_5" => [
                                    "level_6" => [
                                  "level_7" => [
                                "level_8" => [
                                    "level_9" => [
                                  "level_10" => [
                                        "deep_value" => "You found me!",
                                    "deep_array" => [1, 2, 3, 4, 5],
                                        "deep_assoc" => ["key" => "value"],
                                      "deep_bool" => true,
                                        "deep_null" => null,
                                      "deep_float" => 3.14159
                                  ]
                                    ]
                                ]
                                  ],
                          "branch_a" => [
                                    "sub_branch_1" => [
                                  "sub_sub_1" => [
                                        "data" => "nested data 1",
                                      "number" => 42,
                        "active" => true,
                        ],
                        ],
                        ],
                        ],
                        ],
                        ],
                        ],
                        ],
                        ],
                        ];
                        INPUT,
                ];

                yield 'Deep nested associative and indexed array fomatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            [
                                [
                                    [
                                        [
                                            [
                                                [
                                                    [
                                                        "a" => "associated_1",
                                                        0 => "indexed_1",
                                                        1 => "indexed_2"
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [[[[[[[["a" => "associated_1",
                            0 => "indexed_1", 1 => "indexed_2"]
                        ]]]]]]];
                        INPUT,
                ];

                yield 'Multiline array with comments gets formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            "a",
                            "b", // comment
                            "c" // comment
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = ["a",
                                "b", // comment


                                    "c" // comment
                        ];
                        INPUT,
                ];

                yield 'Array with comment between gets formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            "a",
                            "b", /* comment */
                            "c"
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [ "a", "b", /* comment */ "c"
                        ];
                        INPUT,
                ];

                yield 'Nested array with comments gets formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            "a",
                            "b" => [
                                "one", // comment
                            ],
                            "c" // comment
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [ "a",
                            "b" => [ "one", // comment
                                ],
                            "c" // comment
                        ];
                        INPUT,
                ];

                yield 'Empty multiline array gets converted to singleline' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [

                        ];
                        INPUT,
                ];

                yield 'Empty singleline array gets converted to multiline with "on_empty" = "ensure_multiline"' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = [];
                        INPUT,
                    ['on_empty' => 'ensure_multiline'],
                ];

                yield 'Singleline array gets formatted to multiline with "on_singleline" = "ensure_multiline"' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            'a',
                            'b',
                            'c'
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = ['a', 'b', 'c'];
                        INPUT,
                    ['on_singleline' => 'ensure_multiline'],
                ];

                yield 'Nested singleline array gets formatted to multiline with "on_singleline" = "ensure_multiline"' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            'a',
                            'b' => [
                                'a',
                                'b'
                            ],
                            'c'
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = ['a', 'b' => ['a', 'b'], 'c'];
                        INPUT,
                    ['on_singleline' => 'ensure_multiline'],
                ];

                yield 'Nested empty array remains singleline with "on_singleline" = "ensure_multiline"' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            'a',
                            'b' => [],
                            'c'
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = ['a', 'b' => [], 'c'];
                        INPUT,
                    ['on_singleline' => 'ensure_multiline'],
                ];

                yield 'Nested empty array formats to multiline with "on_singleline" = "ensure_multiline" and "on_empty" = "ensure_multiline"' => [
                    <<<'EXPECTED'
                        <?php
                        $arr = [
                            'a',
                            'b' => [
                            ],
                            'c'
                        ];
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        $arr = ['a', 'b' => [], 'c'];
                        INPUT,
                    ['on_singleline' => 'ensure_multiline', 'on_empty' => 'ensure_multiline'],
                ];
            })(),
        );

        yield from self::withAlternateSyntaxCases(
            'Destructuring List',
            [
                '[' => 'list(',
                ']' => ')',
            ],
            (static function() {
                yield 'Multiline destructuring properly formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        [
                            $a,
                            $b,
                            $c
                        ] = $arr;
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        [
                        $a,
                                $b,
                        $c
                        ] = $arr;
                        INPUT,
                ];

                yield 'Multiline destructuring with comments properly formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        [ // comment
                            $a,
                            $b, // comment
                            $c // comment
                        ] = $arr;
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        [ // comment
                        $a,
                                $b, // comment
                        $c // comment
                        ] = $arr;
                        INPUT,
                ];

                yield 'Multiline destructuring with multiple comments on same line properly formatted.' => [
                    <<<'EXPECTED'
                        <?php
                        [ // comment
                            $a,
                            $b, /* comment */ /* comment */
                            $c // comment
                        ] = $arr;
                        EXPECTED,
                    <<<'INPUT'
                        <?php
                        [ // comment
                        $a,
                                $b, /* comment */ /* comment */
                        $c // comment
                        ] = $arr;
                        INPUT,
                ];

                yield 'Destructuring singleline left alone.' => [
                    <<<'EXPECTED'
                        <?php
                        [ $a, $b, $c ] = $arr;
                        EXPECTED,
                ];
            })(),
        );
    }

    /**
     * @param array<string, string> $replacers
     * @param \Generator<string, array{0: string, 1?: string, 2?: array<mixed>}> $cases *
     *
     * @return array<array<string, {0: string, 1?: string, 2?: array}>>
     */
    private static function withAlternateSyntaxCases(string $syntax, array $replacers, \Generator $cases): array {
        $originalSyntaxCases = [];
        $alternateSyntaxCases = [];

        foreach ($cases as $key => $case) {
            $case[0] = strtr($case[0], $replacers);

            if (isset($case[1])) {
                $case[1] = strtr($case[1], $replacers);
            }

            if ('string' === \gettype($key)) {
                if (isset($originalSyntaxCases[$key])) {
                    throw new \Exception("Duplicate test case name - {$key}");
                }

                $originalSyntaxCases[$key] = $case;
                $alternateSyntaxCases["{$syntax}: " . $key] = $case;
            } else {
                $originalSyntaxCases[] = $case;
                $alternateSyntaxCases[] = $case;
            }
        }

        return [...$originalSyntaxCases, ...$alternateSyntaxCases];
    }
}
