<?php
/**
 * Squiz_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

require_once 'PHP/CodeSniffer/Standards/AbstractVariableSniff.php';

/**
 * Squiz_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * Checks the naming of variables and member variables.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Squiz_Sniffs_NamingConventions_ValidVariableNameSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{

    /**
     * Tokens to ignore so that we can find a DOUBLE_COLON.
     *
     * @var array
     */
    private $_ignore = array(
                        T_WHITESPACE,
                        T_COMMENT,
                       );


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens  = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        $phpReservedVars = array(
                            '_SERVER',
                            '_GET',
                            '_POST',
                            '_REQUEST',
                            'GLOBALS',
                           );

        // If it's a php reserved var, then its ok.
        if (in_array($varName, $phpReservedVars) === true) {
            return;
        }

        $objOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);
        if ($tokens[$objOperator]['code'] === T_OBJECT_OPERATOR) {
            // Check to see if we are using a variable from an object.
            $var     = $phpcsFile->findNext(array(T_STRING), ($objOperator + 1));
            $bracket = $objOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($var + 1), null, true);

            if ($tokens[$bracket]['code'] !== T_OPEN_PARENTHESIS) {
                $objVarName = $tokens[$var]['content'];

                // There is no way for us to know if the var is public or private,
                // so we have to ignore a leading underscore if there is one and just
                // check the main part of the variable name.
                $originalVarName = $objVarName;
                if (substr($objVarName, 0, 1) === '_') {
                    $objVarName = substr($objVarName, 1);
                }

                if (PHP_CodeSniffer::isCamelCaps($objVarName, false, true) === false) {
                    $error = "Variable \"$originalVarName\" is not in valid camel caps format";
                    $phpcsFile->addError($error, $var);
                }
            }
        }//end if

        // There is no way for us to know if the var is public or private,
        // so we have to ignore a leading underscore if there is one and just
        // check the main part of the variable name.
        $originalVarName = $varName;
        if (substr($varName, 0, 1) === '_') {
            $objOperator = $phpcsFile->findPrevious(array(T_WHITESPACE), ($stackPtr - 1), null, true);
            if ($tokens[$objOperator]['code'] === T_DOUBLE_COLON) {
                // The variable lives within a class, and is reference like
                // this: MyClass::$_variable, so we don't know its scope.
                $inClass = true;
            } else {
                $inClass = $phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_INTERFACE));
            }

            if ($inClass === true) {
                $varName = substr($varName, 1);
            }
        }

        if (PHP_CodeSniffer::isCamelCaps($varName, false, true) === false) {
            $error = "Variable \"$originalVarName\" is not in valid camel caps format";
            $phpcsFile->addError($error, $stackPtr);
        }

    }//end processVariable()


    /**
     * Processes class member variables.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $varName     = ltrim($tokens[$stackPtr]['content'], '$');
        $memberProps = $phpcsFile->getMemberProperties($stackPtr);
        $public      = ($memberProps['scope'] !== 'private');

        if ($public === true) {
            if (substr($varName, 0, 1) === '_') {
                $scope = ucfirst($memberProps['scope']);
                $error = "$scope member variable \"$varName\" must not contain a leading underscore";
                $phpcsFile->addError($error, $stackPtr);
                return;
            }
        } else {
            if (substr($varName, 0, 1) !== '_') {
                $error = "Private member variable \"$varName\" must contain a leading underscore";
                $phpcsFile->addError($error, $stackPtr);
                return;
            }
        }

        if (PHP_CodeSniffer::isCamelCaps($varName, false, $public) === false) {
            $error = "Variable \"$varName\" is not in valid camel caps format";
            $phpcsFile->addError($error, $stackPtr);
        }

    }//end processMemberVar()


    /**
     * Processes the variable found within a double quoted string.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the double quoted
     *                                        string.
     *
     * @return void
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (preg_match_all('|[^\\\]\$([a-zA-Z0-9_]+)|', $tokens[$stackPtr]['content'], $matches) !== 0) {
            foreach ($matches[1] as $varName) {
                // There is no way for us to know if the var is public or private,
                // so we have to ignore a leading underscore if there is one and just
                // check the main part of the variable name.
                $originalVarName = $varName;
                if (substr($varName, 0, 1) === '_') {
                    if ($phpcsFile->hasCondition($stackPtr, array(T_CLASS, T_INTERFACE)) === true) {
                        $varName = substr($varName, 1);
                    }
                }

                if (PHP_CodeSniffer::isCamelCaps($varName, false, true) === false) {
                    $varName = $match[0];
                    $error   = "Variable \"$originalVarName\" is not in valid camel caps format";
                    $phpcsFile->addError($error, $stackPtr);
                }
            }
        }//end if

    }//end processVariableInString()


}//end class

?>
