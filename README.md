# Custom Fixers for Php Cs Fixer

## Requirements

Php >= 8.3

## Installation

```bash
composer require --dev stevekanger/sk-php-cs-fixers
```

Then you can configure your custom fixer in `.php-cs-fixer.php`. See [Php-cs-fixer custom rules](https://cs.symfony.com/doc/custom_rules.html)

```php
<?php

use SkPhpCsFixers\Fixer\CustomFixer;

require __DIR__ . '/vendor/autoload.php'

$rules = [
    'SkPhpCsFixers/custom_fixer' => true,
];

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRules($rules)
    ->registerCustomFixers([
        new CustomFixer(),
    ]);
```

## Fixers

### Array Format Fixer `SkPhpCsFixers/array_format`

Currently Php-cs-fixers rules only format indents on array. This fixer makes sure arrays are fully formatted.

#### Configuration

- namespace: `SkPhpCsFixers\Fixer\ArrayNotation\ArrayFormatFixer`
- rule name: `SkPhpCsFixers/array_format`

#### Options

| Option        | Default           | Allowed Values                              | Description                              |
| ------------- | ----------------- | ------------------------------------------- | ---------------------------------------- |
| on_singleline | ignore            | ignore, ensure_multiline                    | Handles what to do on singleline arrays. |
| on_empty      | ensure_singleline | ensure_multiline, ensure_singleline, ignore | Handles what to do on empty arrays.      |

#### Examples

```php
<?php
// Unformatted
$arr = [1, 2, 3
            ];

// Formatted
$arr = [1, 2, 3];

// Unformatted
$arr = [1, 2, 3 => [
            "one" => 1 ]
            ];

// Formatted
$arr = [
    1,
    2,
    3 => [
        "one" => 1
    ]
];
```

## License

Licensed under the MIT license. See [LICENSE](https://github.com/stevekanger/sk-php-cs-fixers/blob/main/LICENSE) for more information.
