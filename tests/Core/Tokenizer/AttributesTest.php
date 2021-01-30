<?php
/**
 * Tests the support of PHP 8 attributes
 *
 * @author    Alessandro Chitolina <alekitto@gmail.com>
 * @copyright 2019 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Tests\Core\Tokenizer;

use PHP_CodeSniffer\Tests\Core\AbstractMethodUnitTest;
use PHP_CodeSniffer\Util\Tokens;

class AttributesTest extends AbstractMethodUnitTest
{


    /**
     * Test that attributes are parsed correctly.
     *
     * @param string $testMarker The comment which prefaces the target token in the test file.
     * @param int    $length     The number of tokens between opener and closer.
     * @param array  $tokenCodes The codes of tokens inside the attributes.
     *
     * @dataProvider dataAttribute
     * @covers       PHP_CodeSniffer\Tokenizers\PHP::tokenize
     * @covers       PHP_CodeSniffer\Tokenizers\PHP::findCloser
     * @covers       PHP_CodeSniffer\Tokenizers\PHP::parsePhpAttribute
     *
     * @return void
     */
    public function testAttribute($testMarker, $length, $tokenCodes)
    {
        $tokens = self::$phpcsFile->getTokens();

        $attribute = $this->getTargetToken($testMarker, T_ATTRIBUTE);
        $this->assertArrayHasKey('attribute_closer', $tokens[$attribute]);

        $closer = $tokens[$attribute]['attribute_closer'];
        $this->assertSame(($attribute + $length), $closer);

        $this->assertSame(T_ATTRIBUTE_END, $tokens[$closer]['code']);

        $this->assertSame($tokens[$attribute]['attribute_opener'], $tokens[$closer]['attribute_opener']);
        $this->assertSame($tokens[$attribute]['attribute_closer'], $tokens[$closer]['attribute_closer']);

        $map = array_map(
            static function ($token) {
                return $token['code'];
            },
            array_slice($tokens, ($attribute + 1), ($length - 1))
        );

        $this->assertSame($tokenCodes, $map);

    }//end testAttribute()


    /**
     * Data provider.
     *
     * @see testAttribute()
     *
     * @return array
     */
    public function dataAttribute()
    {
        return [
            [
                '/* testAttribute */',
                2,
                [ T_STRING ],
            ],
            [
                '/* testAttributeWithParams */',
                7,
                [
                    T_STRING,
                    T_OPEN_PARENTHESIS,
                    T_STRING,
                    T_DOUBLE_COLON,
                    T_STRING,
                    T_CLOSE_PARENTHESIS,
                ],
            ],
            [
                '/* testAttributeWithNamedParam */',
                10,
                [
                    T_STRING,
                    T_OPEN_PARENTHESIS,
                    T_PARAM_NAME,
                    T_COLON,
                    T_WHITESPACE,
                    T_STRING,
                    T_DOUBLE_COLON,
                    T_STRING,
                    T_CLOSE_PARENTHESIS,
                ],
            ],
            [
                '/* testAttributeOnFunction */',
                2,
                [ T_STRING ],
            ],
            [
                '/* testAttributeOnFunctionWithParams */',
                17,
                [
                    T_STRING,
                    T_OPEN_PARENTHESIS,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_COMMA,
                    T_WHITESPACE,
                    T_PARAM_NAME,
                    T_COLON,
                    T_WHITESPACE,
                    T_OPEN_SHORT_ARRAY,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_WHITESPACE,
                    T_DOUBLE_ARROW,
                    T_WHITESPACE,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_CLOSE_SHORT_ARRAY,
                    T_CLOSE_PARENTHESIS,
                ],
            ],
            [
                '/* testAttributeGrouping */',
                26,
                [
                    T_STRING,
                    T_COMMA,
                    T_WHITESPACE,
                    T_STRING,
                    T_OPEN_PARENTHESIS,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_CLOSE_PARENTHESIS,
                    T_COMMA,
                    T_WHITESPACE,
                    T_STRING,
                    T_OPEN_PARENTHESIS,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_COMMA,
                    T_WHITESPACE,
                    T_PARAM_NAME,
                    T_COLON,
                    T_WHITESPACE,
                    T_OPEN_SHORT_ARRAY,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_WHITESPACE,
                    T_DOUBLE_ARROW,
                    T_WHITESPACE,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_CLOSE_SHORT_ARRAY,
                    T_CLOSE_PARENTHESIS,
                ],
            ],
            [
                '/* testAttributeMultiline */',
                31,
                [
                    T_WHITESPACE,
                    T_WHITESPACE,
                    T_STRING,
                    T_COMMA,
                    T_WHITESPACE,
                    T_WHITESPACE,
                    T_STRING,
                    T_OPEN_PARENTHESIS,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_CLOSE_PARENTHESIS,
                    T_COMMA,
                    T_WHITESPACE,
                    T_WHITESPACE,
                    T_STRING,
                    T_OPEN_PARENTHESIS,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_COMMA,
                    T_WHITESPACE,
                    T_PARAM_NAME,
                    T_COLON,
                    T_WHITESPACE,
                    T_OPEN_SHORT_ARRAY,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_WHITESPACE,
                    T_DOUBLE_ARROW,
                    T_WHITESPACE,
                    T_CONSTANT_ENCAPSED_STRING,
                    T_CLOSE_SHORT_ARRAY,
                    T_CLOSE_PARENTHESIS,
                    T_WHITESPACE,
                ],
            ],
        ];

    }//end dataAttribute()


    /**
     * Test that multiple attributes on the same line are parsed correctly.
     *
     * @covers PHP_CodeSniffer\Tokenizers\PHP::tokenize
     * @covers PHP_CodeSniffer\Tokenizers\PHP::findCloser
     * @covers PHP_CodeSniffer\Tokenizers\PHP::parsePhpAttribute
     *
     * @return void
     */
    public function testTwoAttributesOnTheSameLine()
    {
        $tokens = self::$phpcsFile->getTokens();

        $attribute = $this->getTargetToken('/* testTwoAttributeOnTheSameLine */', T_ATTRIBUTE);
        $this->assertArrayHasKey('attribute_closer', $tokens[$attribute]);

        $closer = $tokens[$attribute]['attribute_closer'];
        $this->assertSame(T_WHITESPACE, $tokens[($closer + 1)]['code']);
        $this->assertSame(T_ATTRIBUTE, $tokens[($closer + 2)]['code']);
        $this->assertArrayHasKey('attribute_closer', $tokens[($closer + 2)]);

    }//end testTwoAttributesOnTheSameLine()


    /**
     * Test that attribute followed by a line comment is parsed correctly.
     *
     * @covers PHP_CodeSniffer\Tokenizers\PHP::tokenize
     * @covers PHP_CodeSniffer\Tokenizers\PHP::findCloser
     * @covers PHP_CodeSniffer\Tokenizers\PHP::parsePhpAttribute
     *
     * @return void
     */
    public function testAttributeAndLineComment()
    {
        $tokens = self::$phpcsFile->getTokens();

        $attribute = $this->getTargetToken('/* testAttributeAndCommentOnTheSameLine */', T_ATTRIBUTE);
        $this->assertArrayHasKey('attribute_closer', $tokens[$attribute]);

        $closer = $tokens[$attribute]['attribute_closer'];
        $this->assertSame(T_WHITESPACE, $tokens[($closer + 1)]['code']);
        $this->assertSame(T_COMMENT, $tokens[($closer + 2)]['code']);

    }//end testAttributeAndLineComment()


    /**
     * Test that attribute followed by a line comment is parsed correctly.
     *
     * @param string $testMarker The comment which prefaces the target token in the test file.
     * @param int    $position   The token position (starting from T_FUNCTION) of T_ATTRIBUTE token.
     * @param int    $length     The number of tokens between opener and closer.
     *
     * @dataProvider dataAttributeOnParameters
     *
     * @covers PHP_CodeSniffer\Tokenizers\PHP::tokenize
     * @covers PHP_CodeSniffer\Tokenizers\PHP::findCloser
     * @covers PHP_CodeSniffer\Tokenizers\PHP::parsePhpAttribute
     *
     * @return void
     */
    public function testAttributeOnParameters($testMarker, $position, $length)
    {
        $tokens = self::$phpcsFile->getTokens();

        $function  = $this->getTargetToken($testMarker, T_FUNCTION);
        $attribute = ($function + $position);

        $this->assertSame(T_ATTRIBUTE, $tokens[$attribute]['code']);
        $this->assertArrayHasKey('attribute_closer', $tokens[$attribute]);
        $this->assertSame(($attribute + $length), $tokens[$attribute]['attribute_closer']);

        $closer = $tokens[$attribute]['attribute_closer'];
        $this->assertSame(T_WHITESPACE, $tokens[($closer + 1)]['code']);
        $this->assertSame(T_STRING, $tokens[($closer + 2)]['code']);
        $this->assertSame('int', $tokens[($closer + 2)]['content']);

        $this->assertSame(T_VARIABLE, $tokens[($closer + 4)]['code']);
        $this->assertSame('$param', $tokens[($closer + 4)]['content']);

    }//end testAttributeOnParameters()


    /**
     * Data provider.
     *
     * @see testAttributeOnParameters()
     *
     * @return array
     */
    public function dataAttributeOnParameters()
    {
        return [
            [
                '/* testSingleAttributeOnParameter */',
                4,
                2,
            ],
            [
                '/* testMultipleAttributesOnParameter */',
                4,
                10,
            ],
            [
                '/* testMultilineAttributesOnParameter */',
                4,
                13,
            ],
        ];

    }//end dataAttributeOnParameters()


    /**
     * Test that invalid attribute (or comment starting with #[ and without ]) are parsed correctly.
     *
     * @covers PHP_CodeSniffer\Tokenizers\PHP::tokenize
     * @covers PHP_CodeSniffer\Tokenizers\PHP::findCloser
     * @covers PHP_CodeSniffer\Tokenizers\PHP::parsePhpAttribute
     *
     * @return void
     */
    public function testInvalidAttribute()
    {
        $tokens = self::$phpcsFile->getTokens();

        $attribute = $this->getTargetToken('/* testInvalidAttribute */', T_ATTRIBUTE);

        $this->assertArrayHasKey('attribute_closer', $tokens[$attribute]);
        $this->assertNull($tokens[$attribute]['attribute_closer']);

    }//end testInvalidAttribute()


}//end class
