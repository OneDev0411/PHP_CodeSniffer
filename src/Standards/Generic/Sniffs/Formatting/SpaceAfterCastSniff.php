<?php

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Generic_Sniffs_Formatting_SpaceAfterCastSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Generic_Sniffs_Formatting_SpaceAfterCastSniff.
 *
 * Ensures there is a single space after cast tokens.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class SpaceAfterCastSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return Tokens::$castTokens;

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process($phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
            $error = 'A cast statement must be followed by a single space';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpace');
            if ($fix === true) {
                $phpcsFile->fixer->addContent($stackPtr, ' ');
            }

            $phpcsFile->recordMetric($stackPtr, 'Spacing after cast statement', 0);
            return;
        }

        $phpcsFile->recordMetric($stackPtr, 'Spacing after cast statement', $tokens[($stackPtr + 1)]['length']);

        if ($tokens[($stackPtr + 1)]['length'] !== 1) {
            $error = 'A cast statement must be followed by a single space';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'TooMuchSpace');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($stackPtr + 1), ' ');
            }
        }

    }//end process()


}//end class
