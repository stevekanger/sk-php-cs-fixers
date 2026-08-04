<?php

declare(strict_types=1);

namespace SkPhpCsFixers\Support\Markup\HtmlFormatFixer;

use SkPhpCsFixers\Traits\AsciiHelpersTrait;

enum State {
    case Php;
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
    private State $returnState = State::Data;
    private string $input = '';
    private string $buffer = '';
    private int $pos = 0;
    private int $len = 0;
    private array $tokens = [];
    private array $tokensPending = [];
    private int $depthOffset = 0;
    private array $tagStack = [];
    private ?StartTag $currentStartTag = null;
    private ?EndTag $currentEndTag = null;

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
    public function tokenize(string $input): array {
        $this->reset();
        $this->input = $input;
        $this->len = strlen($input);

        while ($this->pos < $this->len) {
            $char = $this->getChar();
            $this->handleCurrentChar($char);

            ++$this->pos;
        }

        $this->cleanup();

        return $this->tokens;
    }

    /**
     * Gets the state.
     *
     * @return The current state of the state machine
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
     * Sets the return state.
     *
     * @param State $returnState The State to set
     */
    public function setReturnState(State $returnState): void {
        $this->returnState = $returnState;
    }

    /**
     * Resets the appropriate values before a token run.
     *
     * This does not change the states. If you need a clean tokenizer create a new instance.
     */
    private function reset() {
        $this->tokens = [];
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
        if (!$pos) {
            $pos = $this->pos;
        }

        if ($pos > $this->len) {
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
     */
    private function posStartsWith(int $pos, string $needle, bool $case_insensitive = false): bool {
        return 0 === substr_compare($this->input, $needle, $pos, strlen($needle), $case_insensitive);
    }

    /**
     * Trims the trailing whitespace from a string.
     *
     * @param string $str The string to trim
     *
     * @return {string, string}
     *     0: Trimmed string
     *     1: Trimmed whitespace characters
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
     * Emits the buffer and trims the trailing whitespace as its own whitespace token.
     *
     * @param HT $tokenType The type of token your trying to emit
     */
    private function emitTrimTrailingWhitespace(HT $tokenType): void {
        [$buffer, $whitespaceChars] = $this->trimTrailingWhitespace($this->buffer);

        $this->buffer = $buffer;
        $this->emit($tokenType);
        $this->appendBuffer($whitespaceChars);
        $this->emit(HT::Whitespace);
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
     * Creates a token and adds it to the tokens array with the buffer contents.
     *
     * @param HT $kind The token kind
     */
    private function emit(HT $kind): void {
        if (count($this->tokensPending)) {
            throw new \RuntimeException("HtmlTokenizer: call to emit {$kind->name} with pending tokens.");
        }

        if ('' === $this->buffer) {
            return;
        }

        $this->tokens[] = new HtmlToken($kind, $this->flushBuffer());
    }

    /**
     * Adds a token to the $tokensPending array.
     *
     * @param HT $kind The kind of token
     */
    private function addPending(HT $kind) {
        if ('' === $this->buffer) {
            return;
        }

        $this->tokensPending[] = new HtmlToken($kind, $this->flushBuffer());
    }

    /**
     * Adds all pending tokens contents to the buffer.
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
     * Emits all pending tokens.
     */
    private function emitPending() {
        foreach ($this->tokensPending as $token) {
            $this->tokens[] = $token;
        }

        $this->tokensPending = [];
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
        switch ($this->state) {
            case State::Data:
                $this->emit(HT::HtmlData);

                break;
            case State::BeforeData:
                break;
            case State::OpenTagStart:
                // todo: assume break involves tag name?
                $this->emitPending();
                $this->emit(HT::HtmlOpenTagStart);
                $this->setState(State::OpenTagName);

                break;
            case State::OpenTagName:
                $this->emit(HT::HtmlOpenTagName);

                break;
            case State::BetweenTagAttributes:
                break;
            case State::AttributeName:
                $this->emit(HT::HtmlAttributeName);

                break;
            case State::BeforeAttributeValue:
                break;
            case State::AttributeValueDoubleQuoted:
                $this->emit(HT::HtmlAttributeValue);

                break;
            case State::AttributeValueSingleQuoted:
                $this->emit(HT::HtmlAttributeValue);

                break;
            case State::AttributeValueUnquoted:
                $this->emit(HT::HtmlAttributeValue);

                break;
            case State::SelfClosingTagStart:
                break;
            case State::CloseTagStart:
                // todo: assume break involves tag name?
                $this->emit(HT::HtmlOpenTagStart);
                $this->setState(State::CloseTagName);

                break;
            case State::CloseTagName:
                $this->emit(HT::HtmlCloseTagName);

                break;
            case State::MarkupDeclarationStart:
                break;
            case State::BetweenDoctypeAttributes:
                break;
            case State::DoctypeName:
                $this->emit(HT::HtmlDoctypeName);

                break;
            case State::DoctypeIdentifierValueDoubleQuoted:
                $this->emit(HT::HtmlDoctypeIdentifierValue);

                break;
            case State::DoctypeIdentifierValueSingleQuoted:
                $this->emit(HT::HtmlDoctypeIdentifierValue);

                break;
            case State::BogusDoctype:
                // todo: how to handle bogus doctype

                break;
            case State::Comment:
                $this->emit(HT::HtmlComment);

                break;
            case State::BogusComment:
                $this->emit(HT::HtmlComment);

                break;
            case State::Cdata:
                $this->emit(HT::HtmlCdata);

                break;
            case State::BeforeRawtext:
                break;
            case State::Rawtext:
                $this->emit(HT::HtmlRawtext);

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
     * @param string $char The current character
     *
     * Referring States
     * BeforeData: empty
     * Data: "...data"
     */
    private function handleData(string $char): void {
        $this->expectedBuffer(State::Data, ExpectedFlag::Any);

        if ('<' === $char) {
            [$data, $whitespace] = $this->trimTrailingWhitespace($this->flushBuffer());

            // Pending data
            $this->appendBuffer($data);
            $this->addPending(HT::HtmlData);

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
     * @param string $char The current character
     *
     * Referring States
     * OpenTagName: empty
     * CloseTagName: empty
     * BetweenTagAttributes: empty
     * SelfClosingTagStart: empty
     * BetweenDoctypeAttributes: empty
     * DoctypeName: empty
     * DoctypeIdentifierValueDoubleQuoted: empty
     * DoctypeIdentifierValueSingleQuoted: empty
     * BogusDoctype: empty
     * Comment: empty
     * BogusComment: empty
     * Cdata: empty
     * RawtextCloseTagName: empty
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
     * @param string $char The current character
     *
     * Referring States
     * Data: "<"
     *
     * Pending ["...data", "...whitespace"]
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
            $this->emit(HT::HtmlCloseTagStart);
            $this->appendBuffer('/');
            $this->setState(State::CloseTagStart);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->emitPending();
            $this->emit(HT::HtmlOpenTagStart);
            $this->currentStartTag = new StartTag();
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
     * @param string $char The current character
     *
     * Referring States
     * OpenTagName: "...name"
     */
    private function handleOpenTagName(string $char): void {
        $this->expectedBuffer(State::OpenTagName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::HtmlOpenTagName);
            $this->emitConsecutiveWhitespace();
            $this->setState(State::BetweenTagAttributes);

            return;
        }

        if ('/' === $char) {
            $this->emit(HT::HtmlOpenTagName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::HtmlOpenTagName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        $this->currentStartTag?->appendName($char);
        $this->appendBuffer($char);
    }

    /**
     * Handle State::BetweenTagAttributes.
     *
     * @param string $char The current character
     *
     * Referring States
     * OpenTagName: empty
     * AttributeName: empty
     * BeforeAttributeValue: empty
     * AttributeValueDoubleQuoted: empty
     * AttributeValueSingleQuoted: empty
     * AttributeValueUnquoted: empty
     * SelfClosingTagStart: empty
     */
    private function handleBetweenTagAttributes(string $char): void {
        $this->expectedBuffer(State::BetweenTagAttributes, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('=' === $char) {
            $this->appendBuffer('=');
            $this->emit(HT::HtmlAttributeEquals);
            $this->setState(State::BeforeAttributeValue);

            return;
        }

        if ('/' === $char) {
            $this->emit(HT::HtmlAttributeName);
            $this->appendBuffer('/');
            $this->emit(HT::HtmlTagSolidus);
            $this->setState(State::SelfClosingTagStart);

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::HtmlOpenTagEnd);

            if ($this->currentStartTag && in_array($this->currentStartTag->getName(), self::RAWTEXT_TAGS)) {
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
     * @param string $char The current character
     *
     * Referring States
     * BetweenTagAttributes: "...name"
     * AttributeName: "...name"
     */
    private function handleAttributeName(string $char): void {
        $this->expectedBuffer(State::AttributeName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::HtmlAttributeName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('/' === $char || '>' === $char) {
            $this->emit(HT::HtmlAttributeName);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('=' === $char) {
            $this->emit(HT::HtmlAttributeName);
            $this->appendBuffer('=');
            $this->emit(HT::HtmlAttributeEquals);
            $this->setState(State::BeforeAttributeValue);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BeforeAttributeValue.
     *
     * @param string $char The current character
     *
     * Referring States
     * BetweenTagAttributes: empty
     * AttributeName: empty
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
            $this->emit(HT::HtmlAttributeQuote);
            $this->setState(State::AttributeValueDoubleQuoted);

            return;
        }

        if ("'" === $char) {
            $this->appendBuffer("'");
            $this->emit(HT::HtmlAttributeQuote);
            $this->setState(State::AttributeValueSingleQuoted);

            return;
        }

        $this->reconsume(State::AttributeValueUnquoted);
    }

    /**
     * Handle State::AttributeValueDoubleQuoted.
     *
     * @param string $char The current character
     *
     * Referring States
     * BeforeAttributeValue: empty
     * AttributeValueDoubleQuoted: "...value"
     */
    private function handleAttributeValueDoubleQuoted(string $char): void {
        $this->expectedBuffer(State::AttributeValueDoubleQuoted, ExpectedFlag::Any);

        if ('"' === $char) {
            $this->emit(HT::HtmlAttributeValue);
            $this->appendBuffer('"');
            $this->emit(HT::HtmlAttributeQuote);
            $this->setState(State::BetweenTagAttributes);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::AttributeValueSingleQuoted.
     *
     * @param string $char The current character
     *
     * Referring States
     * BeforeAttributeValue: empty
     * AttributeValueSingleQuoted: "...value"
     */
    private function handleAttributeValueSingleQuoted(string $char): void {
        $this->expectedBuffer(State::AttributeValueSingleQuoted, ExpectedFlag::Any);

        if ("'" === $char) {
            $this->emit(HT::HtmlAttributeValue);
            $this->appendBuffer("'");
            $this->emit(HT::HtmlAttributeQuote);
            $this->setState(State::BetweenTagAttributes);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::AttributeValueUnquoted.
     *
     * @param string $char The current character
     *
     * Referring States
     * BeforeAttributeValue: empty
     * AttributeValueUnquoted: "...value"
     */
    private function handleAttributeValueUnquoted(string $char): void {
        $this->expectedBuffer(State::AttributeValueUnquoted, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::HtmlAttributeValue);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        if ('>' === $char || '/' === $char) {
            $this->emit(HT::HtmlAttributeValue);
            $this->reconsume(State::BetweenTagAttributes);

            return;
        }

        // todo: should there be a alphanumeric check for valid unquoted value
        $this->appendBuffer($char);
    }

    /**
     * Handle State SelfClosingTagStart.
     *
     * @param string $char The current character
     *
     * Referring States
     * OpenTagName: empty
     * BetweenTagAttributes: empty
     * RawtextCloseTagName: empty
     */
    private function handleSelfClosingTagStart(string $char): void {
        $this->expectedBuffer(State::SelfClosingTagStart, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::HtmlOpenTagEnd);
            $this->currentStartTag?->setSelfClosing(true);
            $this->setState(State::BeforeData);

            return;
        }

        $this->reconsume(State::BetweenTagAttributes);
    }

    /**
     * Handle State::CloseTagStart.
     *
     * @param string $char The current character
     *
     * Referring States
     * OpenTagStart: "/"
     */
    private function handleCloseTagStart(string $char): void {
        $this->expectedBuffer(State::CloseTagStart, ['/']);

        // todo: expected buffer flag for whitespace
        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            // todo: possible parse error but need to handle </<?php echo 'div'>
            $this->reconsume(State::AfterCloseTagName);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->emit(HT::HtmlTagSolidus);
            $this->reconsume(State::CloseTagName);

            return;
        }

        /* $this->reconsume(State::BogusComment); */
        $this->parseError("Invalid character {$char} in CloseTagStart state");
    }

    /**
     * Handle State::CloseTagName.
     *
     * @param string $char The current character
     *
     * Referring States
     * CloseTagName: "...name"
     */
    private function handleCloseTagName(string $char): void {
        $this->expectedBuffer(State::CloseTagName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::HtmlCloseTagName);
            $this->reconsume(State::AfterCloseTagName);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::HtmlCloseTagName);
            $this->reconsume(State::AfterCloseTagName);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::AfterCloseTagName.
     *
     * @param string $char The current character
     *
     * Referring States
     * CloseTagName: "...name"
     */
    private function handleAfterCloseTagName(string $char): void {
        $this->expectedBuffer(State::CloseTagName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::HtmlCloseTagEnd);
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
     * @param string $char The current character
     *
     * Referring States
     * OpenTagStart: "<!"
     */
    private function handleMarkupDeclarationStart(string $char): void {
        $this->expectedBuffer(State::MarkupDeclarationStart, ['<!']);

        if ($this->posStartsWith($this->pos, 'doctype', true)) {
            $this->appendBuffer($this->consume(strlen('doctype')));
            $this->emit(HT::HtmlDoctypeStart);
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
     * @param string $char The current character
     *
     * Referring States
     * MarkupDeclarationStart: empty
     * DoctypeName: empty
     * DoctypeIdentifierValueDoubleQuoted: empty
     * DoctypeIdentifierValueSingleQuoted: empty
     */
    private function handleBetweenDoctypeAttributes(string $char): void {
        $this->expectedBuffer(State::BetweenDoctypeAttributes, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $this->emitConsecutiveWhitespace();

            return;
        }

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::HtmlDoctypeEnd);
            $this->setState(State::BeforeData);

            return;
        }

        if ('"' === $char) {
            $this->appendBuffer('"');
            $this->emit(HT::HtmlDoctypeIdentifierQuote);
            $this->setState(State::DoctypeIdentifierValueDoubleQuoted);

            return;
        }

        if ("'" === $char) {
            $this->appendBuffer("'");
            $this->emit(HT::HtmlDoctypeIdentifierQuote);
            $this->setState(State::DoctypeIdentifierValueSingleQuoted);

            return;
        }

        if ($this->posStartsWith($this->pos, 'public', true)) {
            $this->appendBuffer($this->consume(strlen('public')));
            $this->emit(HT::HtmlDoctypeIdentifierName);

            return;
        }

        if ($this->posStartsWith($this->pos, 'system', true)) {
            $this->appendBuffer($this->consume(strlen('system')));
            $this->emit(HT::HtmlDoctypeIdentifierName);

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
     * @param string $char The current character
     *
     * Referring States
     * BetweenDoctypeAttributes: empty
     * DoctypeName: "...name"
     */
    private function handleDoctypeName(string $char): void {
        $this->expectedBuffer(State::DoctypeName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->emit(HT::HtmlDoctypeName);
            $this->emitConsecutiveWhitespace();
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            $this->emit(HT::HtmlDoctypeName);
            $this->appendBuffer('>');
            $this->emit(HT::HtmlDoctypeEnd);
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
     * @param string $char The current character
     *
     * Referring States
     * BetweenDoctypeAttributes: empty
     * DoctypeIdentifierValueDoubleQuoted: "...value"
     */
    private function handleDoctypeIdentifierValueDoubleQuoted(string $char): void {
        $this->expectedBuffer(State::DoctypeIdentifierValueDoubleQuoted, ExpectedFlag::Any);

        if ('"' === $char) {
            $this->emit(HT::HtmlDoctypeIdentifierValue);
            $this->appendBuffer('"');
            $this->emit(HT::HtmlDoctypeIdentifierQuote);
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            // todo: possible parse error
            $this->emit(HT::HtmlDoctypeIdentifierValue);
            $this->appendBuffer('>');
            $this->emit(HT::HtmlDoctypeEnd);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::DoctypeIdentifierValueSingleQuoted.
     *
     * @param string $char The current character
     *
     * Buffer Contents:
     * BetweenDoctypeAttributes: empty
     * DoctypeIdentifierValueSingleQuoted: "...value"
     */
    private function handleDoctypeIdentifierValueSingleQuoted(string $char): void {
        $this->expectedBuffer(State::DoctypeIdentifierValueSingleQuoted, ExpectedFlag::Any);

        if ("'" === $char) {
            $this->emit(HT::HtmlDoctypeIdentifierValue);
            $this->appendBuffer("'");
            $this->emit(HT::HtmlDoctypeIdentifierQuote);
            $this->setState(State::BetweenDoctypeAttributes);

            return;
        }

        if ('>' === $char) {
            // todo: possible parse error
            $this->emit(HT::HtmlDoctypeIdentifierValue);
            $this->appendBuffer('>');
            $this->emit(HT::HtmlDoctypeEnd);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BogusDoctype.
     *
     * @param string $char The current character
     *
     * Referring States
     * BetweenDoctypeAttributes: "...any"
     * DoctypeName: "...any"
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
     * Handle State Comment.
     *
     * @param string $char The current character
     *
     * Referring States
     * MarkupDeclarationStart: empty
     * Comment: "...comment"
     */
    private function handleComment(string $char): void {
        $this->expectedBuffer(State::Comment, ExpectedFlag::Any);

        if ($this->posStartsWith($this->pos, '-->')) {
            $this->appendBuffer($this->consume(strlen('-->')));
            $this->emit(HT::HtmlComment);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BogusComment.
     *
     * @param string $char The current character
     *
     * Referring States
     * OpenTagStart: "...comment"
     * CloseTagStart: "...comment"
     * MarkupDeclarationStart: "...comment"
     * BogusComment: "...comment"
     */
    private function handleBogusComment(string $char): void {
        $this->expectedBuffer(State::BogusComment, ExpectedFlag::Any);

        if ('>' === $char) {
            $this->appendBuffer('>');
            $this->emit(HT::HtmlComment);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::Cdata.
     *
     * @param string $char The current character
     *
     * Referring States
     * MarkupDeclarationStart: "<[CDATA["
     * Cdata: "...cdata"
     */
    private function handleCdata(string $char): void {
        $this->expectedBuffer(State::Cdata, ExpectedFlag::Any);

        if ($this->posStartsWith($this->pos, ']]>')) {
            $this->appendBuffer($this->consume(strlen(']]>')));
            $this->emit(HT::HtmlCdata);
            $this->setState(State::BeforeData);

            return;
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::BeforeRawtext.
     *
     * @param string $char The current character
     *
     * Referring States
     * BetweenTagAttributes: empty
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
     * @param string $char The current character
     *
     * Referring States
     * RawtextLessThanSign: "...rawtext"
     * RawtextCloseTagStart: "...rawtext"
     * RawtextCloseTagName: "...rawtext"
     * Rawtext: "...rawtext"
     */
    private function handleRawtext(string $char): void {
        $this->expectedBuffer(State::Rawtext, ExpectedFlag::Any);

        if ('<' === $char) {
            if ($this->currentStartTag) {
                [$buffer, $whitespace] = $this->trimTrailingWhitespace($this->flushBuffer());

                // add pending rawtext
                $this->appendBuffer($buffer);
                $this->addPending(HT::HtmlRawtext);

                // add pending trailing whitespace
                $this->appendBuffer($whitespace);
                $this->addPending(Ht::Whitespace);

                // add pending close tag start
                $this->appendBuffer($char);
                $this->addPending(HT::HtmlCloseTagStart);

                $this->setState(State::RawtextLessThanSign);

                return;
            }
        }

        $this->appendBuffer($char);
    }

    /**
     * Handle State::RawtextLessThanSign.
     *
     * @param string $char The current character
     *
     * Referring States
     * Rawtext: empty
     *
     * Pending: ["...rawtext", "...whitespace", "<"]
     */
    private function handleRawtextLessThanSign(string $char): void {
        $this->expectedBuffer(State::RawtextLessThanSign, ['']);

        if ('/' === $char) {
            $this->appendBuffer($char);
            $this->addPending(HT::HtmlTagSolidus);
            $this->setState(State::RawtextCloseTagStart);

            return;
        }

        $this->appendBuffer($this->flushPending());
        $this->reconsume(State::Rawtext);
    }

    /**
     * Handle State::RawtextCloseTagStart.
     *
     * @param string $char The current character
     *
     * Referring States
     * RawtextLessThanSign: empty
     *
     * Pending ["...rawtext", "<", "/", "...possibleWhitespace"]
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
     * @param string $char The current character
     *
     * Referring States
     * RawtextCloseTagStart: empty
     * RawtextCloseTagName: "...name"
     *
     * Pending ["...rawtext", "<", "/", "...possibleWhitespace"]
     */
    private function handleRawtextCloseTagName(string $char): void {
        $this->expectedBuffer(State::RawtextCloseTagName, ExpectedFlag::Any);

        if ($this->isAsciiWhitespace($char)) {
            $this->addPending(HT::HtmlCloseTagName);
            $this->reconsume(State::RawtextAfterCloseTagName);

            return;
        }

        if ($this->isAsciiAlpha($char)) {
            $this->appendBuffer($char);

            return;
        }

        if ('>' === $char) {
            $this->addPending(HT::HtmlCloseTagName);
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
     * @param string $char The current character
     *
     * Referring States
     * RawtextCloseTagName: empty
     *
     * Pending ["...rawtext", "<", "/", "...possibleWhitespace", "...name", "...possibleWhitespace"]
     */
    private function handleRawtextAfterCloseTagName(string $char): void {
        $this->expectedBuffer(State::RawtextAfterCloseTagName, ['']);

        if ($this->isAsciiWhitespace($char)) {
            $whitespaceChars = $this->getConsecutiveWhitespace($this->pos);
            $this->addPending(HT::Whitespace, $this->consume(strlen($whitespaceChars)));

            return;
        }

        if ('>' === $char) {
            $tagName = '';

            for ($i = count($this->tokensPending) - 1; $i >= 0; --$i) {
                if ($this->tokensPending[$i]->isGivenKind(HT::HtmlCloseTagName)) {
                    $tagName = $this->tokensPending[$i]->content;
                }
            }

            if ($this->currentStartTag?->getName() === $tagName) {
                $this->emitPending();
                $this->appendBuffer($char);
                $this->emit(HT::HtmlCloseTagEnd);
                $this->currentStartTag = null;
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
