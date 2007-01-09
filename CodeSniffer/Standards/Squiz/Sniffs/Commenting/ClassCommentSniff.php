<?php
/**
 * Parses and verifies the class doc comment.
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

require_once 'PHP/CodeSniffer/Sniff.php';
require_once 'PHP/CodeSniffer/CommentParser/ClassCommentParser.php';

/**
 * Parses and verifies the class doc comment.
 *
 * Verifies that :
 * <ul>
 *  <li>A file doc comment exists.</li>
 *  <li>Short description ends with a full stop.</li>
 *  <li>There is a blank line after the short description.</li>
 *  <li>Each paragraph of the long description ends with a full stop.</li>
 *  <li>There is a blank line between the description and the tags.</li>
 *  <li>Check the format of the since tag (x.x.x).</li>
 * </ul>
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
class Squiz_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_CLASS);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $this->_phpcsFile = $phpcsFile;
        $tokens = $this->_phpcsFile->getTokens();
        $find   = array (
                   T_ABSTRACT,
                   T_WHITESPACE,
                   T_FINAL,
                  );

        // Extract the class comment docblock.
        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

        if ($commentEnd !== false && $tokens[$commentEnd]['code'] === T_COMMENT) {
            $this->_phpcsFile->addError('You must use "/**" style comments for a class comment', $stackPtr);
            return;
        } else if ($commentEnd === false || $tokens[$commentEnd]['code'] !== T_DOC_COMMENT) {
            $this->_phpcsFile->addError('Missing class doc comment', $stackPtr);
            return;
        }
        $commentStart = $phpcsFile->findPrevious(T_DOC_COMMENT, $commentEnd - 1, null, true) + 1;

        $comment = $this->_phpcsFile->getTokensAsString($commentStart, $commentEnd - $commentStart + 1);

        // Parse the class comment docblock.
        try {
            $this->_fp = new PHP_CodeSniffer_CommentParser_ClassCommentParser($comment);
            $this->_fp->parse();
        } catch (PHP_CodeSniffer_CommentParser_ParserException $e) {
            $line = $e->getLineWithinComment() + $commentStart;
            $this->_phpcsFile->addError($e->getMessage(), $line);
            return;
        }

        // No extra newline before short description.
        $comment      = $this->_fp->getComment();
        $short        = rtrim($comment->getShortComment(), "\n");
        $newlineCount = 0;
        $newlineSpan  = strspn($short, "\n");
        if ($short !== '' && $newlineSpan > 0) {
            $line  = ($newlineSpan > 1) ? 'newlines' : 'newline';
            $error = "Extra $line found before class comment short description";
            $phpcsFile->addError($error, $commentStart + 1);
        }
        $newlineCount = substr_count($short, "\n") + 1;

        // Exactly one blank line between short and long description.
        $long = $comment->getLongComment();
        if (!empty($long)) {
            $between        = $comment->getWhiteSpaceBetween();
            $newlineBetween = substr_count($between, "\n");
            if ($newlineBetween !== 2) {
                $error = 'There must be exactly one blank line between descriptions in class comment';
                $phpcsFile->addError($error, $commentStart + $newlineCount + 1);
            }
            $newlineCount += $newlineBetween;
        }

        // Exactly one blank line before tags.
        $tags = $this->_fp->getTagOrders();
        if (count($tags) > 1) {
            $newlineSpan = $comment->getNewlineAfter();
            if ($newlineSpan !== 2) {
                $error = 'There must be exactly one blank line before the tags in file comment';
                if ($long !== '') {
                    $newlineCount += (substr_count($long, "\n") - $newlineSpan + 1);
                }
                $phpcsFile->addError($error, $commentStart + $newlineCount);
                $short = rtrim($short, "\n ");
            }
        }

        // Short description must be single line and end with a full stop.
        $lastChar = $short[strlen($short)-1];
        if (substr_count($short, "\n") !== 0) {
            $error = "Class comment short description must be on a single line";
            $phpcsFile->addError($error, $commentStart + 1);
        }
        if ($lastChar !== '.') {
            $error = "Class comment short description must end with a full stop";
            $phpcsFile->addError($error, $commentStart + 1);
        }

        // Check for unknown/deprecated tags.
        $unknownTags = $this->_fp->getUnknown();
        foreach ($unknownTags as $errorTag) {
            $error = ucfirst($errorTag['tag']).' tag is not allowed in class comment';
            $phpcsFile->addWarning($error, $commentStart + $errorTag['line']);
            return;
        }

        // Check each tag.
        $this->processTags($commentStart, $commentEnd);

    }//end process()


    /**
     * Processes each required or optional tag.
     *
     * @param int $commentStart The position in the stack where the comment started.
     * @param int $commentEnd   The position in the stack where the comment ended.
     *
     * @return void
     */
    protected function processTags($commentStart, $commentEnd)
    {
        $fp        = $this->_fp;
        $foundTags = $fp->getTagOrders();

        // Other tags found.
        foreach ($foundTags as $tagName) {
            if ($tagName !== 'comment' && $tagName !== 'since') {
                $error = 'Only since tag is allowed in class comment';
                $this->_phpcsFile->addWarning($error, $commentEnd);
                break;
            }
        }

        // Since tag missing.
        if (!in_array('since', $foundTags)) {
            $error = "Missing required since tag in class comment";
            $this->_phpcsFile->addError($error, $commentEnd);
            return;
        }

        // Get the line number for current tag.
        $since = $fp->getSince();
        if (is_null($since) || empty($since)) {
            return;
        }
        $errorPos = $commentStart + $since->getLine();

        // Make sure there is no duplicate tag.
        $foundIndexes = array_keys($foundTags, 'since');
        if (count($foundIndexes) > 1) {
            $error = "Only 1 since tag is allowed in class comment";
            $this->_phpcsFile->addError($error, $errorPos);
        }

        // Check spacing.
        if ($since->getContent() !== '') {
            $spacing = substr_count($since->getWhitespaceBeforeContent(), ' ');
            if ($spacing !== 1) {
                $error = "Expected 1 space but found $spacing before version number in since tag";
                $this->_phpcsFile->addError($error, $errorPos);
            }
        }

        // Check content.
        $this->_processSince($errorPos);

    }//end processTags()


    /**
     * Processes the since tag
     *
     * The since tag must have the exact keyword 'release_version'
     * or is in the form x.x.x
     *
     * @param int $errorPos The line number where the error occurs.
     *
     * @return void
     */
    private function _processSince($errorPos)
    {
        $since = $this->_fp->getSince();
        if ($since !== null) {
            $content = $since->getContent();
            if (empty($content)) {
                $error = 'Content missing for since tag in class comment';
                $this->_phpcsFile->addError($error, $errorPos);

            } else if ($content !== '%release_version%') {
                if (preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)/', $content) === 0) {
                    $error = 'Expected version number to be in the form x.x.x in since tag';
                    $this->_phpcsFile->addError($error, $errorPos);
                }
            }
        }

    }//end _processSince()


}//end class
?>