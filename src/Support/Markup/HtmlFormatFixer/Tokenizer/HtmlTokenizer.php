<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer\Tokenizer;

use SkPhpCsFixers\Traits\AsciiHelpersTrait;

enum State {
    case Data;
    case OpenTagStart;
    case OpenTagName;
    case BetweenTagAttributes;
    case AttributeName;
    case BeforeAttributeValue;
    case AttributeValueDoubleQuoted;
    case AttributeValueSingleQuoted;
    case AttributeValueUnquoted;
    case SelfClosingTagStart;
    case CloseTagStart;
    case CloseTagName;
    case AfterCloseTagName;
    case MarkupDeclarationStart;
    case BetweenDoctypeAttributes;
    case DoctypeName;
    case DoctypeIdentifierValueDoubleQuoted;
    case DoctypeIdentifierValueSingleQuoted;
    case Comment;
    case Cdata;
    case Rawtext;
}

/**
 * HtmlTkenizer class.
 *
 * Tokenizer for html strings.
 */
final class HtmlTokenizer {
    use AsciiHelpersTrait;

    private State $state = State::Data;
    private string $input = '';
    private string $buffer = '';
    private int $pos = 0;
    private int $len = 0;
    private ?OpenTag $openTag = null;

    public function __construct(
        private \Closure $emitter,
    ) {
    }

    public const VOID_TAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    public const RAWTEXT_TAGS = [
        'script',
        'style',
        'textarea',
        'title',
        'xmp',
        'iframe',
        'noembed',
        'noframes',
    ];

    /**
     * Gets the state.
     */
    public function getState(): State {
        return $this->state;
    }

    /**
     * Sets the state.
     *
     * @param State $state The State to set
     */
    public function setState(State $state): void {
        $this->state = $state;
    }

    /**
     * Tokenizes the input string.
     *
     * @param string $input The string of html you want to tokenize
     */
    public function tokenize(string $input): void {
        $this->input = $input;
        $this->len = strlen($input);
        $this->pos = 0;

        while ($this->pos < $this->len) {
            $this->handleChar($this->getChar());

            ++$this->pos;
        }

        $this->cleanup();
    }

    /**
     * Cleans up buffer and pending tokens after a tokenization run.
     */
    public function cleanup(): void {
        // todo: handle eof final run cleanup.
        switch ($this->state) {
            case State::Data:
                $this->emit(HT::Data);

                break;
            case State::OpenTagStart:
                // assume break involves tag name
                $this->emit(HT::OpenTagStart);
                $this->openTag = new OpenTag();
                $this->setState(State::OpenTagName);

                break;
            case State::OpenTagName:
                $this->emit(HT::OpenTagName);

                break;
            case State::BetweenTagAttributes:
                break;
            case State::AttributeName:
                $this->emit(HT::AttributeName);

                break;
            case State::BeforeAttributeValue:
                break;
            case State::AttributeValueDoubleQuoted:
                $this->emit(HT::AttributeValue);

                break;
            case State::AttributeValueSingleQuoted:
                $this->emit(HT::AttributeValue);

                break;
            case State::AttributeValueUnquoted:
                $this->emit(HT::AttributeValue);

                break;
            case State::SelfClosingTagStart:
                break;
            case State::CloseTagStart:
                // todo: assume break involves tag name?
                $this->emit(HT::TagSolidus);
                $this->setState(State::CloseTagName);

                break;
            case State::CloseTagName:
                $this->emit(HT::CloseTagName);

                break;
            case State::MarkupDeclarationStart:
                // todo: handle <! from buffer

                break;
            case State::BetweenDoctypeAttributes:
                break;
            case State::DoctypeName:
                $this->emit(HT::DoctypeName);

                break;
            case State::DoctypeIdentifierValueDoubleQuoted:
                $this->emit(HT::DoctypeIdentifierValue);

                break;
            case State::DoctypeIdentifierValueSingleQuoted:
                $this->emit(HT::DoctypeIdentifierValue);

                break;
            case State::Comment:
                $this->emit(HT::Comment);

                break;
            case State::Cdata:
                $this->emit(HT::Cdata);

                break;
            case State::Rawtext:
                $this->emit(HT::Rawtext);

                break;
            default:
                return;
        }

        if ('' !== $this->buffer) {
            throw new \RuntimeException("Buffer has remaining content '{$this->buffer}' after tokenization run.");
        }
    }

    /**
     * Processes the current character with the current state.
     *
     * @param string $char The current character
     */
    private function handleChar(string $char): void {
        switch ($this->state) {
            case State::Data:
                $this->handleData($char);

                break;
            case State::OpenTagStart:
                $this->handleOpenTagStart($char);

                break;
            case State::OpenTagName:
                $this->handleOpenTagName($char);

                break;
            case State::BetweenTagAttributes:
                $this->handleBetweenTagAttributes($char);

                break;
            case State::AttributeName:
                $this->handleAttributeName($char);

                break;
            case State::BeforeAttributeValue:
                $this->handleBeforeAttributeValue($char);

                break;
            case State::AttributeValueDoubleQuoted:
                $this->handleAttributeValueDoubleQuoted($char);

                break;
            case State::AttributeValueSingleQuoted:
                $this->handleAttributeValueSingleQuoted($char);

                break;
            case State::AttributeValueUnquoted:
                $this->handleAttributeValueUnquoted($char);

                break;
            case State::SelfClosingTagStart:
                $this->handleSelfClosingTagStart($char);

                break;
            case State::CloseTagStart:
                $this->handleCloseTagStart($char);

                break;
            case State::CloseTagName:
                $this->handleCloseTagName($char);

                break;
            case State::AfterCloseTagName:
                $this->handleAfterCloseTagName($char);

                break;
            case State::MarkupDeclarationStart:
                $this->handleMarkupDeclarationStart($char);

                break;
            case State::BetweenDoctypeAttributes:
                $this->handleBetweenDoctypeAttributes($char);

                break;
            case State::DoctypeName:
                $this->handleDoctypeName($char);

                break;
            case State::DoctypeIdentifierValueDoubleQuoted:
                $this->handleDoctypeIdentifierValueDoubleQuoted($char);

                break;
            case State::DoctypeIdentifierValueSingleQuoted:
                $this->handleDoctypeIdentifierValueSingleQuoted($char);

                break;
            case State::Comment:
                $this->handleComment($char);

                break;
            case State::Cdata:
                $this->handleCdata($char);

                break;
            case State::Rawtext:
                $this->handleRawtext($char);

                break;
            default:
                throw new \RuntimeException("HtmlTokenizer: attempt to run an undefined state {$this->state}");
        }
    }

    /**
     * Sets the position back and sets the state so you can reconsume the same character in a differents state.
     *
     * This function alters the $this->pos. It should be called after all other operations.
     *
     * @param State $state The new state to reconsume
     */
    private function reconsume(State $state): void {
        --$this->pos;
        $this->setState($state);
    }

    /**
     * Consumes and returns the input string the length of the given characters. If $len <= 0 nothing happens and '' is retunred.
     *
     * @param int $len The amount characters to consume
     * @param bool $decrementPos Whether or not to decrement the pos to account for the loop. If set to true call last in the operation (Default true)
     */
    private function consume(int $len, bool $decrementPos = true): string {
        if ($len <= 0) {
            return '';
        }

        $inputChars = substr($this->input, $this->pos, $len);
        $this->pos += $decrementPos ? strlen($inputChars) - 1 : strlen($inputChars);

        return $inputChars;
    }

    /**
     * Appends to the temporary buffer.
     *
     * @param string $chars The characters to append to the buf
     */
    private function appendBuffer(string $chars): void {
        $this->buffer .= $chars;
    }

    /**
     * Clears the buffer and returns the previous buffers content.
     */
    private function flushBuffer(): string {
        $buffer = $this->buffer;
        $this->buffer = '';

        return $buffer;
    }

    /**
     * Gets a character at a specific position of the input.
     *
     * @param int $pos The position you want to access. (Default $this->pos)
     */
    private function getChar(?int $pos = null): string {
        if (null === $pos) {
            $pos = $this->pos;
        }

        if ($pos >= $this->len) {
            throw new \RuntimeException('Attempt to access character outside bounds.');
        }

        return $this->input[$pos];
    }

    /**
     * Gets all whitespace characters from the pos to the next non whitespace character.
     *
     * @param int $pos The position to start at
     */
    private function getConsecutiveWhitespace(int $pos): string {
        $chars = '';

        while ($pos < $this->len && $this->isAsciiWhitespace($this->getChar($pos))) {
            $chars .= $this->getChar($pos);
            ++$pos;
        }

        return $chars;
    }

    /**
     * Checks if a substring starts with.
     *
     * @param int $pos
     * @param string $needle
     * @param bool $case_insensitive
     */
    private function posStartsWith(int $pos, string $needle, bool $case_insensitive = false): bool {
        if ($pos < 0) {
            return false;
        }

        return 0 === substr_compare($this->input, $needle, $pos, strlen($needle), $case_insensitive);
    }

    /**
     * Checks if a substring ends with.
     *
     * @param int $pos
     * @param string $needle
     * @param bool $case_insensitive
     */
    private function posEndsWith(int $pos, string $needle, bool $case_insensitive = false): bool {
        $startingPos = $pos - strlen($needle) + 1;

        return $this->posStartsWith($startingPos, $needle, $case_insensitive);
    }

    /**
     * Creates a token of a kind with the buffer contents and emits it immediately.
     *
     * @param HT $kind The token kind
     */
    private function emit(HT $kind): void {
        if ('' === $this->buffer) {
            return;
        }

        ($this->emitter)(new HtmlToken($kind, $this->flushBuffer()));
    }

    /**
     * Consumes and emits all whitespace until next non whitespace character.
     */
    private function emitConsecutiveWhitespace() {
        $whitespace = $this->getConsecutiveWhitespace($this->pos);
        $this->appendBuffer($this->consume(strlen($whitespace)));

        $this->emit(HT::Whitespace);
    }

    /**
     * Checks if a close tag matches the open tag.
     *
     * @param int $pos The position to check against
     * @param string $tagName The expected tag name
     */
    private function isMatchingCloseTag(int $pos, string $tagName): bool {
        if (!$this->posStartsWith($pos, '</' . $tagName, true)) {
            return false;
        }

        $pos += 2 + strlen($tagName);
        $pos += strlen($this->getConsecutiveWhitespace($pos));

        return $pos < $this->len && '>' === $this->getChar($pos);
    }

    /**
     * Handle State::Data.
     *
     * Referring states and buffer contents
     * - BeforeData: empty
     * - Data: "...data"
     *
     * @param string $char The current character
     */
    private function handleData(string $char): void {
        if ('<' === $char) {
            $this->emit(HT::Data);
            $this->appendBuffer('<');
            $this->setState(State::OpenTagStart);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::OpenTagStart.
     *
     * Referring states and buffer contents
     * - Data: "<"
     *
     * @param string $char The current character
     */
    private function handleOpenTagStart(string $char): void {
        if ('!' === $char) {
            $this->appendBuffer('!');
            $this->setState(State::MarkupDeclarationStart);

            return;
        }

        if ('/' === $char) {
            $this->emit(HT::CloseTagStart);
            $this->appendBuffer('/');
            $this->emit(HT::TagSolidus);
            $this->setState(State::CloseTagStart);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->emit(HT::OpenTagStart);
            $this->openTag = new OpenTag();
            $this->reconsume(State::OpenTagName);

            return;
        }

        $this->reconsume(State::Data);
    }

    /**
     * Handle State::OpenTagName.
     *
     * Referring states and buffer contents
     * - OpenTagName: "...name"
     *
     * @param string $char The current character
     */
    private function handleOpenTagName(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::OpenTagName);
            $this->emitConsecutiveWhitespace();
            $this->setState(State::BetweenTagAttributes);

            return;
        }

        if ('/' === $char) {
            $this->emit(HT::OpenTagName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::OpenTagName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        $this->openTag?->appendName($char);
        $this->appendBuffer($char);
    }

    /**
     * Handle State::BetweenTagAttributes.
     *
     * Referring states and buffer contents
     * - OpenTagName: empty
     * - AttributeName: empty
     * - BeforeAttributeValue: empty
     * - AttributeValueDoubleQuoted: empty
     * - AttributeValueSingleQuoted: empty
     * - AttributeValueUnquoted: empty
     * - SelfClosingTagStart: empty
     *
     * @param string $char The current character
     */
    private function handleBetweenTagAttributes(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('=' === $char) {
            $this->appendBuffer('=');
            $this->emit(HT::AttributeEquals);
            $this->setState(State::BeforeAttributeValue);

            return;
        }

        if ('/' === $char) {
            $this->emit(HT::AttributeName);
            $this->appendBuffer('/');
            $this->emit(HT::TagSolidus);
            $this->setState(State::SelfClosingTagStart);

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::OpenTagEnd);

            if (
                $this->openTag
                && in_array(strtolower($this->openTag->getName()), self::RAWTEXT_TAGS)
            ) {
                $this->setState(State::Rawtext);
            } else {
                $this->setState(State::Data);
            }

            return;
        }

        if ('"' === $char || "'" === $char) {
            $this->reconsume(State::BeforeAttributeValue);

            return;
        }

        $this->reconsume(State::AttributeName);
    }

    /**
     * Handle State::AttributeName.
     *
     * Referring states and buffer contents
     * - BetweenTagAttributes: "...name"
     * - AttributeName: "...name"
     *
     * @param string $char The current character
     */
    private function handleAttributeName(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::AttributeName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('/' === $char || '>' === $char) {
            $this->emit(HT::AttributeName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('=' === $char) {
            $this->emit(HT::AttributeName);
            $this->appendBuffer('=');
            $this->emit(HT::AttributeEquals);
            $this->setState(State::BeforeAttributeValue);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BeforeAttributeValue.
     *
     * Referring states and buffer contents
     * - BetweenTagAttributes: empty
     * - AttributeName: empty
     *
     * @param string $char The current character
     */
    private function handleBeforeAttributeValue(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('>' === $char) {
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('"' === $char) {
            $this->appendBuffer('"');
            $this->emit(HT::AttributeQuote);
            $this->setState(State::AttributeValueDoubleQuoted);

            return;
        }

        if ("'" === $char) {
            $this->appendBuffer("'");
            $this->emit(HT::AttributeQuote);
            $this->setState(State::AttributeValueSingleQuoted);

            return;
        }

        $this->reconsume(State::AttributeValueUnquoted);
    }

    /**
     * Handle State::AttributeValueDoubleQuoted.
     *
     * Referring states and buffer contents
     * - BeforeAttributeValue: empty
     * - AttributeValueDoubleQuoted: "...value"
     *
     * @param string $char The current character
     */
    private function handleAttributeValueDoubleQuoted(string $char): void {
        if ('"' === $char) {
            $this->emit(HT::AttributeValue);
            $this->appendBuffer('"');
            $this->emit(HT::AttributeQuote);
            $this->setState(State::BetweenTagAttributes);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::AttributeValueSingleQuoted.
     *
     * Referring states and buffer contents
     * - BeforeAttributeValue: empty
     * - AttributeValueSingleQuoted: "...value"
     *
     * @param string $char The current character
     */
    private function handleAttributeValueSingleQuoted(string $char): void {
        if ("'" === $char) {
            $this->emit(HT::AttributeValue);
            $this->appendBuffer("'");
            $this->emit(HT::AttributeQuote);
            $this->setState(State::BetweenTagAttributes);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::AttributeValueUnquoted.
     *
     * Referring states and buffer contents
     * - BeforeAttributeValue: empty
     * - AttributeValueUnquoted: "...value"
     *
     * @param string $char The current character
     */
    private function handleAttributeValueUnquoted(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::AttributeValue);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        // todo: possibly handle extra terminators " ' ` = < and maybe /
        if ('>' === $char) {
            $this->emit(HT::AttributeValue);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::SelfClosingTagStart.
     *
     * Referring states and buffer contents
     * - OpenTagName: empty
     * - BetweenTagAttributes: empty
     * - RawtextCloseTagName: empty
     *
     * @param string $char The current character
     */
    private function handleSelfClosingTagStart(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::OpenTagEnd);
            $this->openTag?->setSelfClosing(true);
            $this->setState(State::Data);

            return;
        }

        $this->reconsume(State::BetweenTagAttributes);
    }

    /**
     * Handle State::CloseTagStart.
     *
     * Referring states and buffer contents
     * - OpenTagStart: empty
     *
     * @param string $char The current character
     */
    private function handleCloseTagStart(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->reconsume(State::AfterCloseTagName);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->reconsume(State::CloseTagName);

            return;
        }

        throw new HtmlParseException("Invalid character '{$char}' in CloseTagStart state");
    }

    /**
     * Handle State::CloseTagName.
     *
     * Referring states and buffer contents
     * - CloseTagName: "...name"
     *
     * @param string $char The current character
     */
    private function handleCloseTagName(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::CloseTagName);
            $this->reconsume(State::AfterCloseTagName);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::CloseTagName);
            $this->reconsume(State::AfterCloseTagName);

            return;
        }

        if ('/' === $char) {
            throw new HtmlParseException("Invalid character '{$char}' in CloseTagName state");
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::AfterCloseTagName.
     *
     * Referring states and buffer contents
     * - CloseTagName: "...name"
     *
     * @param string $char The current character
     */
    private function handleAfterCloseTagName(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::CloseTagEnd);
            $this->setState(State::Data);

            return;
        }

        throw new HtmlParseException("Invalid character '{$char}' in CloseTagName state.");
    }

    /**
     * Handle State::MarkupDeclarationStart.
     *
     * Referring states and buffer contents
     * - OpenTagStart: "<!"
     *
     * @param string $char The current character
     */
    private function handleMarkupDeclarationStart(string $char): void {
        if ($this->posStartsWith($this->pos, 'doctype', true)) {
            $this->appendBuffer($this->consume(strlen('doctype')));
            $this->emit(HT::DoctypeStart);
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ($this->posStartsWith($this->pos, '--')) {
            $this->appendBuffer($this->consume(strlen('--')));
            $this->setState(State::Comment);

            return;
        }

        if ($this->posStartsWith($this->pos, '[CDATA[', true)) {
            $this->appendBuffer($this->consume(strlen('[CDATA[')));
            $this->setState(State::Cdata);

            return;
        }

        throw new HtmlParseException("Invalid character '{$char}' in MarkupDeclarationStart state");
    }

    /**
     * Handle State::BetweenDoctypeAttributes.
     *
     * Referring states and buffer contents
     * - MarkupDeclarationStart: empty
     * - DoctypeName: empty
     * - DoctypeIdentifierValueDoubleQuoted: empty
     * - DoctypeIdentifierValueSingleQuoted: empty
     *
     * @param string $char The current character
     */
    private function handleBetweenDoctypeAttributes(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::DoctypeEnd);
            $this->setState(State::Data);

            return;
        }

        if ('"' === $char) {
            $this->appendBuffer('"');
            $this->emit(HT::DoctypeIdentifierQuote);
            $this->setState(State::DoctypeIdentifierValueDoubleQuoted);

            return;
        }

        if ("'" === $char) {
            $this->appendBuffer("'");
            $this->emit(HT::DoctypeIdentifierQuote);
            $this->setState(State::DoctypeIdentifierValueSingleQuoted);

            return;
        }

        if ($this->posStartsWith($this->pos, 'public', true)) {
            $this->appendBuffer($this->consume(strlen('public')));
            $this->emit(HT::DoctypeIdentifierName);

            return;
        }

        if ($this->posStartsWith($this->pos, 'system', true)) {
            $this->appendBuffer($this->consume(strlen('system')));
            $this->emit(HT::DoctypeIdentifierName);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->reconsume(State::DoctypeName);

            return;
        }

        throw new HtmlParseException("Invalid character '{$char}' in BetweenDoctypeAttributes state");
    }

    /**
     * Handle State::DoctypeName.
     *
     * Referring states and buffer contents
     * - BetweenDoctypeAttributes: empty
     * - DoctypeName: "...name"
     *
     * @param string $char The current character
     */
    private function handleDoctypeName(string $char): void {
        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::DoctypeName);
            $this->emitConsecutiveWhitespace();
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::DoctypeName);
            $this->appendBuffer('>');
            $this->emit(HT::DoctypeEnd);
            $this->setState(State::Data);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->appendBuffer($char);

            return;
        }

        throw new HtmlParseException("Invalid character '{$char}' in DoctypeName state");
    }

    /**
     * Handle State::DoctypeIdentifierValueDoubleQuoted.
     *
     * Referring states and buffer contents
     * - BetweenDoctypeAttributes: empty
     * - DoctypeIdentifierValueDoubleQuoted: "...value"
     *
     * @param string $char The current character
     */
    private function handleDoctypeIdentifierValueDoubleQuoted(string $char): void {
        if ('"' === $char) {
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer('"');
            $this->emit(HT::DoctypeIdentifierQuote);
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer('>');
            $this->emit(HT::DoctypeEnd);
            $this->setState(State::Data);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::DoctypeIdentifierValueSingleQuoted.
     *
     * Referring states and buffer contents
     * - BetweenDoctypeAttributes: empty
     * - DoctypeIdentifierValueSingleQuoted: "...value"
     *
     * @param string $char The current character
     */
    private function handleDoctypeIdentifierValueSingleQuoted(string $char): void {
        if ("'" === $char) {
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer("'");
            $this->emit(HT::DoctypeIdentifierQuote);
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer('>');
            $this->emit(HT::DoctypeEnd);
            $this->setState(State::Data);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::Comment.
     *
     * Referring states and buffer contents
     * - MarkupDeclarationStart: empty
     * - Comment: "...comment"
     *
     * @param string $char The current character
     */
    private function handleComment(string $char): void {
        if ('>' === $char && (
            $this->posEndsWith($this->pos, '-->')
            || $this->posEndsWith($this->pos, '--!>')
        )) {
            $this->appendBuffer($char);
            $this->emit(HT::Comment);
            $this->setState(State::Data);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::Cdata.
     *
     * Referring states and buffer contents
     * - MarkupDeclarationStart: "<![CDATA["
     * - Cdata: "...cdata"
     *
     * @param string $char The current character
     */
    private function handleCdata(string $char): void {
        if ($this->posStartsWith($this->pos, ']]>')) {
            $this->appendBuffer($this->consume(strlen(']]>')));
            $this->emit(HT::Cdata);
            $this->setState(State::Data);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::Rawtext.
     *
     * Referring states and buffer contents
     * - RawtextLessThanSign: "...rawtext"
     * - RawtextCloseTagStart: "...rawtext"
     * - RawtextCloseTagName: "...rawtext"
     * - Rawtext: "...rawtext"
     *
     * @param string $char The current character
     */
    private function handleRawtext(string $char): void {
        if (
            '<' === $char
            && null !== $this->openTag
            && $this->isMatchingCloseTag($this->pos, $this->openTag->getName())
        ) {
            $this->emit(HT::Rawtext);
            $this->appendBuffer($char);
            $this->setState(State::OpenTagStart);

            return;
        }

        $this->appendBuffer($char);
    }
}
