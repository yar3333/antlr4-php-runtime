<?php

namespace Antlr4;

/**
 * This interface provides information about the vocabulary used by a
 * recognizer.
 *
 * @see Recognizer#getVocabulary()
 * @author Sam Harwell
 */
interface Vocabulary
{
    /**
     * Returns the highest token type value. It can be used to iterate from
     * zero to that number, inclusively, thus querying all stored entries.
     * @return int the highest token type value
     */
    function getMaxTokenType() : int;

    /**
     * Gets the string literal associated with a token type. The string returned
     * by this method, when not {@code null}, can be used unaltered in a parser
     * grammar to represent this token type.
     *
     * <p>The following table shows examples of lexer rules and the literal
     * names assigned to the corresponding token types.</p>
     *
     * <table>
     *  <tr>
     *   <th>Rule</th>
     *   <th>Literal Name</th>
     *   <th>Java String Literal</th>
     *  </tr>
     *  <tr>
     *   <td>{@code THIS : 'this';}</td>
     *   <td>{@code 'this'}</td>
     *   <td>{@code "'this'"}</td>
     *  </tr>
     *  <tr>
     *   <td>{@code SQUOTE : '\'';}</td>
     *   <td>{@code '\''}</td>
     *   <td>{@code "'\\''"}</td>
     *  </tr>
     *  <tr>
     *   <td>{@code ID : [A-Z]+;}</td>
     *   <td>n/a</td>
     *   <td>{@code null}</td>
     *  </tr>
     * </table>
     *
     * @param int tokenType The token type.
     *
     * @return string The string literal associated with the specified token type, or
     * {@code null} if no string literal is associated with the type.
     */
    function getLiteralName(int $tokenType) : ?string;

    /**
     * Gets the symbolic name associated with a token type. The string returned
     * by this method, when not {@code null}, can be used unaltered in a parser
     * grammar to represent this token type.
     *
     * <p>This method supports token types defined by any of the following
     * methods:</p>
     *
     * <ul>
     *  <li>Tokens created by lexer rules.</li>
     *  <li>Tokens defined in a <code>tokens{}</code> block in a lexer or parser
     *  grammar.</li>
     *  <li>The implicitly defined {@code EOF} token, which has the token type
     *  {@link Token#EOF}.</li>
     * </ul>
     *
     * <p>The following table shows examples of lexer rules and the literal
     * names assigned to the corresponding token types.</p>
     *
     * <table>
     *  <tr>
     *   <th>Rule</th>
     *   <th>Symbolic Name</th>
     *  </tr>
     *  <tr>
     *   <td>{@code THIS : 'this';}</td>
     *   <td>{@code THIS}</td>
     *  </tr>
     *  <tr>
     *   <td>{@code SQUOTE : '\'';}</td>
     *   <td>{@code SQUOTE}</td>
     *  </tr>
     *  <tr>
     *   <td>{@code ID : [A-Z]+;}</td>
     *   <td>{@code ID}</td>
     *  </tr>
     * </table>
     *
     * @param int $tokenType The token type.
     *
     * @return string The symbolic name associated with the specified token type, or
     * {@code null} if no symbolic name is associated with the type.
     */
    function getSymbolicName(int $tokenType) : ?string;

    /**
     * Gets the display name of a token type.
     *
     * <p>ANTLR provides a default implementation of this method, but
     * applications are free to override the behavior in any manner which makes
     * sense for the application. The default implementation returns the first
     * result from the following list which produces a non-{@code null}
     * result.</p>
     *
     * <ol>
     *  <li>The result of {@link #getLiteralName}</li>
     *  <li>The result of {@link #getSymbolicName}</li>
     *  <li>The result of {@link Integer#toString}</li>
     * </ol>
     *
     * @param int tokenType The token type.
     *
     * @return string The display name of the token type, for use in error reporting or
     * other user-visible messages which reference specific token types.
     */
    function getDisplayName(int $tokenType) : string;
}
