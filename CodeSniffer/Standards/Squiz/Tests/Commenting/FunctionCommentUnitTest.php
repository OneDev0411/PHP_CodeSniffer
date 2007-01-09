<?php
/**
 * Unit test class for FunctionCommentSniff.
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

/**
 * Unit test class for FunctionCommentSniff.
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
class Squiz_Tests_Commenting_FunctionCommentUnitTest extends AbstractSniffUnitTest
{


    /**
     * Returns the lines where errors should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of errors that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getErrorList()
    {
        return array(
                10  => 3,
                12  => 3,
                13  => 2,
                14  => 1,
                15  => 1,
                16  => 1,
                28  => 1,
                35  => 3,
                38  => 1,
                41  => 1,
                44  => 1,
                53  => 1,
                54  => 1,
                66  => 1,
                76  => 1,
                87  => 1,
                96  => 1,
                103 => 1,
                109 => 1,
                112 => 2,
                122 => 1,
                123 => 4,
                124 => 2,
                125 => 4,
                126 => 6,
                127 => 3,
                137 => 1,
                139 => 1,
                141 => 1,
                143 => 2,
                156 => 1,
                159 => 1,
                160 => 2,
                168 => 1,
                175 => 1,
                184 => 1,
                185 => 3,
                186 => 1,
                196 => 2,
                199 => 1,
                200 => 1,
                201 => 1,
                216 => 2,
                217 => 2,
               );

    }//end getErrorList()


    /**
     * Returns the lines where warnings should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of warnings that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getWarningList()
    {
        return array(
                203 => 1,
               );

    }//end getWarningList()


}//end class

?>
