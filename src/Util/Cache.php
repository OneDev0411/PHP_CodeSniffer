<?php

namespace PHP_CodeSniffer\Util;

use PHP_CodeSniffer\Autoload;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Util\Common;

/**
 * A class to process command line phpcs scripts.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * A class to process command line phpcs scripts.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Cache
{

    private static $path  = '';
    private static $cache = array();


    public static function load(Config $config, Ruleset $ruleset)
    {
        // Look at every loaded sniff class so far and use their file contents
        // to generate a hash for the code used during the run.
        // At this point, the loaded class list contains the core PHPCS code
        // and all sniffs that have been loaded as part of the run.
        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo PHP_EOL."\tGenerating loaded file list for code hash".PHP_EOL;
        }

        $codeHash = '';
        $classes  = array_keys(Autoload::getLoadedClasses());
        sort($classes);

        $installDir     = dirname(__DIR__);
        $installDirLen  = strlen($installDir);
        $standardDir    = $installDir.DIRECTORY_SEPARATOR.'Standards';
        $standardDirLen = strlen($standardDir);
        foreach ($classes as $file) {
            if (substr($file, 0, $standardDirLen) !== $standardDir) {
                if (substr($file, 0, $installDirLen) === $installDir) {
                    // We are only interested in sniffs here.
                    continue;
                }

                if (PHP_CODESNIFFER_VERBOSITY > 1) {
                    echo "\t\t=> external file: $file".PHP_EOL;
                }
            } else if (PHP_CODESNIFFER_VERBOSITY > 1) {
                echo "\t\t=> internal sniff: $file".PHP_EOL;
            }

            $codeHash .= md5_file($file);
        }

        // Go through the core PHPCS code and add those files to the file
        // hash. This ensure that core PHPCS changes will also invalidate the cache.
        // Note that we ignore sniffs here, and any files that don't affect
        // the outcome of the run.
        $ignore = array(
                   'Standards'  => true,
                   'Exceptions' => true,
                   'Reports'    => true,
                   'Generators' => true,
                  );

        $di = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($installDir),
            0,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($di as $file) {
            // Skip hidden files.
            $filename = $file->getFilename();
            if (substr($filename, 0, 1) === '.') {
                continue;
            }

            $filePath = Common::realpath($file->getPathname());
            if ($filePath === false) {
                continue;
            }

            if (is_dir($filePath) === true) {
                continue;
            }

            $dir = substr($filePath, ($installDirLen + 1));
            $dir = substr($dir, 0, strpos($dir, DIRECTORY_SEPARATOR));
            if (isset($ignore[$dir]) === true) {
                continue;
            }

            if (PHP_CODESNIFFER_VERBOSITY > 1) {
                echo "\t\t=> core file: $file".PHP_EOL;
            }

            $codeHash .= md5_file($file);
        }//end foreach

        $codeHash = md5($codeHash);

        // Along with the code hash, use various settings that can affect
        // the results of a run to create a new hash. This hash will be used
        // in the cache file name.
        $configData = array(
                       'tabWidth' => $config->tabWidth,
                       'encoding' => $config->encoding,
                       'codeHash' => $codeHash,
                      );

        $configString = implode(',', $configData);
        $cacheHash    = substr(sha1($configString), 0, 12);

        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo "\tGenerating cache key data".PHP_EOL;
            echo "\t\t=> tabWidth: ".$configData['tabWidth'].PHP_EOL;
            echo "\t\t=> encoding: ".$configData['encoding'].PHP_EOL;
            echo "\t\t=> codeHash: ".$configData['codeHash'].PHP_EOL;
            echo "\t\t=> cacheHash: $cacheHash".PHP_EOL;
        }

        // Determine the common paths for all files being checked.
        // We can use this to locate an existing cache file, or to
        // determine where to create a new one.
        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo "\tChecking possible cache file paths".PHP_EOL;
        }

        $paths = array();
        foreach ($config->files as $file) {
            $file = Common::realpath($file);
            while ($file !== DIRECTORY_SEPARATOR) {
                if (isset($paths[$file]) === false) {
                    $paths[$file] = 1;
                } else {
                    $paths[$file]++;
                }

                $lastFile = $file;
                $file     = dirname($file);
                if ($file === $lastFile) {
                    // Just in case something went wrong,
                    // we don't want to end up in an inifite loop.
                    break;
                }
            }
        }

        ksort($paths);
        $paths = array_reverse($paths);

        $numFiles  = count($config->files);
        $tmpDir    = sys_get_temp_dir();
        $cacheFile = null;
        foreach ($paths as $file => $count) {
            if ($count !== $numFiles) {
                unset($paths[$file]);
                continue;
            }

            $fileHash = substr(sha1($file), 0, 12);
            $testFile = $tmpDir.DIRECTORY_SEPARATOR."phpcs.$fileHash.$cacheHash.cache";
            if ($cacheFile === null) {
                // This will be our default location if we can't find
                // an existing file.
                $cacheFile = $testFile;
            }

            if (PHP_CODESNIFFER_VERBOSITY > 1) {
                echo "\t\t=> $testFile".PHP_EOL;
                echo "\t\t\t * based on shared location: $file *".PHP_EOL;
            }

            if (file_exists($testFile) === true) {
                $cacheFile = $testFile;
                break;
            }
        }//end foreach

        if ($cacheFile === null) {
            // Unlikely, but just in case $paths is empty for some reason.
            $cacheFile = $tmpDir.DIRECTORY_SEPARATOR."phpcs.$cacheHash.cache";
        }

        self::$path = $cacheFile;
        if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo "\t=> Using cache file: ".self::$path.' **'.PHP_EOL;
        }

        if (file_exists(self::$path) === true) {
            self::$cache = json_decode(file_get_contents(self::$path), true);
        } else if (PHP_CODESNIFFER_VERBOSITY > 1) {
            echo "\t* cache file does not exist *".PHP_EOL;
        }

        self::$cache['config'] = $configData;

    }//end load()


    public static function save()
    {
        file_put_contents(self::$path, json_encode(self::$cache));

    }//end save()


    public static function get($key)
    {
        if (isset(self::$cache[$key]) === true) {
            return self::$cache[$key];
        }

        return false;

    }//end get()


    public static function set($key, $value)
    {
        self::$cache[$key] = $value;

    }//end set()


    public static function getSize()
    {
        return (count(self::$cache) - 1);

    }//end getSize()


}//end class
