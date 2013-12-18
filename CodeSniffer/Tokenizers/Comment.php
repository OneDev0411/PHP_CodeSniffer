<?php
/**
 * Tokenizes doc block comments.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Tokenizes doc block comments.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHP_CodeSniffer_Tokenizers_Comment
{


    /**
     * Creates an array of tokens when given some PHP code.
     *
     * Starts by using token_get_all() but does a lot of extra processing
     * to insert information about the context of the token.
     *
     * @param string $string  The string to tokenize.
     * @param string $eolChar The EOL character to use for splitting strings.
     *
     * @return array
     */
    public function tokenizeString($string, $eolChar='\n')
    {
        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo "\t\t*** START COMMENT TOKENIZING ***".PHP_EOL;
        }

        $tokens   = array();
        $numChars = strlen($string);

        /*
            Doc block comments start with /*, but typically contain an
            extra star when they are used for function and class comments.
        */

        for ($c = 0; $c < $numChars; $c++) {
            if ($string[$c] !== '/' && $string[$c] !== '*') {
                break;
            }
        }

        $openTag  = substr($string, 0, $c);
        $tokens[] = array(
                     'content' => $openTag,
                     'code'    => T_DOC_COMMENT_OPEN_TAG,
                     'type'    => 'T_DOC_COMMENT_OPEN_TAG',
                    );

        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            $content = str_replace(' ', "\033[30;1m·\033[0m", $openTag);
            echo "\t\tCreate comment token: T_DOC_COMMENT_OPEN_TAG => $content".PHP_EOL;
        }

        /*
            Strip off the close tag so it doesn't interfere with any
            of our comment line processing. The token will be added to the
            stack just before we return it.
        */

        for ($i = ($numChars - 1); $i > $c; $i--) {
            if ($string[$i] !== '/' && $string[$i] !== '*') {
                break;
            }
        }

        $i++;
        $closeTag = array(
                     'content' => substr($string, $i),
                     'code'    => T_DOC_COMMENT_CLOSE_TAG,
                     'type'    => 'T_DOC_COMMENT_CLOSE_TAG',
                    );

        $string   = substr($string, 0, $i);
        $numChars = strlen($string);

        /*
            Process each line of the comment.
        */

        while ($c < $numChars) {
            $lineTokens = $this->_processLine($string, $eolChar, $c, $numChars);
            foreach ($lineTokens as $lineToken) {
                $tokens[] = $lineToken;
                $c       += strlen($lineToken['content']);
                if (PHP_CODESNIFFER_VERBOSITY > 1) {
                    $content = str_replace(' ', "\033[30;1m·\033[0m", $lineToken['content']);
                    $content = str_replace($eolChar, "\033[30;1m\\n\033[0m", $content);
                    $type    = $lineToken['type'];
                    echo "\t\tCreate comment token: $type => $content".PHP_EOL;
                }
            }

            if ($c === $numChars) {
                break;
            }

            // We've started a new line, so process the indent.
            $space = $this->_collectWhitespace($string, $c, $numChars);
            if ($space !== null) {
                $tokens[] = $space;
                if (PHP_CODESNIFFER_VERBOSITY > 1) {
                    $content = str_replace(' ', "\033[30;1m·\033[0m", $space['content']);
                    $type    = $lineToken['type'];
                    echo "\t\tCreate comment token: T_DOC_COMMENT_WHITESPACE => $content".PHP_EOL;
                }

                $c += strlen($space['content']);
                if ($c === $numChars) {
                    break;
                }
            }

            if ($string[$c] === '*') {
                // This is a function or class doc block line.
                $c++;
                $tokens[] = array(
                             'content' => '*',
                             'code'    => T_DOC_COMMENT_STAR,
                             'type'    => 'T_DOC_COMMENT_STAR',
                            );

                if (PHP_CODESNIFFER_VERBOSITY > 1) {
                    echo "\t\tCreate comment token: T_DOC_COMMENT_STAR => *".PHP_EOL;
                }
            }

            // Now we are ready to process the actual content of the line.
            // So round we go.

        }//end while

        $tokens[] = $closeTag;
        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            $content = str_replace(' ', "\033[30;1m·\033[0m", $closeTag['content']);
            echo "\t\tCreate comment token: T_DOC_COMMENT_CLOSE_TAG => $content".PHP_EOL;
        }

        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo "\t\t*** END COMMENT TOKENIZING ***".PHP_EOL;
        }

        return $tokens;

    }//end tokenizeString()


    /**
     * Process a single line of a comment.
     *
     * @param string $string  The comment string being tokenized.
     * @param string $eolChar The EOL character to use for splitting strings.
     * @param int    $start   The position in the string to start processing.
     * @param int    $end     The position in the string to end processing.
     *
     * @return array
     */
    private function _processLine($string, $eolChar, $start, $end)
    {
        $tokens = array();

        // Collect content padding.
        $space = $this->_collectWhitespace($string, $start, $end);
        if ($space !== null) {
            $tokens[] = $space;
            $start   += strlen($space['content']);
        }

        if ($string[$start] === '@') {
            // The content up until the first whitespace is the tag name.
            $tagName = '';
            for ($start; $start < $end; $start++) {
                if ($string[$start] === ' '
                    || $string[$start] === "\t"
                    || $string[$start] === "\n"
                    || $string[$start] === "\r"
                ) {
                    break;
                }

                $tagName .= $string[$start];
            }

            $tokens[] = array(
                         'content' => $tagName,
                         'code'    => T_DOC_COMMENT_TAG,
                         'type'    => 'T_DOC_COMMENT_TAG',
                        );

            // Then there will be some whitespace.
            $space = $this->_collectWhitespace($string, $start, $end);
            if ($space !== null) {
                $tokens[] = $space;
                $start   += strlen($space['content']);
            }
        }//end if

        // Process the rest of the line.
        $eol = $this->_findEOL($string, $eolChar, $start, $end);
        if ($eol > $start) {
            $tokens[] = array(
                         'content' => substr($string, $start, ($eol - $start)),
                         'code'    => T_DOC_COMMENT_STRING,
                         'type'    => 'T_DOC_COMMENT_STRING',
                        );
        }

        $tokens[] = array(
                     'content' => substr($string, $eol, strlen($eolChar)),
                     'code'    => T_DOC_COMMENT_WHITESPACE,
                     'type'    => 'T_DOC_COMMENT_WHITESPACE',
                    );

        return $tokens;

    }//end _processLine()


    /**
     * Collect consecutive whitespace into a single token.
     *
     * @param string $string The comment string being tokenized.
     * @param int    $start  The position in the string to start processing.
     * @param int    $end    The position in the string to end processing.
     *
     * @return array|null
     */
    private function _collectWhitespace($string, $start, $end)
    {
        $space = '';
        for ($start; $start < $end; $start++) {
            if ($string[$start] !== ' ' && $string[$start] !== "\t") {
                break;
            }

            $space .= $string[$start];
        }

        if ($space === '') {
            return null;
        }

        $token = array(
                  'content' => $space,
                  'code'    => T_DOC_COMMENT_WHITESPACE,
                  'type'    => 'T_DOC_COMMENT_WHITESPACE',
                 );

        return $token;

    }//end _collectWhitespace()


    /**
     * Find the position of the next EOL character.
     *
     * @param string $string  The comment string being tokenized.
     * @param string $eolChar The EOL character to use for splitting strings.
     * @param int    $start   The position in the string to start processing.
     * @param int    $end     The position in the string to end processing.
     *
     * @return int
     */
    private function _findEOL($string, $eolChar, $start, $end)
    {
        $eolLen = strlen($eolChar);
        for ($start; $start < $end; $start++) {
            $eolTest = substr($string, $start, $eolLen);
            if ($eolTest === $eolChar) {
                break;
            }
        }

        return $start;

    }//end _findEOL()


}//end class
