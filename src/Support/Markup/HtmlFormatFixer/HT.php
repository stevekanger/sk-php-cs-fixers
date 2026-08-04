<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

enum HT {
    case HtmlData;
    case HtmlOpenTagStart;
    case HtmlOpenTagEnd;
    case HtmlCloseTagStart;
    case HtmlCloseTagEnd;
    case HtmlTagSolidus;
    case HtmlOpenTagName;
    case HtmlCloseTagName;
    case HtmlAttributeName;
    case HtmlAttributeEquals;
    case HtmlAttributeQuote;
    case HtmlAttributeValue;
    case HtmlDoctypeStart;
    case HtmlDoctypeEnd;
    case HtmlDoctypeName;
    case HtmlDoctypeIdentifierName;
    case HtmlDoctypeIdentifierQuote;
    case HtmlDoctypeIdentifierValue;
    case HtmlCdata;
    case HtmlComment;
    case HtmlRawtext;
    case Whitespace;
}
