<?php
/**
 * Ensures PHP believes the syntax is clean.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Blaine Schmeisser <blainesch@gmail.com>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Config;

class SyntaxSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OPEN_TAG);

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
    public function process(File $phpcsFile, $stackPtr)
    {
        $phpPath = Config::getExecutablePath('php');
        if ($phpPath === null) {
            return;
        }

        $fileName = $phpcsFile->getFilename();
        $cmd      = "$phpPath -l \"$fileName\" 2>&1";
        $output   = shell_exec($cmd);
var_dump($cmd);
var_dump($output);
        $matches = array();
        if (preg_match('/^.*error:(.*) in .* on line ([0-9]+)/', $output, $matches) === 1) {
            echo "FOUND ERROR\n";
            $error = trim($matches[1]);
            $line  = (int) $matches[2];
            $phpcsFile->addErrorOnLine("PHP syntax error: $error", $line, 'PHPSyntax');
        }
var_dump($matches);

        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);

    }//end process()


}//end class
