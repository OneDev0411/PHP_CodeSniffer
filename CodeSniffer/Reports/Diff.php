<?php
/**
 * Diff report for PHP_CodeSniffer.
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
 * Diff report for PHP_CodeSniffer.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHP_CodeSniffer_Reports_Diff implements PHP_CodeSniffer_Report
{


    /**
     * Generate a partial report for a single processed file.
     *
     * Function should return TRUE if it printed or stored data about the file
     * and FALSE if it ignored the file. Returning TRUE indicates that the file and
     * its data should be counted in the grand totals.
     *
     * @param array                $report      Prepared report data.
     * @param PHP_CodeSniffer_File $phpcsFile   The file being reported on.
     * @param boolean              $showSources Show sources?
     * @param int                  $width       Maximum allowed line width.
     *
     * @return boolean
     */
    public function generateFileReport(
        $report,
        PHP_CodeSniffer_File $phpcsFile,
        $showSources=false,
        $width=80
    ) {
        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            ob_end_clean();
            echo "\t*** START ADDITIONAL FIXING ***".PHP_EOL;
        }

        // We've gone through the file trying to fix things once, but we often
        // need multiple passes to create a proper diff.
        $fixes = $phpcsFile->fixer->getFixCount();
        while ($fixes > 0) {
            if (PHP_CODESNIFFER_VERBOSITY > 1) {
                echo "\tFixed $fixes violations, starting over".PHP_EOL;
            }

            $contents = $phpcsFile->fixer->getContents();
            //print_r(str_replace("\n", '\n', $contents)."\n\n");
            ob_start();
            $phpcsFile->refreshTokenListeners();
            $phpcsFile->start($contents);
            ob_end_clean();
            /*
            Possibly useful as a fail-safe, but may mask problems with the actual
            fixes being performed.
            $newContents = $phpcsFile->fixer->getContents();
            if ($newContents === $contents) {
                break;
            }
            */
            $fixes = $phpcsFile->fixer->getFixCount();
        }

        $diff = $phpcsFile->fixer->generateDiff();
        if ($diff === '') {
            // Nothing to print.
            return false;
        }

        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo "\t*** END ADDITIONAL FIXING ***".PHP_EOL;
            ob_start();
        }

        echo $diff;
        return true;

    }//end generateFileReport()


    /**
     * Prints all errors and warnings for each file processed.
     *
     * @param string  $cachedData    Any partial report data that was returned from
     *                               generateFileReport during the run.
     * @param int     $totalFiles    Total number of files processed during the run.
     * @param int     $totalErrors   Total number of errors found during the run.
     * @param int     $totalWarnings Total number of warnings found during the run.
     * @param int     $totalFixable  Total number of problems that can be fixed.
     * @param boolean $showSources   Show sources?
     * @param int     $width         Maximum allowed line width.
     * @param boolean $toScreen      Is the report being printed to screen?
     *
     * @return void
     */
    public function generate(
        $cachedData,
        $totalFiles,
        $totalErrors,
        $totalWarnings,
        $totalFixable,
        $showSources=false,
        $width=80,
        $toScreen=true
    ) {
        echo $cachedData;

    }//end generate()


}//end class

?>
