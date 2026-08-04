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

    /**
     * Formats the html token based on kind.
     *
     * @param HtmlToken $token The html token to format
     * @param HtmlFormatData $data The format data
     */
    public function handleToken(HtmlToken $token, HtmlFormatData $data): string {
        switch ($token->kind) {
            case HT::HtmlData:
                return $this->handleHtmlData($token, $data);
            case HT::HtmlOpenTagStart:
                return $this->handleHtmlOpenTagStart($token, $data);
            case HT::HtmlOpenTagEnd:
                return $this->handleHtmlOpenTagEnd($token, $data);
            case HT::HtmlCloseTagStart:
                return $this->handleHtmlCloseTagStart($token, $data);
            case HT::HtmlCloseTagEnd:
                return $this->handleHtmlCloseTagEnd($token, $data);
            case HT::HtmlTagSolidus:
                return $this->handleHtmlCloseTagEnd($token, $data);
            case HT::HtmlOpenTagName:
                return $this->handleHtmlOpenTagName($token, $data);
            case HT::HtmlCloseTagName:
                return $this->handleHtmlCloseTagName($token, $data);
            case HT::HtmlAttributeName:
                return $this->handleHtmlAttributeName($token, $data);
            case HT::HtmlAttributeEquals:
                return $this->handleHtmlAttributeEquals($token, $data);
            case HT::HtmlAttributeQuote:
                return $this->handleHtmlAttributeQuote($token, $data);
            case HT::HtmlAttributeValue:
                return $this->handleHtmlAttributeValue($token, $data);
            case HT::HtmlDoctypeStart:
                return $this->handleHtmlDoctypeStart($token, $data);
            case HT::HtmlDoctypeEnd:
                return $this->handleHtmlDoctypeEnd($token, $data);
            case HT::HtmlDoctypeName:
                return $this->handleHtmlDoctypeName($token, $data);
            case HT::HtmlDoctypeIdentifierName:
                return $this->handleHtmlDoctypeIdentifierName($token, $data);
            case HT::HtmlDoctypeIdentifierQuote:
                return $this->handleHtmlDoctypeIdentifierQuote($token, $data);
            case HT::HtmlDoctypeIdentifierValue:
                return $this->handleHtmlDoctypeIdentifierValue($token, $data);
            case HT::HtmlCdata:
                return $this->handleHtmlCdata($token, $data);
            case HT::HtmlComment:
                return $this->handleHtmlComment($token, $data);
            case HT::HtmlRawtext:
                return $this->handleHtmlRawtext($token, $data);
            case HT::Whitespace:
                return $this->handleWhitespace($token, $data);
            default:
                throw new \RuntimeException("HtmlFormatter: attempt to format token of unknown kind {$token->kind->name}");
        }
    }

    /**
     * Formats the HT::HtmlData token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlData(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlOpenTagStart token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlOpenTagStart(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlOpenTagEnd token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlOpenTagEnd(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlCloseTagStart token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCloseTagStart(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlCloseTagEnd token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCloseTagEnd(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlSolidus token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlSolidus(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlOpenTagName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlOpenTagName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlCloseTagName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCloseTagName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlAttributeName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlAttributeEquals token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeEquals(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlAttributeQuote token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeQuote(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlAttributeValue token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlAttributeValue(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlDoctypeStart token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeStart(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlDoctypeEnd token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeEnd(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlDoctypeName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlDoctypeIdentifierName token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeIdentifierName(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlDoctypeIdentifierQuote token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeIdentifierQuote(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlDoctypeIdentifierValue token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlDoctypeIdentifierValue(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlCdata token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlCdata(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlComment token kind.
     *
     * @param HtmlToken $token The token to format
     * @param HtmlFormatData $data The current format data
     */
    private function handleHtmlComment(HtmlToken $token, HtmlFormatData $data): string {
        return $token->content;
    }

    /**
     * Formats the HT::HtmlRawtext token kind.
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
                    HT::HtmlCloseTagStart,
                    HT::HtmlCloseTagName,
                    HT::HtmlAttributeEquals,
                ])
                || $nexttoken?->isGivenKind([
                    HT::HtmlOpenTagEnd,
                    HT::HtmlCloseTagEnd,
                    HT::HtmlAttributeEquals,
                ])
            ) {
                return '';
            }

            return ' ';
        }

        // Handle open and close tags
        if (
            $hasNewline && $prevtoken?->isGivenKind([
                HT::HtmlOpenTagEnd,
                HT::HtmlCloseTagEnd,
                HT::HtmlDoctypeEnd,
                HT::HtmlCdata,
                HT::HtmlComment,
                HT::HtmlRawtext,
            ])
        ) {
            if ($prevtoken?->isGivenKind(HT::HtmlOpenTagEnd)) {
                $indent->increaseDepth();
            }

            if ($prevtoken?->isGivenKind(HT::HtmlCloseTagEnd)) {
                $indent->decreaseDepth();
            }

            return $indent->getLineEndIndent();
        }

        return $token->content;
    }
}
