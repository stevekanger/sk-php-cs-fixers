<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

enum HT {
    case Data;
    case OpenTagStart;
    case OpenTagEnd;
    case CloseTagStart;
    case CloseTagEnd;
    case TagSolidus;
    case OpenTagName;
    case CloseTagName;
    case AttributeName;
    case AttributeEquals;
    case AttributeQuote;
    case AttributeValue;
    case DoctypeStart;
    case DoctypeEnd;
    case DoctypeName;
    case DoctypeIdentifierName;
    case DoctypeIdentifierQuote;
    case DoctypeIdentifierValue;
    case Cdata;
    case Comment;
    case Rawtext;
    case Whitespace;
    case PhpBlock;
}
