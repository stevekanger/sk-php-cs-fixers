<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

use PhpCsFixer\Fixer\IndentationTrait;
use SkPhpCsFixers\Traits\TokenHelpersTrait;
use SkPhpCsFixers\Traits\WhitespaceHelpersTrait;

class HtmlFormatter {
    use WhitespaceHelpersTrait;
    use IndentationTrait;
    use TokenHelpersTrait;

    private ?OpenTag $openTag = null;
    private ?CloseTag $closeTag = null;

    /**
     * Formats the html token based on kind.
     *
     * @param HtmlToken $token The html token to format
     * @param HtmlFormatData $data The format data
     */
    public function handleToken(HtmlToken $token, HtmlFormatData $data): string {
        switch ($token->kind) {
            case HT::Data:
                return $this->handleHtmlData($token, $data);
            case HT::OpenTagStart:
                return $this->handleHtmlOpenTagStart($token, $data);
            case HT::OpenTagEnd:
                return $this->handleHtmlOpenTagEnd($token, $data);
            case HT::CloseTagStart:
                return $this->handleHtmlCloseTagStart($token, $data);
            case HT::CloseTagEnd:
                return $this->handleHtmlCloseTagEnd($token, $data);
            case HT::TagSolidus:
                return $this->handleHtmlCloseTagEnd($token, $data);
            case HT::OpenTagName:
                return $this->handleHtmlOpenTagName($token, $data);
            case HT::CloseTagName:
                return $this->handleHtmlCloseTagName($token, $data);
            case HT::AttributeName:
                return $this->handleHtmlAttributeName($token, $data);
            case HT::AttributeEquals:
                return $this->handleHtmlAttributeEquals($token, $data);
            case HT::AttributeQuote:
                return $this->handleHtmlAttributeQuote($token, $data);
            case HT::AttributeValue:
                return $this->handleHtmlAttributeValue($token, $data);
            case HT::DoctypeStart:
                return $this->handleHtmlDoctypeStart($token, $data);
            case HT::DoctypeEnd:
                return $this->handleHtmlDoctypeEnd($token, $data);
            case HT::DoctypeName:
                return $this->handleHtmlDoctypeName($token, $data);
            case HT::DoctypeIdentifierName:
                return $this->handleHtmlDoctypeIdentifierName($token, $data);
            case HT::DoctypeIdentifierQuote:
                return $this->handleHtmlDoctypeIdentifierQuote($token, $data);
            case HT::DoctypeIdentifierValue:
                return $this->handleHtmlDoctypeIdentifierValue($token, $data);
            case HT::Cdata:
                return $this->handleHtmlCdata($token, $data);
            case HT::Comment:
                return $this->handleHtmlComment($token, $data);
            case HT::Rawtext:
                return $this->handleHtmlRawtext($token, $data);
            case HT::Whitespace:
                return $this->handleWhitespace($token, $data);
            default:
                throw new \RuntimeException("HtmlFormatter: attempt to format token of unknown kind {$token->kind->name}");
        }
    }

    /**
     * Formats the HT::Data token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlData(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::OpenTagStart token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlOpenTagStart(HtmlToken $token, HtmlFormatData $data): string {
        $this->openTag = new OpenTag();

        return $token->content;
    }

    /**
     * Formats the HT::OpenTagEnd token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlOpenTagEnd(HtmlToken $token, HtmlFormatData $data): string {
        if ($this->openTag) {
            $data->indent->tagsPush($this->openTag);
        }

        $this->openTag = null;

        return $token->content;
    }

    /**
     * Formats the HT::CloseTagStart token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCloseTagStart(HtmlToken $token, HtmlFormatData $data): string {
        $this->closeTag = new CloseTag();

        return $token->content;
    }

    /**
     * Formats the HT::CloseTagEnd token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCloseTagEnd(HtmlToken $token, HtmlFormatData $data): string {
        $data->indent->tagsPop();
        $this->closeTag = null;

        return $token->content;
    }

    /**
     * Formats the HT::Solidus token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlSolidus(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::OpenTagName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlOpenTagName(HtmlToken $token, HtmlFormatData $data): string {
        $this->openTag?->setName($token->content);

        return $token->content;
    }

    /**
     * Formats the HT::CloseTagName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCloseTagName(HtmlToken $token, HtmlFormatData $data): string {
        $this->closeTag?->setName($token->content);

        return $token->content;
    }

    /**
     * Formats the HT::AttributeName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::AttributeEquals token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeEquals(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::AttributeQuote token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeQuote(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::AttributeValue token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeValue(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::DoctypeStart token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeStart(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::DoctypeEnd token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeEnd(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::DoctypeName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::DoctypeIdentifierName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeIdentifierName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::DoctypeIdentifierQuote token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeIdentifierQuote(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::DoctypeIdentifierValue token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeIdentifierValue(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::Cdata token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCdata(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::Comment token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlComment(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::Rawtext token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlRawtext(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::Whitespace token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleWhitespace(HtmlToken $token, HtmlFormatData $data): string {
        $indent = $data->indent;
        $phpIndex = $data->getPhpIndex();
        $htmlIndex = $data->getHtmlIndex();
        $prevtoken = $data->htmlTokens->getToken($phpIndex, $htmlIndex - 1);
        $nexttoken = $data->htmlTokens->getToken($phpIndex, $htmlIndex + 1);
        $hasNewline = $this->hasNewline($token->content);

        // Handle beginning whitespace after php block
        if (0 === $htmlIndex) {
            if (0 === $phpIndex) {
                return '';
            }

            [$phpCloseTag, $index] = $this->findPrevTokenOfKind($data->phpTokens, \T_CLOSE_TAG, $phpIndex);

            if ($phpCloseTag && $this->hasNewline($phpCloseTag->getContent())) {
                return $indent->getIndent();
            }

            return $token->content;
        }

        // Handle inside tag spacing
        if ($data->inHtmlTag() && !$hasNewline) {
            if (
                $prevtoken?->isGivenKind([
                    HT::CloseTagStart,
                    HT::CloseTagName,
                    HT::AttributeEquals,
                ])
                || $nexttoken?->isGivenKind([
                    HT::OpenTagEnd,
                    HT::CloseTagEnd,
                    HT::AttributeEquals,
                ])
            ) {
                return '';
            }

            return ' ';
        }

        // Handle open and close tags endings
        if (
            $hasNewline && $prevtoken?->isGivenKind([
                HT::OpenTagEnd,
                HT::CloseTagEnd,
                HT::DoctypeEnd,
                HT::Cdata,
                HT::Comment,
                HT::Rawtext,
            ])
        ) {
            return $indent->getLineEndIndent();
        }

        return $token->content;
    }
}
