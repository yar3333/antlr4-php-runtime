<?php

namespace Antlr4;

use Antlr4\Utils\Pair;

class CommonToken extends Token
{
    /**
     * CommonToken constructor.
     * @param Pair<TokenSource, CharStream> $source
     * @param int $type
     * @param int $channel
     * @param int $start
     * @param int $stop
     */
    function __construct(Pair $source=null, int $type=null, int $channel=null, int $start=-1, int $stop=-1)
    {
        parent::__construct();

        $this->source = $source ?? self::EMPTY_SOURCE();
        $this->type = $type;
        $this->channel = $channel ?? Token::DEFAULT_CHANNEL;
        $this->start = $start;
        $this->stop = $stop;
        $this->tokenIndex = -1;

        if ($this->source->a)
        {
            /** @var TokenSource $a */
            $a = $this->source->a;
            $this->line = $a->getLine();
            $this->charPositionInLine = $a->getCharPositionInLine();
        }
        else
        {
            $this->charPositionInLine = -1;
        }
    }

    // An empty {@link Pair} which is used as the default value of
    // {@link //source} for tokens that do not have a source.
    private static $_EMPTY_SOURCE;
    static function EMPTY_SOURCE() : Pair { return self::$_EMPTY_SOURCE ?? self::$_EMPTY_SOURCE = new Pair(null, null); }

    // Constructs a new {@link CommonToken} as a copy of another {@link Token}.
    //
    // <p>
    // If {@code oldToken} is also a {@link CommonToken} instance, the newly
    // constructed token will share a reference to the {@link //text} field and
    // the {@link Pair} stored in {@link //source}. Otherwise, {@link //text} will
    // be assigned the result of calling {@link //getText}, and {@link //source}
    // will be constructed from the result of {@link Token//getTokenSource} and
    // {@link Token//getInputStream}.</p>
    //
    // @param oldToken The token to copy.
    function clone() : CommonToken
    {
        $t = new CommonToken($this->source, $this->type, $this->channel, $this->start, $this->stop);
        $t->tokenIndex = $this->tokenIndex;
        $t->line = $this->line;
        $t->charPositionInLine = $this->charPositionInLine;
        $t->setText($this->getText());
        return $t;
    }

    function getText() : string
    {
        if ($this->_text !== null) return $this->_text;

        $input = $this->getInputStream();
        if ($input === null) return null;

        $n = $input->size();
        if ($this->start < $n && $this->stop < $n) {
            return $input->getText($this->start, $this->stop);
        }

        return "<EOF>";
    }

    function __toString()
    {
        $txt = $this->getText();
        if ($txt !== null) {
            $txt = preg_replace('/\n/', "\\n", $txt);
            $txt = preg_replace('/\r/', "\\r", $txt);
            $txt = preg_replace('/\t/', "\\t", $txt);
        } else {
            $txt = "<no text>";
        }
        return "[@" . $this->tokenIndex . "," . $this->start . ":" . $this->stop . "='" .
            $txt . "',<" . $this->type . ">" .
            ($this->channel > 0 ? ",channel=" . $this->channel : "") . "," .
            $this->line . ":" . $this->charPositionInLine . "]";
    }
}