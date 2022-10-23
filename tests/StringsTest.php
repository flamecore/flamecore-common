<?php
/*
 * FlameCore Common Library
 * Copyright (C) 2022 FlameCore Team
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 */

namespace FlameCore\Common\Tests;

use FlameCore\Common\Exception\InvalidArgumentException;
use FlameCore\Common\Strings;
use PHPUnit\Framework\TestCase;

class StringsTest extends TestCase
{
    public function testLength()
    {
        $this->assertSame(0, Strings::length(''));
        $this->assertSame(20, Strings::length("I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n")); // Iñtërnâtiônàlizætiøn
        $this->assertSame(1, Strings::length("\u{10000}")); // U+010000
        $this->assertSame(6, Strings::length("ma\u{F1}ana")); // mañana, U+00F1
        $this->assertSame(7, Strings::length("man\u{303}ana")); // mañana, U+006E + U+0303 (combining character)
    }

    public function testIndexOf()
    {
        $string = '0123456789a123456789b123456789c';

        $this->assertSame(0, Strings::indexOf($string, '0'));
        $this->assertSame(9, Strings::indexOf($string, '9'));
        $this->assertSame(7, Strings::indexOf($string, '789'));
        $this->assertSame(0, Strings::indexOf($string, ''));
        $this->assertSame(31, Strings::indexOf($string, '', -1));
        $this->assertSame(30, Strings::indexOf($string, 'c', -1));
        $this->assertSame(29, Strings::indexOf($string, '9', -1));
        $this->assertSame(27, Strings::indexOf($string, '789', -1));
        $this->assertSame(29, Strings::indexOf($string, '9', 3));
        $this->assertSame(27, Strings::indexOf($string, '789', 3));
        $this->assertSame(9, Strings::indexOf($string, '9', -3));
        $this->assertSame(7, Strings::indexOf($string, '789', -3));

        $this->assertSame(3, Strings::indexOf("ma\u{F1}ana", 'ana')); // mañana, U+00F1
        $this->assertSame(4, Strings::indexOf("man\u{303}ana", 'ana')); // mañana, U+006E + U+0303 (combining character)

        $this->assertNull(Strings::indexOf($string, '9', 0));
        $this->assertNull(Strings::indexOf($string, 'not-in-string'));
        $this->assertNull(Strings::indexOf($string, 'b', -2));
        $this->assertNull(Strings::indexOf($string, 'b', 2));
    }

    public function testIndexOfWithUtf8()
    {
        $string = "I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n"; // Iñtërnâtiônàlizætiøn
        $this->assertSame(7, Strings::indexOf($string, 'ti', 1));
        $this->assertSame(16, Strings::indexOf($string, 'ti', 2));
        $this->assertSame(3, Strings::indexOf($string, "\u{EB}"));
    }

    public function testCompare()
    {
        $this->assertTrue(Strings::compare('', ''));
        $this->assertTrue(Strings::compare('', '', 0));
        $this->assertTrue(Strings::compare('', '', 1));
        $this->assertTrue(Strings::compare('xy', 'xx', 0));
        $this->assertTrue(Strings::compare('xy', 'xx', 1));
        $this->assertTrue(Strings::compare('xy', 'yy', -1));
        $this->assertTrue(Strings::compare('xy', 'yy', -1));
        $this->assertFalse(Strings::compare('xy', 'xx'));
        $this->assertFalse(Strings::compare('xy', 'yy', 1));

        $this->assertTrue(Strings::compare("I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n", "I\u{D1}T\u{CB}RN\u{C2}TI\u{D4}N\u{C0}LIZ\u{C6}TI\u{D8}N")); // Iñtërnâtiônàlizætiøn
        $this->assertTrue(Strings::compare("I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n", "I\u{D1}T\u{CB}RN\u{C2}TI\u{D4}N\u{C0}LIZ\u{C6}TI\u{D8}N", 10));

        if (class_exists('Normalizer', false)) {
            $this->assertTrue(Strings::compare("\xC3\x85", "A\xCC\x8A"), 'comparing NFC with NFD form');
            $this->assertTrue(Strings::compare("A\xCC\x8A", "\xC3\x85"), 'comparing NFD with NFC form');
        }
    }

    public function testChr()
    {
        $this->assertSame("\x00", Strings::chr(0x000000));
        $this->assertSame("\x7F", Strings::chr(0x00007F));
        $this->assertSame("\u{80}", Strings::chr(0x000080));
        $this->assertSame("\u{7FF}", Strings::chr(0x0007FF));
        $this->assertSame("\u{800}", Strings::chr(0x000800));
        $this->assertSame("\u{D7FF}", Strings::chr(0x00D7FF));
        $this->assertSame("\u{E000}", Strings::chr(0x00E000));
        $this->assertSame("\u{FFFF}", Strings::chr(0x00FFFF));
        $this->assertSame("\u{10000}", Strings::chr(0x010000));
        $this->assertSame("\u{10FFFF}", Strings::chr(0x10FFFF));
    }

    /**
     * @dataProvider provideDataForChrException
     *
     * @param int $code
     */
    public function testChrException(int $code)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Code point must be in range 0x0 to 0xD7FF or 0xE000 to 0x10FFFF.');

        Strings::chr($code);
    }

    public function provideDataForChrException()
    {
        return [
            [-1],
            [0xD800],
            [0xDFFF],
            [0x110000]
        ];
    }

    public function testSubstring()
    {
        $string = "man\u{303}ana"; // mañana, U+006E + U+0303 (combining character)

        $this->assertSame("man\u{303}ana", Strings::substring($string, 0));
        $this->assertSame('', Strings::substring($string, 0, 0));
        $this->assertSame('man', Strings::substring($string, 0, 3));
        $this->assertSame("man\u{303}", Strings::substring($string, 0, 4));
        $this->assertSame("man\u{303}", Strings::substring($string, 0, -3));
        $this->assertSame('man', Strings::substring($string, 0, -4));
        $this->assertSame('', Strings::substring($string, 3, 0));
        $this->assertSame("\u{303}ana", Strings::substring($string, 3));
        $this->assertSame('ana', Strings::substring($string, 4));
        $this->assertSame('an', Strings::substring($string, 1, 2));
        $this->assertSame("an\u{303}", Strings::substring($string, 1, 3));
        $this->assertSame("n\u{303}", Strings::substring($string, 2, 2));
        $this->assertSame("\u{303}a", Strings::substring($string, 3, 2));
        $this->assertSame("an\u{303}", Strings::substring($string, 1, -3));
        $this->assertSame('an', Strings::substring($string, 1, -4));
        $this->assertSame('', Strings::substring($string, -3, 0));
        $this->assertSame("\u{303}ana", Strings::substring($string, -4));
        $this->assertSame("n\u{303}ana", Strings::substring($string, -5));
        $this->assertSame("\u{303}a", Strings::substring($string, -4, 2));
        $this->assertSame('n', Strings::substring($string, -5, 1));
        $this->assertSame("n\u{303}", Strings::substring($string, -5, -3));
        $this->assertSame('n', Strings::substring($string, -5, -4));
        $this->assertSame('', Strings::substring($string, -5, -5));
    }

    public function testReverse()
    {
        $s1 = "\x60,\u{236},\u{E22},\u{20062}";
        $s2 = "\u{20062},\u{E22},\u{236},\x60";
        $this->assertSame($s1, Strings::reverse($s2));
        $this->assertSame($s2, Strings::reverse($s1));

        $this->assertSame("ana\u{F1}am", Strings::reverse("ma\u{F1}ana"));   // mañana -> anañam, U+00F1
        $this->assertSame("ana\u{303}nam", Strings::reverse("man\u{303}ana")); // mañana -> anãnam, U+006E + U+0303 (combining character)
    }

    public function testTrim()
    {
        $this->assertSame('x', Strings::trim(" \t\n\r\x00\x0B\u{A0}x"));
        $this->assertSame('a b', Strings::trim(' a b '));
        $this->assertSame(' a b ', Strings::trim(' a b ', ''));
        $this->assertSame('e', Strings::trim("\u{158}e-", "\u{158}-")); // Ře-
    }

    public function testTrimException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid trim characters: Malformed UTF-8 data');

        Strings::trim("\xC2x\xA0");
    }

    public function testTruncate()
    {
        $str = "\u{158}ekn\u{11B}te, jak se (dnes) m\u{E1}te?"; // Řekněte, jak se (dnes) máte?
        
        $this->assertSame('…', Strings::truncate($str, -1)); // length=-1
        $this->assertSame('…', Strings::truncate($str, 0)); // length=0
        $this->assertSame('…', Strings::truncate($str, 1)); // length=1
        $this->assertSame('Ř…', Strings::truncate($str, 2)); // length=2
        $this->assertSame('Ře…', Strings::truncate($str, 3)); // length=3
        $this->assertSame('Řek…', Strings::truncate($str, 4)); // length=4
        $this->assertSame('Řekn…', Strings::truncate($str, 5)); // length=5
        $this->assertSame('Řekně…', Strings::truncate($str, 6)); // length=6
        $this->assertSame('Řeknět…', Strings::truncate($str, 7)); // length=7
        $this->assertSame('Řekněte…', Strings::truncate($str, 8)); // length=8
        $this->assertSame('Řekněte,…', Strings::truncate($str, 9)); // length=9
        $this->assertSame('Řekněte,…', Strings::truncate($str, 10)); // length=10
        $this->assertSame('Řekněte,…', Strings::truncate($str, 11)); // length=11
        $this->assertSame('Řekněte,…', Strings::truncate($str, 12)); // length=12
        $this->assertSame('Řekněte, jak…', Strings::truncate($str, 13)); // length=13
        $this->assertSame('Řekněte, jak…', Strings::truncate($str, 14)); // length=14
        $this->assertSame('Řekněte, jak…', Strings::truncate($str, 15)); // length=15
        $this->assertSame('Řekněte, jak se…', Strings::truncate($str, 16)); // length=16
        $this->assertSame('Řekněte, jak se …', Strings::truncate($str, 17)); // length=17
        $this->assertSame('Řekněte, jak se …', Strings::truncate($str, 18)); // length=18
        $this->assertSame('Řekněte, jak se …', Strings::truncate($str, 19)); // length=19
        $this->assertSame('Řekněte, jak se …', Strings::truncate($str, 20)); // length=20
        $this->assertSame('Řekněte, jak se …', Strings::truncate($str, 21)); // length=21
        $this->assertSame('Řekněte, jak se (dnes…', Strings::truncate($str, 22)); // length=22
        $this->assertSame('Řekněte, jak se (dnes)…', Strings::truncate($str, 23)); // length=23
        $this->assertSame('Řekněte, jak se (dnes)…', Strings::truncate($str, 24)); // length=24
        $this->assertSame('Řekněte, jak se (dnes)…', Strings::truncate($str, 25)); // length=25
        $this->assertSame('Řekněte, jak se (dnes)…', Strings::truncate($str, 26)); // length=26
        $this->assertSame('Řekněte, jak se (dnes)…', Strings::truncate($str, 27)); // length=27
        $this->assertSame('Řekněte, jak se (dnes) máte?', Strings::truncate($str, 28)); // length=28
        $this->assertSame('Řekněte, jak se (dnes) máte?', Strings::truncate($str, 29)); // length=29
        $this->assertSame('Řekněte, jak se (dnes) máte?', Strings::truncate($str, 30)); // length=30
        $this->assertSame('Řekněte, jak se (dnes) máte?', Strings::truncate($str, 31)); // length=31
        $this->assertSame('Řekněte, jak se (dnes) máte?', Strings::truncate($str, 32)); // length=32

        // mañana, U+006E + U+0303 (combining character)
        $this->assertSame("man\u{303}", Strings::truncate("man\u{303}ana", 4, ''));
        $this->assertSame('man', Strings::truncate("man\u{303}ana", 3, ''));
    }

    public function testSplit()
    {
        $this->assertSame(
            ['a', ',', 'b', ',', 'c'],
            Strings::split('a, b, c', '#(,)\s*#')
        );
        $this->assertSame(
            ['a', ',', 'b', ',', 'c'],
            Strings::split('a, b, c', '#(,)\s*#', skipEmpty: true)
        );
        $this->assertSame(
            [['a', 0], [',', 1], ['b', 3], [',', 4], ['c', 6]],
            Strings::split('a, b, c', '#(,)\s*#', -1, captureOffset: true)
        );
        $this->assertSame(
            [['ž', 0], ['lu', 2], ['ť', 4], ['ou', 6], ['č', 8], ['k', 10], ['ý ', 11], ['k', 14], ['ůň', 15]],
            Strings::split('žluťoučký kůň', '#([a-z]+)\s*#u', captureOffset: true)
        );
        $this->assertSame(
            [['ž', 0], ['lu', 1], ['ť', 3], ['ou', 4], ['č', 6], ['k', 7], ['ý ', 8], ['k', 10], ['ůň', 11]],
            Strings::split('žluťoučký kůň', '#([a-z]+)\s*#u', captureOffset: true, utf8: true)
        );
        $this->assertSame(
            ['', ' ', ''],
            Strings::split('žluťoučký kůň', '#\w+#', utf8: true) // without modifier
        );
        $this->assertSame(
            ['a', ',', 'b, c'],
            Strings::split('a, b, c', '#(,)\s*#', limit: 2)
        );
    }

    public function testMatch()
    {
        $this->assertNull(Strings::match('hello world!', '#([E-L])+#'));
        $this->assertSame(['hell', 'l'], Strings::match('hello world!', '#([e-l])+#'));
        $this->assertSame(['hell'], Strings::match('hello world!', '#[e-l]+#'));
        $this->assertSame([['l', 2]], Strings::match('žluťoučký kůň', '#[e-l]+#u', captureOffset: true));
        $this->assertSame([['l', 1]], Strings::match('žluťoučký kůň', '#[e-l]+#u', captureOffset: true, utf8: true));
        $this->assertSame(['e', null], Strings::match('hello world!', '#e(x)*#', unmatchedAsNull: true));
        $this->assertSame(['e', null], Strings::match('hello world!', '#e(x)*#', 0, 0, unmatchedAsNull: true)); // $flags = 0
        $this->assertSame(['ll'], Strings::match('hello world!', '#[e-l]+#', offset: 2));
        $this->assertSame(['l'], Strings::match('žluťoučký kůň', '#[e-l]+#u', offset: 2));
        $this->assertSame(['k'], Strings::match('žluťoučký kůň', '#[e-l]+#u', offset: 2, utf8: true));
        $this->assertSame(['žluťoučký'], Strings::match('žluťoučký kůň', '#\w+#', utf8: true)); // without modifier
        $this->assertSame([['k', 7]], Strings::match('žluťoučký kůň', '#[e-l]+#u', offset: 2, captureOffset: true, utf8: true));
        $this->assertNull(Strings::match('hello world!', '', offset: 50));
        $this->assertNull(Strings::match('', '', offset: 1));
    }

    public function testMatchAll()
    {
        $this->assertSame([], Strings::matchAll('hello world!', '#([E-L])+#'));
        $this->assertSame([
            ['hell', 'l'],
            ['l', 'l'],
        ], Strings::matchAll('hello world!', '#([e-l])+#'));
        $this->assertSame([
            ['hell'],
            ['l'],
        ], Strings::matchAll('hello world!', '#[e-l]+#'));
        $this->assertSame([
            [['lu', 2], ['l', 2], ['u', 3]],
            [['ou', 6], ['o', 6], ['u', 7]],
            [['k', 10], ['k', 10], ['', 11]],
            [['k', 14], ['k', 14], ['', 15]],
        ], Strings::matchAll('žluťoučký kůň!', '#([a-z])([a-z]*)#u', captureOffset: true));
        $this->assertSame([
            [['lu', 1], ['l', 1], ['u', 2]],
            [['ou', 4], ['o', 4], ['u', 5]],
            [['k', 7], ['k', 7], ['', 8]],
            [['k', 10], ['k', 10], ['', 11]],
        ], Strings::matchAll('žluťoučký kůň!', '#([a-z])([a-z]*)#u', captureOffset: true, utf8: true));
        $this->assertSame([
            [['lu', 2], ['ou', 6], ['k', 10], ['k', 14]],
            [['l', 2], ['o', 6], ['k', 10], ['k', 14]],
            [['u', 3], ['u', 7], ['', 11], ['', 15]],
        ], Strings::matchAll('žluťoučký kůň!', '#([a-z])([a-z]*)#u', captureOffset: true, patternOrder: true));
        $this->assertSame([
            [['lu', 1], ['ou', 4], ['k', 7], ['k', 10]],
            [['l', 1], ['o', 4], ['k', 7], ['k', 10]],
            [['u', 2], ['u', 5], ['', 8], ['', 11]],
        ], Strings::matchAll('žluťoučký kůň!', '#([a-z])([a-z]*)#u', captureOffset: true, patternOrder: true, utf8: true));
        $this->assertSame([['l'], ['k'], ['k']], Strings::matchAll('žluťoučký kůň', '#[e-l]+#u', offset: 2));
        $this->assertSame([['k'], ['k']], Strings::matchAll('žluťoučký kůň', '#[e-l]+#u', offset: 2, utf8: true));
        $this->assertSame([['žluťoučký'], ['kůň']], Strings::matchAll('žluťoučký kůň', '#\w+#', utf8: true)); // without modifier
        $this->assertSame([['ll', 'l']], Strings::matchAll('hello world!', '#[e-l]+#', offset: 2, patternOrder: true));
        $this->assertSame([['e', null]], Strings::matchAll('hello world!', '#e(x)*#', unmatchedAsNull: true));
        $this->assertSame([['e', null]], Strings::matchAll('hello world!', '#e(x)*#', 0, 0, unmatchedAsNull: true)); // $flags = 0
        $this->assertSame([], Strings::matchAll('hello world!', '', offset: 50));
    }

    public function testReplace()
    {
        $this->assertSame('hello world!', Strings::replace('hello world!', ['#([E-L])+#' => '#']));
        $this->assertSame(' !', Strings::replace('hello world!', ['#\w#' => '']));
        $this->assertSame('#@ @@@#d!', Strings::replace('hello world!', [
            '#([e-l])+#' => '#',
            '#[o-w]#' => '@',
        ]));

        // utf-8 without modifier
        $this->assertSame('* *', Strings::replace('žluťoučký kůň', ['#\w+#' => '*'], utf8: true));
    }

    public function testReplaceDynamically()
    {
        $this->assertSame('@o wor@d!', Strings::replaceDynamically('hello world!', '#[e-l]+#', fn () => '@'));
        $this->assertSame('@o wor@d!', Strings::replaceDynamically('hello world!', '#[e-l]+#', \Closure::fromCallable(StringsTest\Test::class . '::cb')));
        $this->assertSame('@o wor@d!', Strings::replaceDynamically('hello world!', ['#[e-l]+#'], \Closure::fromCallable(StringsTest\Test::class . '::cb')));
        $this->assertSame('@o wor@d!', Strings::replaceDynamically('hello world!', '#[e-l]+#', [StringsTest\Test::class, 'cb']));

        // flags
        $this->assertSame('hell0o worl9d!', Strings::replaceDynamically('hello world!', '#[e-l]+#', fn ($m) => implode('', $m[0]), captureOffset: true));
        $this->assertSame('žl1uťoučk7ý k10ůň!', Strings::replaceDynamically('žluťoučký kůň!', '#[e-l]+#u', fn ($m) => implode('', $m[0]), captureOffset: true, utf8: true));
        Strings::replaceDynamically('hello world!', '#e(x)*#', fn ($m) => $this->assertNull($m[1]), unmatchedAsNull: true);

        // utf-8 without modifier
        $this->assertSame('* *', Strings::replaceDynamically('žluťoučký kůň', '#\w+#', fn () => '*', utf8: true));
    }

    public function testReplaceDynamicallyWithErrors()
    {
        $this->expectError();
        $this->expectErrorMessage('Undefined variable $a');

        Strings::replaceDynamically('hello', '#.+#', function ($m) {
            $a++; // E_NOTICE
            return strtoupper($m[0]);
        });

        $this->assertSame('HELLO', Strings::replaceDynamically('hello', '#.+#', function ($m) {
            preg_match('#\d#u', "0123456789\xFF"); // Malformed UTF-8 data
            return strtoupper($m[0]);
        }));
    }

    public function testLower()
    {
        $this->assertSame('ďábelské', Strings::lower('ĎÁBELSKÉ'));
    }

    public function testLowerFirst()
    {
        $this->assertSame('ďÁBELSKÉ', Strings::lowerFirst('ĎÁBELSKÉ'));
    }

    public function testUpper()
    {
        $this->assertSame('ĎÁBELSKÉ', Strings::upper('ďábelské'));
    }

    public function testUpperFirst()
    {
        $this->assertSame('Ďábelské', Strings::upperFirst('ďábelské'));
    }

    public function testCapitalize()
    {
        $this->assertSame('Ďábelské Ódy', Strings::capitalize('ďábelské ódy'));
    }

    public function testNormalize()
    {
        $this->assertSame("Hello\n  World", Strings::normalize("\r\nHello  \r  World \n\n"));

        $this->assertSame('Hello  World', Strings::normalize("Hello \x00 World"));
        $this->assertSame('Hello  World', Strings::normalize("Hello \x0B World"));
        $this->assertSame('Hello  World', Strings::normalize("Hello \x1F World"));
        $this->assertSame("Hello \x7E World", Strings::normalize("Hello \x7E World"));
        $this->assertSame('Hello  World', Strings::normalize("Hello \x7F World"));
        $this->assertSame('Hello  World', Strings::normalize("Hello \u{80} World"));
        $this->assertSame('Hello  World', Strings::normalize("Hello \u{9F} World"));
        $this->assertSame("Hello \u{A0} World", Strings::normalize("Hello \u{A0} World"));

        if (class_exists('Normalizer', false)) {
            $this->assertSame("\xC3\x85", Strings::normalize("\xC3\x85")); // NFC -> NFC form
            $this->assertSame("\xC3\x85", Strings::normalize("A\xCC\x8A")); // NFD -> NFC form
        }
    }

    public function testNormalizeNewLines()
    {
        $this->assertSame("\n \n \n\n", Strings::normalizeNewLines("\r\n \r \n\n"));
        $this->assertSame("\n\n", Strings::normalizeNewLines("\n\r"));
    }

    public function testIsUtf8()
    {
        $this->assertTrue(Strings::isValidUtf8("\u{17E}lu\u{165}ou\u{10D}k\u{FD}")); // UTF-8   žluťoučký
        $this->assertTrue(Strings::isValidUtf8("\x01")); // C0
        $this->assertFalse(Strings::isValidUtf8("\xed\xa0\x80")); // surrogate pairs   xD800
        $this->assertFalse(Strings::isValidUtf8("\xf4\x90\x80\x80")); // out of range   x110000
    }

    public function testFixUtf8()
    {
        // Based on "UTF-8 decoder capability and stress test" by Markus Kuhn
        // http://www.cl.cam.ac.uk/~mgk25/ucs/examples/UTF-8-test.txt
        $tests = [
            '1  Some correct UTF-8 text' => [
                "\u{3BA}\u{1F79}\u{3C3}\u{3BC}\u{3B5}",
                "\u{3BA}\u{1F79}\u{3C3}\u{3BC}\u{3B5}",
            ],
            '2  Boundary condition test cases' => [
                '2.1  First possible sequence of a certain length' => [
                    '2.1.1  1 byte  (U-00000000)' => [
                        "\x00",
                        "\x00",
                    ],
                    '2.1.2  2 bytes (U-00000080)' => [
                        "\u{80}",
                        "\u{80}",
                    ],
                    '2.1.3  3 bytes (U-00000800)' => [
                        "\u{800}",
                        "\u{800}",
                    ],
                    '2.1.4  4 bytes (U-00010000)' => [
                        "\u{10000}",
                        "\u{10000}",
                    ],
                    '2.1.5  5 bytes (U-00200000)' => [
                        "\xF8\x88\x80\x80\x80",
                        '',
                    ],
                    '2.1.6  6 bytes (U-04000000)' => [
                        "\xFC\x84\x80\x80\x80\x80",
                        '',
                    ],
                ],
                '2.2  Last possible sequence of a certain length' => [
                    '2.2.1  1 byte  (U-0000007F)' => [
                        "\x7F",
                        "\x7F",
                    ],
                    '2.2.2  2 bytes (U-000007FF)' => [
                        "\u{7FF}",
                        "\u{7FF}",
                    ],
                    '2.2.3  3 bytes (U-0000FFFF)' => [
                        "\u{FFFF}",
                        "\u{FFFF}",
                    ],
                    '2.2.4  4 bytes (U-001FFFFF)' => [
                        "\xF7\xBF\xBF\xBF",
                        '',
                    ],
                    '2.2.5  5 bytes (U-03FFFFFF)' => [
                        "\xFB\xBF\xBF\xBF\xBF",
                        '',
                    ],
                    '2.2.6  6 bytes (U-7FFFFFFF)' => [
                        "\xFD\xBF\xBF\xBF\xBF\xBF",
                        '',
                    ],
                ],
                '2.3  Other boundary conditions' => [
                    '2.3.1  U-0000D7FF' => [
                        "\u{D7FF}",
                        "\u{D7FF}",
                    ],
                    '2.3.2  U-0000E000' => [
                        "\u{E000}",
                        "\u{E000}",
                    ],
                    '2.3.3  U-0000FFFD' => [
                        "\u{FFFD}",
                        "\u{FFFD}",
                    ],
                    '2.3.4  U-0010FFFF' => [
                        "\u{10FFFF}",
                        "\u{10FFFF}",
                    ],
                    '2.3.5  U-00110000' => [
                        "\xF4\x90\x80\x80",
                        '',
                    ],
                ],
            ],
            '3  Malformed sequences' => [
                '3.1  Unexpected continuation bytes' => [
                    '3.1.1  First continuation byte 0x80' => [
                        "\x80",
                        '',
                    ],
                    '3.1.2  Last  continuation byte 0xbf' => [
                        "\xBF",
                        '',
                    ],
                    '3.1.3  2 continuation bytes' => [
                        "\x80\xBF",
                        '',
                    ],
                    '3.1.4  3 continuation bytes' => [
                        "\x80\xBF\x80",
                        '',
                    ],
                    '3.1.5  4 continuation bytes' => [
                        "\x80\xBF\x80\xBF",
                        '',
                    ],
                    '3.1.6  5 continuation bytes' => [
                        "\x80\xBF\x80\xBF\x80",
                        '',
                    ],
                    '3.1.7  6 continuation bytes' => [
                        "\x80\xBF\x80\xBF\x80\xBF",
                        '',
                    ],
                    '3.1.8  7 continuation bytes' => [
                        "\x80\xBF\x80\xBF\x80\xBF\x80",
                        '',
                    ],
                    '3.1.9  Sequence of all 64 possible continuation bytes (0x80-0xbf)' => [
                        implode('', range("\x80", "\xBF")),
                        '',
                    ],
                ],
                '3.2  Lonely start characters' => [
                    '3.2.1  All 32 first bytes of 2-byte sequences (0xc0-0xdf), each followed by a space character' => [
                        implode(' ', range("\xC0", "\xDF")) . ' ',
                        str_repeat(' ', 32),
                    ],
                    '3.2.2  All 16 first bytes of 3-byte sequences (0xe0-0xef), each followed by a space character' => [
                        implode(' ', range("\xE0", "\xEF")) . ' ',
                        str_repeat(' ', 16),
                    ],
                    '3.2.3  All 8 first bytes of 4-byte sequences (0xf0-0xf7), each followed by a space character' => [
                        implode(' ', range("\xF0", "\xF7")) . ' ',
                        str_repeat(' ', 8),
                    ],
                    '3.2.4  All 4 first bytes of 5-byte sequences (0xf8-0xfb), each followed by a space character' => [
                        implode(' ', range("\xF8", "\xFB")) . ' ',
                        str_repeat(' ', 4),
                    ],
                    '3.2.5  All 2 first bytes of 6-byte sequences (0xfc-0xfd), each followed by a space character' => [
                        implode(' ', range("\xFC", "\xFD")) . ' ',
                        str_repeat(' ', 2),
                    ],
                ],
                '3.3  Sequences with last continuation byte missing' => [
                    '3.3.1  2-byte sequence with last byte missing (U+0000)' => [
                        "\xC0",
                        '',
                    ],
                    '3.3.2  3-byte sequence with last byte missing (U+0000)' => [
                        "\xE0\x80",
                        '',
                    ],
                    '3.3.3  4-byte sequence with last byte missing (U+0000)' => [
                        "\xF0\x80\x80",
                        '',
                    ],
                    '3.3.4  5-byte sequence with last byte missing (U+0000)' => [
                        "\xF8\x80\x80\x80",
                        '',
                    ],
                    '3.3.5  6-byte sequence with last byte missing (U+0000)' => [
                        "\xFC\x80\x80\x80\x80",
                        '',
                    ],
                    '3.3.6  2-byte sequence with last byte missing (U-000007FF)' => [
                        "\xDF",
                        '',
                    ],
                    '3.3.7  3-byte sequence with last byte missing (U-0000FFFF)' => [
                        "\xEF\xBF",
                        '',
                    ],
                    '3.3.8  4-byte sequence with last byte missing (U-001FFFFF)' => [
                        "\xF7\xBF\xBF",
                        '',
                    ],
                    '3.3.9  5-byte sequence with last byte missing (U-03FFFFFF)' => [
                        "\xFB\xBF\xBF\xBF",
                        '',
                    ],
                    '3.3.10 6-byte sequence with last byte missing (U-7FFFFFFF)' => [
                        "\xFD\xBF\xBF\xBF\xBF",
                        '',
                    ],
                ],
                '3.4  Concatenation of incomplete sequences' => [
                    "\xC0\xE0\x80\xF0\x80\x80\xF8\x80\x80\x80\xFC\x80\x80\x80\x80\xDF\xEF\xBF\xF7\xBF\xBF\xFB\xBF\xBF\xBF\xFD\xBF\xBF\xBF\xBF",
                    '',
                ],
                '3.5  Impossible bytes' => [
                    '3.5.1  fe' => [
                        "\xFE",
                        '',
                    ],
                    '3.5.2  ff' => [
                        "\xFF",
                        '',
                    ],
                    '3.5.3  fe fe ff ff' => [
                        "\xFE\xFE\xFF\xFF",
                        '',
                    ],
                ],
            ],
            '4  Overlong sequences' => [
                '4.1  Examples of an overlong ASCII character' => [
                    '4.1.1 U+002F = c0 af' => [
                        "\xC0\xAF",
                        '',
                    ],
                    '4.1.2 U+002F = e0 80 af' => [
                        "\xE0\x80\xAF",
                        '',
                    ],
                    '4.1.3 U+002F = f0 80 80 af' => [
                        "\xF0\x80\x80\xAF",
                        '',
                    ],
                    '4.1.4 U+002F = f8 80 80 80 af' => [
                        "\xF8\x80\x80\x80\xAF",
                        '',
                    ],
                    '4.1.5 U+002F = fc 80 80 80 80 af' => [
                        "\xFC\x80\x80\x80\x80\xAF",
                        '',
                    ],
                ],
                '4.2  Maximum overlong sequences' => [
                    '4.2.1  U-0000007F = c1 bf' => [
                        "\xC1\xBF",
                        '',
                    ],
                    '4.2.2  U-000007FF = e0 9f bf' => [
                        "\xE0\x9F\xBF",
                        '',
                    ],
                    '4.2.3  U-0000FFFF = f0 8f bf bf' => [
                        "\xF0\x8F\xBF\xBF",
                        '',
                    ],
                    '4.2.4  U-001FFFFF = f8 87 bf bf bf' => [
                        "\xF8\x87\xBF\xBF\xBF",
                        '',
                    ],
                    '4.2.5  U-03FFFFFF = fc 83 bf bf bf bf' => [
                        "\xFC\x83\xBF\xBF\xBF\xBF",
                        '',
                    ],
                ],
                '4.3  Overlong representation of the NUL character' => [
                    '4.3.1  U+0000 = c0 80' => [
                        "\xC0\x80",
                        '',
                    ],
                    '4.3.2  U+0000 = e0 80 80' => [
                        "\xE0\x80\x80",
                        '',
                    ],
                    '4.3.3  U+0000 = f0 80 80 80' => [
                        "\xF0\x80\x80\x80",
                        '',
                    ],
                    '4.3.4  U+0000 = f8 80 80 80 80' => [
                        "\xF8\x80\x80\x80\x80",
                        '',
                    ],
                    '4.3.5  U+0000 = fc 80 80 80 80 80' => [
                        "\xFC\x80\x80\x80\x80\x80",
                        '',
                    ],
                ],
            ],
            '5  Illegal code positions' => [
                '5.1 Single UTF-16 surrogates' => [
                    '5.1.1  U+D800 = ed a0 80' => [
                        "\xED\xA0\x80",
                        '',
                    ],
                    '5.1.2  U+DB7F = ed ad bf' => [
                        "\xED\xAD\xBF",
                        '',
                    ],
                    '5.1.3  U+DB80 = ed ae 80' => [
                        "\xED\xAE\x80",
                        '',
                    ],
                    '5.1.4  U+DBFF = ed af bf' => [
                        "\xED\xAF\xBF",
                        '',
                    ],
                    '5.1.5  U+DC00 = ed b0 80' => [
                        "\xED\xB0\x80",
                        '',
                    ],
                    '5.1.6  U+DF80 = ed be 80' => [
                        "\xED\xBE\x80",
                        '',
                    ],
                    '5.1.7  U+DFFF = ed bf bf' => [
                        "\xED\xBF\xBF",
                        '',
                    ],
                ],
                '5.2 Paired UTF-16 surrogates' => [
                    '5.2.1  U+D800 U+DC00 = ed a0 80 ed b0 80' => [
                        "\xED\xA0\x80\xED\xB0\x80",
                        '',
                    ],
                    '5.2.2  U+D800 U+DFFF = ed a0 80 ed bf bf' => [
                        "\xED\xA0\x80\xED\xBF\xBF",
                        '',
                    ],
                    '5.2.3  U+DB7F U+DC00 = ed ad bf ed b0 80' => [
                        "\xED\xAD\xBF\xED\xB0\x80",
                        '',
                    ],
                    '5.2.4  U+DB7F U+DFFF = ed ad bf ed bf bf' => [
                        "\xED\xAD\xBF\xED\xBF\xBF",
                        '',
                    ],
                    '5.2.5  U+DB80 U+DC00 = ed ae 80 ed b0 80' => [
                        "\xED\xAE\x80\xED\xB0\x80",
                        '',
                    ],
                    '5.2.6  U+DB80 U+DFFF = ed ae 80 ed bf bf' => [
                        "\xED\xAE\x80\xED\xBF\xBF",
                        '',
                    ],
                    '5.2.7  U+DBFF U+DC00 = ed af bf ed b0 80' => [
                        "\xED\xAF\xBF\xED\xB0\x80",
                        '',
                    ],
                    '5.2.8  U+DBFF U+DFFF = ed af bf ed bf bf' => [
                        "\xED\xAF\xBF\xED\xBF\xBF",
                        '',
                    ],
                ],
                // noncharacters are allowed according to http://www.unicode.org/versions/corrigendum9.html
                '5.3 Other illegal code positions' => [
                    '5.3.1  U+FFFE = ef bf be' => [
                        "\u{FFFE}",
                        "\u{FFFE}",
                    ],
                    '5.3.2  U+FFFF = ef bf bf' => [
                        "\u{FFFF}",
                        "\u{FFFF}",
                    ],
                ],
            ],
        ];

        $stack = [$tests];
        while ($item = array_pop($stack)) {
            if (isset($item[0])) {
                [$in, $out, $label] = $item;
                $this->assertSame('a' . $out . 'b', Strings::fixUtf8('a' . $in . 'b'), $label);
            } else {
                foreach (array_reverse($item) as $label => $tests) {
                    $stack[] = $tests + (isset($tests[0]) ? [2 => $label] : []);
                }
            }
        }
    }

    public function testFromIso88591()
    {
        $this->assertSame('Aa!$', Strings::fromIso88591('Aa!$')); // ASCII
        $this->assertSame("Àà", Strings::fromIso88591("\xC0\xE0")); // Letters with accents
    }

    public function testToIso88591()
    {
        $this->assertSame('Aa!$', Strings::toIso88591('Aa!$')); // ASCII
        $this->assertSame('?', Strings::toIso88591('€')); // Euro sign
        $this->assertSame('?', Strings::toIso88591("\xF0\x9F\x98\x8A")); // "Smiling face with smiling eyes" emoji
    }
}
