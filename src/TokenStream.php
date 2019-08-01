<?php

namespace Antlr4;

/**
 * An {@link IntStream} whose symbols are {@link Token} instances.
 */
interface TokenStream extends IntStream
{
    /**
     * Get the {@link Token} instance associated with the value returned by
     * {@link #LA LA(k)}. This method has the same pre- and post-conditions as
     * {@link IntStream#LA}. In addition, when the preconditions of this method
     * are met, the return value is non-null and the value of
     * {@code LT(k).getType()==LA(k)}.
     *
     * @see IntStream#LA
     *
     * @param int $k
     * @return Token
     */
    function LT(int $k): ?Token;

    /**
     * Gets the {@link Token} at the specified {@code index} in the stream. When
     * the preconditions of this method are met, the return value is non-null.
     *
     * <p>The preconditions for this method are the same as the preconditions of
     * {@link IntStream#seek}. If the behavior of {@code seek(index)} is
     * unspecified for the current state and given {@code index}, then the
     * behavior of this method is also unspecified.</p>
     *
     * <p>The symbol referred to by {@code index} differs from {@code seek()} only
     * in the case of filtering streams where {@code index} lies before the end
     * of the stream. Unlike {@code seek()}, this method does not adjust
     * {@code index} to point to a non-ignored symbol.</p>
     *
     * @param int $index
     * @return Token
     */
    function get(int $index): Token;

    /**
     * Gets the underlying {@link TokenSource} which provides tokens for this
     * stream.
     */
    function tokenSource(): TokenSource;

    /**
     * Return the text of all tokens within the specified {@code interval}. This
     * method behaves like the following code (including potential exceptions
     * for violating preconditions of {@link #get}, but may be optimized by the
     * specific implementation.
     *
     * <pre>
     * TokenStream stream = ...;
     * String text = "";
     * for (int i = interval.a; i &lt;= interval.b; i++) {
     *   text += stream.get(i).getText();
     * }
     * </pre>
     *
     * @param Interval $interval The interval of tokens within this stream to get text for.
     * @return string The text of all tokens within the specified interval in this stream.
     */
    function getTextByInterval(Interval $interval): string;

    /**
     * Return the text of all tokens in the stream. This method behaves like the
     * following code, including potential exceptions from the calls to
     * {@link IntStream#size} and {@link #getText(Interval)}, but may be
     * optimized by the specific implementation.
     *
     * <pre>
     * TokenStream stream = ...;
     * String text = stream.getText(new Interval(0, stream.size()));
     * </pre>
     *
     * @return string The text of all tokens in the stream.
     */
    function getText() : string;

    /**
     * Return the text of all tokens in the source interval of the specified
     * context. This method behaves like the following code, including potential
     * exceptions from the call to {@link #getText(Interval)}, but may be
     * optimized by the specific implementation.
     *
     * <p>If {@code ctx.getSourceInterval()} does not return a valid interval of
     * tokens provided by this stream, the behavior is unspecified.</p>
     *
     * <pre>
     * TokenStream stream = ...;
     * String text = stream.getText(ctx.getSourceInterval());
     * </pre>
     *
     * @param RuleContext $ctx The context providing the source interval of tokens to get text for.
     * @return string The text of all tokens within the source interval of {@code ctx}.
     */
    function getTextByContext(RuleContext $ctx): string;

    /**
     * Return the text of all tokens in this stream between {@code start} and
     * {@code stop} (inclusive).
     *
     * <p>If the specified {@code start} or {@code stop} token was not provided by
     * this stream, or if the {@code stop} occurred before the {@code start}
     * token, the behavior is unspecified.</p>
     *
     * <p>For streams which ensure that the {@link Token#getTokenIndex} method is
     * accurate for all of its provided tokens, this method behaves like the
     * following code. Other streams may implement this method in other ways
     * provided the behavior is consistent with this at a high level.</p>
     *
     * <pre>
     * TokenStream stream = ...;
     * String text = "";
     * for (int i = start.getTokenIndex(); i &lt;= stop.getTokenIndex(); i++) {
     *   text += stream.get(i).getText();
     * }
     * </pre>
     *
     * @param Token $start The first token in the interval to get text for.
     * @param Token $stop The last token in the interval to get text for (inclusive).
     * @return string The text of all tokens lying between the specified {@code start} and {@code stop} tokens.
     */
    function getTextByTokens(Token $start, Token $stop): string;
}