<?php
/**
 * +------------------------------------------------------------------------+
 * | BSD Licence                                                            |
 * +------------------------------------------------------------------------+
 * | This software is available to you under the BSD license,               |
 * | available in the LICENSE file accompanying this software.              |
 * | You may obtain a copy of the License at                                |
 * |                                                                        |
 * | http://matrix.squiz.net/developer/tools/php_cs/licence                 |
 * |                                                                        |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS    |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT      |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR  |
 * | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT   |
 * | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,  |
 * | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT       |
 * | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,  |
 * | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY  |
 * | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT    |
 * | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE  |
 * | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.   |
 * +------------------------------------------------------------------------+
 * | Copyright (c), 2006 Squiz Pty Ltd (ABN 77 084 670 600).                |
 * | All rights reserved.                                                   |
 * +------------------------------------------------------------------------+
 *
 * @package  PHP_CodeSniffer
 * @category PEAR_Coding_Standards
 * @author   Squiz Pty Ltd
 */

require_once 'PHP/CodeSniffer/Sniff.php';


/**
 * PEAR_Sniffs_Whitespace_ScopeIndentSniff.
 *
 * Checks that control structures are structured correctly, and there contents
 * is indented correctly.
 *
 * @package  PHP_CodeSniffer
 * @category PEAR_Coding_Standards
 * @author   Squiz Pty Ltd
 */
class PEAR_Sniffs_Whitespace_ScopeIndentSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Any scope openers that should not cause a 4 character indent.
     *
     * @var array(int)
     */
    private static $_nonIndentingScopes = array(
                                           T_SWITCH,
                                          );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return PHP_CodeSniffer_Tokens::$scopeOpeners;

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // If this is an inline condition (ie. there is no scope opener), then
        // return, as this is not a new scope.
        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        if ($tokens[$stackPtr]['code'] === T_ELSE) {
            $next = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $stackPtr + 1, null, true);
            // We will handle the T_IF token in another call to process.
            if ($tokens[$next]['code'] === T_IF) {
                return;
            }
        }

        // Find the first token on this line.
        $firstToken = $stackPtr;
        for ($i = $stackPtr; $i >= 0; $i--) {
            // Record the first code token on the line.
            if (in_array($tokens[$i]['code'], PHP_CodeSniffer_Tokens::$emptyTokens) === false) {
                $firstToken = $i;
            }

            // It's the start of the line, so we've found our first php token.
            if ($tokens[$i]['column'] === 1) {
                break;
            }
        }

        // Based on the conditions that surround this token, determine the
        // indent that we expect this current content to be.
        $expectedIndent = $this->_calculateExpectedIndent($tokens, $firstToken);

        if ($tokens[$firstToken]['column'] !== $expectedIndent) {
            $error  = 'Line indented incorrectly. Expected ';
            $error .= ($expectedIndent - 1).' spaces but found ';
            $error .= ($tokens[$firstToken]['column'] - 1).'.';
            $phpcsFile->addError($error, $stackPtr);
        }

        $scopeOpener = $tokens[$stackPtr]['scope_opener'];
        $scopeCloser = $tokens[$stackPtr]['scope_closer'];

        // Some scopes are expected not to have indents.
        if (in_array($tokens[$firstToken]['code'], self::$_nonIndentingScopes) === false) {
            $indent = $expectedIndent + 4;
        } else {
            $indent = $expectedIndent;
        }

        $newline     = false;
        $commentOpen = false;
        $inHereDoc   = false;

        // Only loop over the content beween the opening and closing brace, not
        // the braces themselves.
        for ($i = $scopeOpener + 1; $i < $scopeCloser; $i++) {

            // If this token is another scope, skip it as it will be handled by
            // another call to this sniff.
            if (in_array($tokens[$i]['code'], PHP_CodeSniffer_Tokens::$scopeOpeners) === true) {
                if (isset($tokens[$i]['scope_opener']) === true) {
                    $i = $tokens[$i]['scope_closer'];
                } else {
                    // If this token does not have a scope_opener indice, then
                    // it's probably an inline scope, so let's skip to the next
                    // semicolon. Inline scopes include inline if's, abstract methods etc.
                    $i = $phpcsFile->findNext(T_SEMICOLON, $i, $scopeCloser);
                }
                continue;
            }

            // If this is a HEREDOC then we need to ignore it as the whitespace
            // before the contents within the HEREDOC are considered apart of the content.
            if ($tokens[$i]['code'] === T_START_HEREDOC) {
                $inHereDoc = true;
                continue;
            } else if ($inHereDoc === true) {
                if ($tokens[$i]['code'] === T_END_HEREDOC) {
                    $inHereDoc = false;
                }
                continue;
            }

            if ($tokens[$i]['column'] === 1) {
                // We started a newline.
                $newline = true;
            }

            if ($newline === true && $tokens[$i]['code'] !== T_WHITESPACE) {
                // If we started a newline and we find a token that is not
                // whitespace, then this must be the first token on the line that
                // must be indented.
                $newline    = false;
                $firstToken = $i;

                $column = $tokens[$firstToken]['column'];

                // Check to see if this constant string spans multiple lines.
                // If so, then make sure that the strings on lines other than the
                // first line are indented appropriately, based on their whitespace.
                if (in_array($tokens[$firstToken]['code'], PHP_CodeSniffer_Tokens::$stringTokens) === true) {
                    if (in_array($tokens[$firstToken - 1]['code'], PHP_CodeSniffer_Tokens::$stringTokens) === true) {
                        // If we find a string that directly follows another string
                        // then its just a string that spans multiple lines.
                        $column = strlen($tokens[$firstToken]['content']) - strlen(ltrim($tokens[$firstToken]['content'])) + 1;
                    }
                }

                // This is a special condition for T_DOC_COMMENT and c style
                // comments, which contain whitespace between each line.
                if (in_array($tokens[$firstToken]['code'], array(T_COMMENT, T_DOC_COMMENT)) === true) {

                    $content = trim($tokens[$firstToken]['content']);
                    if (preg_match('|^/\*|', $content) !== 0) {
                        // Check to see if the end of the comment is on the same line
                        // as the start of the comment. If it is, then we don't
                        // have to worry about opening a comment.
                        if (preg_match('|\*/$|', $content) === 0) {
                            // We don't have to calculate the column for the start
                            // of the comment as there is a whitespace token before it.
                            $commentOpen = true;
                        }
                    } else if ($commentOpen === true) {
                        if ($content === '') {
                            // We are in a comment, but this line has nothing on it
                            // so let's skip it.
                            continue;
                        }
                        $column = strlen($tokens[$firstToken]['content']) - strlen(ltrim($tokens[$firstToken]['content'])) + 1;
                        if (preg_match('|\*/$|', $content) !== 0) {
                            $commentOpen = false;
                        }
                    }
                }

                // The token at the start of the line, needs to have its' column
                // greater than the relative indent we set above. If it is less,
                // an error should be shown.
                if ($column < $indent) {
                    $error  = 'Line indented incorrectly. Expected at least ';
                    $error .= ($indent - 1).' spaces, but found ';
                    $error .= ($column - 1).'.';
                    $phpcsFile->addError($error, $firstToken);
                }
            }//end if
        }//end for

    }//end process()


    /**
     * Calculates the expected indent of a token.
     *
     * @param array $tokens   The stack of tokens for this file.
     * @param int   $stackPtr The position of the token to get indent for.
     *
     * @return int
     */
    private function _calculateExpectedIndent(array $tokens, $stackPtr)
    {
        $conditionStack = array();

        // Empty conditions array (top level structure).
        if (empty($tokens[$stackPtr]['conditions']) === true) {
            return 1;
        }

        $conditions     = array_keys($tokens[$stackPtr]['conditions']);
        $firstCondition = array_pop($conditions);

        if ($tokens[$firstCondition]['code'] === T_CASE) {
            // Because cases can have variable indent (currently), we need
            // to determine the correct indent from its column.
            $indent = $tokens[$firstCondition]['column'];
            return $indent + 4;
        }

        foreach ($tokens[$stackPtr]['conditions'] as $id => $condition) {
            // If it's an indenting scope ie. it's not in our array of
            // scopes that don't indent, add it to our condition stack.
            if (in_array($condition, self::$_nonIndentingScopes) === false) {
                $conditionStack[$id] = $condition;
            }
        }

        return (count($conditionStack) * 4) + 1;

    }//end _calculateExpectedIndent()


}//end class

?>
