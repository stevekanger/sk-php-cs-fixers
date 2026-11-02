<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer\Tokenizer;

use SkPhpCsFixers\Traits\AsciiHelpersTrait;

enum State {
    case Data;
    case BeforeData;
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
    case BogusDoctype;
    case Comment;
    case BogusComment;
    case Cdata;
    case BeforeRawtext;
    case Rawtext;
    case RawtextLessThanSign;
    case RawtextCloseTagStart;
    case RawtextCloseTagName;
    case RawtextAfterCloseTagName;
}

enum ExpectedFlag {
    case Any;
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
    private array $tokensPending = [];
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
     * Tokenizes the input string.
     *
     * @param string $input The string of html you want to tokenize
     */
    public function tokenize(string $input): void {
        $this->reset();
        $this->input = $input;
        $this->len = strlen($input);

        while ($this->pos < $this->len) {
            $char = $this->getChar();
            $this->handleCurrentChar($char);

            ++$this->pos;
        }

        $this->cleanup();
    }

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
     * Resets the appropriate values before a token run.
     *
     * This does not change the states. If you need a clean tokenizer create a new instance.
     */
    private function reset() {
        // todo: possible handle buffer, pending, opentag, state
        $this->input = '';
        $this->pos = 0;
        $this->len = 0;
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
     * Trims the trailing whitespace from a string.
     *
     * @param string $str The string to trim
     *
     * @return array{
     *   0: string, // Trimmed string
     *   1: string // Trimmed whitespace chars
     * }
     */
    private function trimTrailingWhitespace(string $str): array {
        $whitespaceChars = '';

        for ($i = strlen($str) - 1; $i >= 0; --$i) {
            $char = $str[$i];

            if ($this->isAsciiWhitespace($char)) {
                $whitespaceChars = $char . $whitespaceChars;
            } else {
                break;
            }
        }

        if ('' === $whitespaceChars) {
            return [$str, ''];
        }

        return [substr($str, 0, -strlen($whitespaceChars)), $whitespaceChars];
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
     * Creates a token of a kind with the buffer contents and emits it immediately.
     *
     * @param HT $kind The token kind
     */
    private function emit(HT $kind): void {
        if (count($this->tokensPending)) {
            $pendingContent = $this->flushPending();

            throw new \RuntimeException("HtmlTokenizer: call to emit {$kind->name} with pending tokens. '{$pendingContent}'");
        }

        if ('' === $this->buffer) {
            return;
        }

        ($this->emitter)(new HtmlToken($kind, $this->flushBuffer()));
    }

    /**
     * Emits all pending tokens.
     */
    private function emitPending(): void {
        foreach ($this->tokensPending as $token) {
            ($this->emitter)($token);
        }

        $this->tokensPending = [];
    }

    /**
     * Adds a token to the $tokensPending array.
     *
     * @param HT $kind The kind of token
     */
    private function addPending(HT $kind): void {
        if ('' === $this->buffer) {
            return;
        }

        $this->tokensPending[] = new HtmlToken($kind, $this->flushBuffer());
    }

    /**
     * Clears all pending tokens and returns their contents.
     */
    private function flushPending(): string {
        $chars = '';

        foreach ($this->tokensPending as $token) {
            $chars .= $token->content;
        }

        $this->tokensPending = [];

        return $chars;
    }

    /**
     * Checks the buffer to make sure the expected characters are available.
     *
     * @param State $state The state
     * @param array $expected Array of expected buffer contents
     */
    private function expectedBuffer(State $state, ExpectedFlag|array $expected): void {
        if ($expected instanceof ExpectedFlag || in_array($this->buffer, $expected)) {
            return;
        }

        $expectedContents = '[';

        for ($i = 0; $i < count($expected); ++$i) {
            $expectedContents .= $expected[$i];

            if ($i !== count($expected) - 1) {
                $expectedContents .= ', ';
            }
        }

        $expectedContents .= ']';

        throw new \RuntimeException("HtmlTokenizer: unexpected buffer contents in state {$state->name}\nExpected: {$expectedContents}\nFound: {$this->buffer}");
    }

    /**
     * Throw a parser error.
     *
     * @param string $msg The error message you want to throw.
     */
    private function parseError(string $msg): void {
        throw new \RuntimeException("HtmlTokenizer parse error: {$msg}");
    }

    /**
     * Processes the current character with the current state.
     *
     * @param string $char The current character
     */
    private function handleCurrentChar(string $char): void {
        switch ($this->state) {
            case State::Data:
                $this->handleData($char);

                break;
            case State::BeforeData:
                $this->handleBeforeData($char);

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
            case State::BogusDoctype:
                $this->handleBogusDoctype($char);

                break;
            case State::Comment:
                $this->handleComment($char);

                break;
            case State::BogusComment:
                $this->handleBogusComment($char);

                break;
            case State::Cdata:
                $this->handleCdata($char);

                break;
            case State::BeforeRawtext:
                $this->handleBeforeRawtext($char);

                break;
            case State::Rawtext:
                $this->handleRawtext($char);

                break;
            case State::RawtextLessThanSign:
                $this->handleRawtextLessThanSign($char);

                break;
            case State::RawtextCloseTagStart:
                $this->handleRawtextCloseTagStart($char);

                break;
            case State::RawtextCloseTagName:
                $this->handleRawtextCloseTagName($char);

                break;
            case State::RawtextAfterCloseTagName:
                $this->handleRawtextAfterCloseTagName($char);

                break;
            default:
                throw new \RuntimeException("HtmlTokenizer: attempt to run an undefined state {$this->state}");
        }
    }

    /**
     * Cleans up the buffer when a php block is encountered or the end of a tokenization run.
     */
    private function cleanup(): void {
        // todo: handle final run cleanup.
        switch ($this->state) {
            case State::Data:
                $this->emit(HT::Data);

                break;
            case State::BeforeData:
                break;
            case State::OpenTagStart:
                // assume break involves tag name
                $this->emitPending();
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
            case State::BogusDoctype:
                // todo: how to handle bogus doctype

                break;
            case State::Comment:
                $this->emit(HT::Comment);

                break;
            case State::BogusComment:
                $this->emit(HT::Comment);

                break;
            case State::Cdata:
                $this->emit(HT::Cdata);

                break;
            case State::BeforeRawtext:
                break;
            case State::Rawtext:
                $this->emit(HT::Rawtext);

                break;
            case State::RawtextLessThanSign:
                break;
            case State::RawtextCloseTagStart:
                break;
            case State::RawtextCloseTagName:
                break;
            case State::RawtextAfterCloseTagName:
                break;
            default:
                return;
        }
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
        $this->expectedBuffer(State::Data, ExpectedFlag::Any);

        if ('<' === $char) {
            [$data, $whitespace] = $this->trimTrailingWhitespace($this->flushBuffer());

            // Pending data
            $this->appendBuffer($data);
            $this->addPending(HT::Data);

            // Pending trailing whitespace
            $this->appendBuffer($whitespace);
            $this->addPending(HT::Whitespace);

            $this->appendBuffer('<');
            $this->setState(State::OpenTagStart);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BeforeData.
     *
     * Referring states and buffer contents
     * - OpenTagName: empty
     * - CloseTagName: empty
     * - BetweenTagAttributes: empty
     * - SelfClosingTagStart: empty
     * - BetweenDoctypeAttributes: empty
     * - DoctypeName: empty
     * - DoctypeIdentifierValueDoubleQuoted: empty
     * - DoctypeIdentifierValueSingleQuoted: empty
     * - BogusDoctype: empty
     * - Comment: empty
     * - BogusComment: empty
     * - Cdata: empty
     * - RawtextCloseTagName: empty
     *
     * @param string $char The current character
     */
    private function handleBeforeData(string $char): void {
        $this->expectedBuffer(State::BeforeData, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        $this->reconsume(State::Data);
    }

    /**
     * Handle State::OpenTagStart.
     *
     * Referring states and buffer contents
     * - Data: "<"
     *
     * Pending Tokens
     * -  ("...data", "...whitespace")
     *
     * @param string $char The current character
     */
    private function handleOpenTagStart(string $char): void {
        $this->expectedBuffer(State::OpenTagStart, ['<']);

        if ('!' === $char) {
            $this->emitPending();
            $this->appendBuffer('!');
            $this->setState(State::MarkupDeclarationStart);

            return;
        }

        if ('/' === $char) {
            $this->emitPending();
            $this->emit(HT::CloseTagStart);
            $this->appendBuffer('/');
            $this->setState(State::CloseTagStart);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->emitPending();
            $this->emit(HT::OpenTagStart);
            $this->openTag = new OpenTag();
            $this->reconsume(State::OpenTagName);

            return;
        }

        $buf = $this->flushBuffer();
        $this->appendBuffer($this->flushPending() . $buf . $char);
        $this->setState(State::Data);
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
        $this->expectedBuffer(State::OpenTagName, ExpectedFlag::Any);

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
        $this->expectedBuffer(State::BetweenTagAttributes, ['']);

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

            // todo: handle case insensitive open tag names
            if (
                $this->openTag
                && in_array($this->openTag->getName(), self::RAWTEXT_TAGS)
            ) {
                $this->setState(State::BeforeRawtext);
            } else {
                $this->setState(State::BeforeData);
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
        $this->expectedBuffer(State::AttributeName, ExpectedFlag::Any);

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
        $this->expectedBuffer(State::BeforeAttributeValue, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('/' === $char || '>' === $char) {
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
        $this->expectedBuffer(State::AttributeValueDoubleQuoted, ExpectedFlag::Any);

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
        $this->expectedBuffer(State::AttributeValueSingleQuoted, ExpectedFlag::Any);

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
        $this->expectedBuffer(State::AttributeValueUnquoted, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::AttributeValue);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        // todo: possibly handle extra terminators " ' ` = <
        if ('>' === $char || '/' === $char) {
            $this->emit(HT::AttributeValue);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        // todo: should there be a alphanumeric check for valid unquoted value
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
        $this->expectedBuffer(State::SelfClosingTagStart, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::OpenTagEnd);
            $this->openTag?->setSelfClosing(true);
            $this->setState(State::BeforeData);

            return;
        }

        $this->reconsume(State::BetweenTagAttributes);
    }

    /**
     * Handle State::CloseTagStart.
     *
     * Referring states and buffer contents
     * - OpenTagStart: "/"
     *
     * @param string $char The current character
     */
    private function handleCloseTagStart(string $char): void {
        $this->expectedBuffer(State::CloseTagStart, ['/']);

        // todo: expected buffer flag for whitespace (right now any whitespace will throw expected error)
        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::TagSolidus);
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            // todo: possible parse error but need to handle </<?php echo 'div'>
            $this->reconsume(State::AfterCloseTagName);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->emit(HT::TagSolidus);
            $this->reconsume(State::CloseTagName);

            return;
        }

        $this->parseError("Invalid character {$char} in CloseTagStart state");
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
        $this->expectedBuffer(State::CloseTagName, ExpectedFlag::Any);

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
        $this->expectedBuffer(State::AfterCloseTagName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::CloseTagEnd);
            $this->setState(State::BeforeData);

            return;
        }

        // todo: possible parse error
        /* $this->appendBuffer($char); */
        /* $this->setState(State::Data); */
        $this->parseError("Invalid character '{$char}' in CloseTagName state.");
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
        $this->expectedBuffer(State::MarkupDeclarationStart, ['<!']);

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

        /* $this->setState(State::BogusComment); */
        $this->parseError("Invalid character '{$char}' in MarkupDeclarationStart state");
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
        $this->expectedBuffer(State::BetweenDoctypeAttributes, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::DoctypeEnd);
            $this->setState(State::BeforeData);

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

        /* $this->reconsume(State::BogusDoctype); */
        $this->parseError("Invalid character '{$char}' in BetweenDoctypeAttributes state");
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
        $this->expectedBuffer(State::DoctypeName, ExpectedFlag::Any);

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
            $this->setState(State::BeforeData);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->appendBuffer($char);

            return;
        }

        $this->reconsume(State::BogusDoctype);
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
        $this->expectedBuffer(State::DoctypeIdentifierValueDoubleQuoted, ExpectedFlag::Any);

        if ('"' === $char) {
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer('"');
            $this->emit(HT::DoctypeIdentifierQuote);
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            // todo: possible parse error
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer('>');
            $this->emit(HT::DoctypeEnd);
            $this->setState(State::BeforeData);

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
        $this->expectedBuffer(State::DoctypeIdentifierValueSingleQuoted, ExpectedFlag::Any);

        if ("'" === $char) {
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer("'");
            $this->emit(HT::DoctypeIdentifierQuote);
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            // todo: possible parse error
            $this->emit(HT::DoctypeIdentifierValue);
            $this->appendBuffer('>');
            $this->emit(HT::DoctypeEnd);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BogusDoctype.
     *
     * Referring states and buffer contents
     * - BetweenDoctypeAttributes: "...any"
     * - DoctypeName: "...any"
     *
     * @param string $char The current character
     */
    private function handleBogusDoctype(string $char): void {
        $this->expectedBuffer(State::BogusDoctype, ExpectedFlag::Any);

        if ('>' === $char) {
            // todo: handle emitting buffer but really probably
            // should just be parser error
            $this->appendBuffer($char);
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
        $this->expectedBuffer(State::Comment, ExpectedFlag::Any);

        if ('>' === $char && (
            $this->posEndsWith($this->pos, '-->')
            || $this->posEndsWith($this->pos, '--!>')
        )) {
            $this->appendBuffer($char);
            $this->emit(HT::Comment);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BogusComment.
     *
     * Referring states and buffer contents
     * - OpenTagStart: "...comment"
     * - CloseTagStart: "...comment"
     * - MarkupDeclarationStart: "...comment"
     * - BogusComment: "...comment"
     *
     * @param string $char The current character
     */
    private function handleBogusComment(string $char): void {
        $this->expectedBuffer(State::BogusComment, ExpectedFlag::Any);

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::Comment);
            $this->setState(State::BeforeData);

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
        $this->expectedBuffer(State::Cdata, ExpectedFlag::Any);

        if ($this->posStartsWith($this->pos, ']]>')) {
            $this->appendBuffer($this->consume(strlen(']]>')));
            $this->emit(HT::Cdata);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BeforeRawtext.
     *
     * Referring states and buffer contents
     * - BetweenTagAttributes: empty
     *
     * @param string $char The current character
     */
    private function handleBeforeRawtext(string $char): void {
        $this->expectedBuffer(State::BeforeRawtext, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();
            $this->setState(State::Rawtext);

            return;
        }

        $this->reconsume(State::Rawtext);
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
        $this->expectedBuffer(State::Rawtext, ExpectedFlag::Any);

        if ('<' === $char) {
            if ($this->openTag) {
                [$buffer, $whitespace] = $this->trimTrailingWhitespace($this->flushBuffer());

                // add pending rawtext
                $this->appendBuffer($buffer);
                $this->addPending(HT::Rawtext);

                // add pending trailing whitespace
                $this->appendBuffer($whitespace);
                $this->addPending(HT::Whitespace);

                // add pending close tag start
                $this->appendBuffer($char);
                $this->addPending(HT::CloseTagStart);

                $this->setState(State::RawtextLessThanSign);

                return;
            }
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::RawtextLessThanSign.
     *
     * Referring states and buffer contents
     * - Rawtext: empty
     *
     * Pending Tokens
     * - ("...rawtext", "...whitespace", "<")
     *
     * @param string $char The current character
     */
    private function handleRawtextLessThanSign(string $char): void {
        $this->expectedBuffer(State::RawtextLessThanSign, ['']);

        if ('/' === $char) {
            $this->appendBuffer($char);
            $this->addPending(HT::TagSolidus);
            $this->setState(State::RawtextCloseTagStart);

            return;
        }

        $this->appendBuffer($this->flushPending());
        $this->reconsume(State::Rawtext);
    }

    /**
     * Handle State::RawtextCloseTagStart.
     *
     * Referring states and buffer contents
     * - RawtextLessThanSign: empty
     *
     * Pending Tokens
     * - ("...rawtext", "<", "/", "...possibleWhitespace")
     *
     * @param string $char The current character
     */
    private function handleRawtextCloseTagStart(string $char): void {
        $this->expectedBuffer(State::RawtextCloseTagStart, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->appendBuffer($this->consume(strlen($this->getConsecutiveWhitespace($this->pos))));
            $this->addPending(HT::Whitespace);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->reconsume(State::RawtextCloseTagName);

            return;
        }

        $this->appendBuffer($this->flushPending());
        $this->reconsume(State::Rawtext);
    }

    /**
     * Handle State RawtextCloseTagName.
     *
     * Referring states and buffer contents
     * - RawtextCloseTagStart: empty
     * - RawtextCloseTagName: "...name"
     *
     * Pending Tokens
     * - ("...rawtext", "<", "/", "...possibleWhitespace")
     *
     * @param string $char The current character
     */
    private function handleRawtextCloseTagName(string $char): void {
        $this->expectedBuffer(State::RawtextCloseTagName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->addPending(HT::CloseTagName);
            $this->reconsume(State::RawtextAfterCloseTagName);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->appendBuffer($char);

            return;
        }

        if ('>' === $char) {
            $this->addPending(HT::CloseTagName);
            $this->reconsume(State::RawtextAfterCloseTagName);

            return;
        }

        $buf = $this->flushBuffer();
        $this->appendBuffer($this->flushPending() . $buf);
        $this->reconsume(State::Rawtext);
    }

    /**
     * Handle State RawtextAfterCloseTagName.
     *
     * Referring states and buffer contents
     * - RawtextCloseTagName: empty
     *
     * Pending Tokens
     * - ("...rawtext", "<", "/", "...possibleWhitespace", "...name", "...possibleWhitespace")
     *
     * @param string $char The current character
     */
    private function handleRawtextAfterCloseTagName(string $char): void {
        $this->expectedBuffer(State::RawtextAfterCloseTagName, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $whitespaceChars = $this->getConsecutiveWhitespace($this->pos);
            $this->appendBuffer($this->consume(strlen($whitespaceChars)));
            $this->addPending(HT::Whitespace);

            return;
        }

        if ('>' === $char) {
            $tagName = '';

            for ($i = count($this->tokensPending) - 1; $i >= 0; --$i) {
                if ($this->tokensPending[$i]->isGivenKind(HT::CloseTagName)) {
                    $tagName = $this->tokensPending[$i]->content;
                }
            }

            // todo: case insensitve compare
            if ($this->openTag?->getName() === $tagName) {
                $this->emitPending();
                $this->appendBuffer($char);
                $this->emit(HT::CloseTagEnd);
                $this->openTag = null;
                $this->setState(State::BeforeData);
            } else {
                $this->appendBuffer($this->flushPending());
                $this->reconsume(State::Rawtext);
            }

            return;
        }

        $this->appendBuffer($this->flushPending());
        $this->reconsume(State::Rawtext);
    }
}
