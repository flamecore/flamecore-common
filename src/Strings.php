<?php
/*
 * FlameCore Common Library
 * Copyright (C) 2022 FlameCore Team
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 */

namespace FlameCore\Common;

use FlameCore\Common\Exception\InvalidArgumentException;
use FlameCore\Common\Exception\InvalidStateException;
use FlameCore\Common\Exception\NotSupportedException;
use FlameCore\Common\Exception\RegexpException;

/**
 * Functions to check and manipulate (Unicode) strings.
 *
 * @author Nette Framework team and contributors
 * @author Christian Neff <christian.neff@gmail.com>
 */
class Strings
{
    use StaticClass;

    public const TRIM_CHARACTERS = " \t\n\r\0\x0B\u{A0}";

    /**
     * Returns the number of characters (not bytes) in a UTF-8 string.
     * That is the number of Unicode code points which may differ from the number of graphemes.
     *
     * @param string $string The input string
     */
    public static function length(string $string): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($string, 'UTF-8')
            : strlen(self::toIso88591($string));
    }

    /**
     * Gets the position in characters (not bytes) of the `$nth` occurrence of the given substring in a string.
     *
     * @param string $haystack The string to search in
     * @param string $needle   The substring to search for in the `$haystack`
     * @param int    $nth      Search from the `$nth` occurrence of the substring. A negative value of `$nth` means searching from the end.
     *
     * @return int|null Returns the position of the substring or `NULL` if the `$needle` was not found.
     */
    public static function indexOf(string $haystack, string $needle, int $nth = 1): ?int
    {
        $pos = self::pos($haystack, $needle, $nth);

        return $pos === null ? null : self::length(substr($haystack, 0, $pos));
    }

    /**
     * Compares two UTF-8 strings or their parts, without taking character case into account.
     *
     * @param string $left   The first string to compare
     * @param string $right  The second string to compare
     * @param ?int   $length If length is non-negative, the appropriate number of characters from the beginning of the strings is compared.
     *                       If length is negative, the corresponding number of characters from the end of the strings is compared.
     *                       If length is `NULL`, the whole strings are compared.
     */
    public static function compare(string $left, string $right, ?int $length = null): bool
    {
        if (class_exists('Normalizer', false)) {
            $left = \Normalizer::normalize($left, \Normalizer::FORM_D); // form NFD is faster
            $right = \Normalizer::normalize($right, \Normalizer::FORM_D); // form NFD is faster
        }

        if ($length < 0) {
            $left = self::substring($left, $length, -$length);
            $right = self::substring($right, $length, -$length);
        } elseif ($length !== null) {
            $left = self::substring($left, 0, $length);
            $right = self::substring($right, 0, $length);
        }

        return self::lower($left) === self::lower($right);
    }

    /**
     * Returns a specific character in UTF-8 from code point (number in range 0x0000..D7FF or 0xE000..10FFFF).
     *
     * @throws InvalidArgumentException if the code point is not in valid range
     *
     * @param int $code The character code
     */
    public static function chr(int $code): string
    {
        if (!extension_loaded('iconv')) {
            throw new NotSupportedException(sprintf('%s() requires "iconv" extension that is not loaded.', __METHOD__));
        }

        if ($code < 0 || ($code >= 0xD800 && $code <= 0xDFFF) || $code > 0x10FFFF) {
            throw new InvalidArgumentException('Code point must be in range 0x0 to 0xD7FF or 0xE000 to 0x10FFFF.');
        }

        return iconv('UTF-32BE', 'UTF-8//IGNORE', pack('N', $code));
    }

    /**
     * Returns a part of a UTF-8 string specified by starting position and length.
     *
     * @param string $string The string to extract the substring from
     * @param int    $offset If offset is non-negative, the returned string will start at the `$offset`th position in `$string`, counting from zero.
     *                       If offset is negative, the returned string will start at the `$offset`th character from the end of the `$string`.
     * @param ?int   $length Maximum number of characters to use from string. If omitted or `NULL` is passed, extract all characters to the end of the string.
     */
    public static function substring(string $string, int $offset, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($string, $offset, $length, 'UTF-8'); // MB is much faster
        } elseif (!extension_loaded('iconv')) {
            throw new NotSupportedException(sprintf('%s() requires extension "iconv" or "mbstring", neither is loaded.', __METHOD__));
        }

        if ($length === null) {
            $length = self::length($string);
        } elseif ($offset < 0 && $length < 0) {
            $offset += self::length($string); // unifies iconv_substr behavior with mb_substr
        }

        return iconv_substr($string, $offset, $length, 'UTF-8');
    }

    /**
     * Reverses a string. This function is binary-safe.
     *
     * @param string $string The input array
     */
    public static function reverse(string $string): string
    {
        if (!extension_loaded('iconv')) {
            throw new NotSupportedException(sprintf('%s() requires the "iconv" extension, which is not loaded.', __METHOD__));
        }

        return iconv('UTF-32LE', 'UTF-8', strrev(iconv('UTF-8', 'UTF-32BE', $string)));
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a UTF-8 encoded string.
     *
     * @param string $string     The string to be trimmed
     * @param string $characters The list of stripped characters
     */
    public static function trim(string $string, string $characters = self::TRIM_CHARACTERS): string
    {
        $charsQuoted = preg_quote($characters, '#');

        try {
            return self::replace($string, ['#^[' . $charsQuoted . ']+|[' . $charsQuoted . ']+$#Du' => '']);
        } catch (RegexpException $e) {
            throw new InvalidArgumentException(sprintf('Invalid trim characters: %s', $e->getMessage()));
        }
    }

    /**
     * Truncates a UTF-8 string to a given maximum length, while trying not to split whole words. Only if the string is truncated,
     * an ellipsis (or something else) is appended to the string.
     *
     * @param string $string    The string to search in
     * @param int    $maxLength The maximum length
     * @param string $append    The character(s) to append to truncated strings
     */
    public static function truncate(string $string, int $maxLength, string $append = "\u{2026}"): string
    {
        if (self::length($string) > $maxLength) {
            $maxLength -= self::length($append);
            if ($maxLength < 1) {
                return $append;
            } elseif ($matches = self::match($string, '#^.{1,' . $maxLength . '}(?=[\s\x00-/:-@\[-`{-~])#us')) {
                return $matches[0] . $append;
            } else {
                return self::substring($string, 0, $maxLength) . $append;
            }
        }

        return $string;
    }

    /**
     * Splits a string into an array by the given regular expression.
     *
     * @param string   $subject       The input string
     * @param string   $pattern       The pattern to search for
     * @param int      $limit         If specified, then only substrings up to limit are returned with the rest of the string being placed in the last substring. A limit of -1 or 0 means "no limit".
     * @param bool|int $captureOffset If this option is set, for every occurring match the appendant string offset will also be returned. Note that this changes the return value in an array where
     *                                every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
     * @param bool     $skipEmpty     If this option is set, only non-empty pieces will be returned.
     * @param bool     $utf8          If this option is set, the subject is treated as UTF-8 string.
     *
     * @return string[]
     *
     * @throws RegexpException if an error occurred.
     */
    public static function split(
        string $subject,
        string $pattern,
        int $limit = -1,
        bool|int $captureOffset = false,
        bool $skipEmpty = false,
        bool $utf8 = false
    ): array {
        $flags = ($captureOffset ? PREG_SPLIT_OFFSET_CAPTURE : 0) | ($skipEmpty ? PREG_SPLIT_NO_EMPTY : 0);
        $pattern .= $utf8 ? 'u' : '';

        $result = self::invokePcre('split', [$pattern, $subject, $limit, $flags | PREG_SPLIT_DELIM_CAPTURE]);
        if ($utf8 && ($flags & PREG_SPLIT_OFFSET_CAPTURE)) {
            return self::convertBytesToChars($subject, [$result])[0];
        }

        return $result;
    }

    /**
     * Checks if the given string matches a regular expression pattern.
     *
     * @param string   $subject         The string to search in
     * @param string   $pattern         The pattern to search for
     * @param int      $offset          Normally, the search starts from the beginning of the subject string. This optional parameter can be used to specify the alternate place from which to
     *                                  start the search (in bytes).
     * @param bool|int $captureOffset   If this option is set, for every occurring match the appendant string offset will also be returned. Note that this changes the return value in an array where
     *                                  every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
     * @param bool     $unmatchedAsNull If this option is set, unmatched subpatterns are reported as null; otherwise they are reported as an empty string.
     * @param bool     $utf8            If this option is set, the subject is treated as UTF-8 string.
     *
     * @return array|null Returns an array with the first found match and each subpattern. Returns `NULL` if the subject does not match the pattern.
     *
     * @throws RegexpException if an error occurred.
     */
    public static function match(
        string $subject,
        string $pattern,
        int $offset = 0,
        bool|int $captureOffset = false,
        bool $unmatchedAsNull = false,
        bool $utf8 = false
    ): ?array {
        $flags = ($captureOffset ? PREG_OFFSET_CAPTURE : 0) | ($unmatchedAsNull ? PREG_UNMATCHED_AS_NULL : 0);
        if ($utf8) {
            $offset = strlen(self::substring($subject, 0, $offset));
            $pattern .= 'u';
        }

        if ($offset > strlen($subject)) {
            return null;
        }

        if (!self::invokePcre('match', [$pattern, $subject, &$matches, $flags, $offset])) {
            return null;
        }

        if ($utf8 && ($flags & PREG_OFFSET_CAPTURE)) {
            return self::convertBytesToChars($subject, [$matches])[0];
        }

        return $matches;
    }

    /**
     * Searches the given string for all occurrences of text matching a regular expression pattern.
     *
     * @param string   $subject         The string to search in
     * @param string   $pattern         The pattern to search for
     * @param int      $offset          Normally, the search starts from the beginning of the subject string. This optional parameter can be used to specify the alternate place from which to
     *                                  start the search (in bytes).
     * @param bool|int $captureOffset   If this option is set, for every occurring match the appendant string offset will also be returned. Note that this changes the return value in an array where
     *                                  every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
     * @param bool     $unmatchedAsNull If this option is set, unmatched subpatterns are reported as null; otherwise they are reported as an empty string.
     * @param bool     $patternOrder    Orders results so that $matches[0] is an array of full pattern matches, $matches[1] is an array of strings matched by the first parenthesized subpattern, and so on.
     * @param bool     $utf8            If this option is set, the subject is treated as UTF-8 string.
     *
     * @return array Returns a two-dimensional array of all matches ordered according to the given options.
     *
     * @throws RegexpException if an error occurred.
     */
    public static function matchAll(
        string $subject,
        string $pattern,
        int $offset = 0,
        bool|int $captureOffset = false,
        bool $unmatchedAsNull = false,
        bool $patternOrder = false,
        bool $utf8 = false
    ): array {
        $flags = ($captureOffset ? PREG_OFFSET_CAPTURE : 0) | ($unmatchedAsNull ? PREG_UNMATCHED_AS_NULL : 0) | ($patternOrder ? PREG_PATTERN_ORDER : 0);
        if ($utf8) {
            $offset = strlen(self::substring($subject, 0, $offset));
            $pattern .= 'u';
        }

        if ($offset > strlen($subject)) {
            return [];
        }

        self::invokePcre('match_all', [$pattern, $subject, &$matches, ($flags & PREG_PATTERN_ORDER) ? $flags : ($flags | PREG_SET_ORDER), $offset]);

        if ($utf8 && ($flags & PREG_OFFSET_CAPTURE)) {
            return self::convertBytesToChars($subject, $matches);
        }

        return $matches;
    }

    /**
     * Replaces all occurrences of text matching a regular expression pattern with the corresponding replacement string.
     *
     * @param string $subject The input string
     * @param array  $map     One or multiple patterns to search for The string or an array of strings to replace. Defaults to an empty string.
     * @param int    $limit   The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
     * @param bool   $utf8    If this option is set, the subject is treated as UTF-8 string.
     *
     * @throws RegexpException if an error occurred.
     */
    public static function replace(
        string $subject,
        array $map,
        int $limit = -1,
        bool $utf8 = false
    ): string {
        $patterns = array_keys($map);
        $replacements = array_values($map);

        if ($utf8) {
            $patterns = array_map(fn ($item) => $item . 'u', $patterns);
        }

        return self::invokePcre('replace', [$patterns, $replacements, $subject, $limit]);
    }

    /**
     * Performs a regular expression search and replace using a callback.
     *
     * @param string          $subject         The input string
     * @param string|string[] $pattern         One or multiple patterns to search for
     * @param callable        $replacement     A callback that will be called and passed an array of matched elements in the subject string. The callback should return the replacement string.
     *                                         This is the callback signature: `function (array $matches): string`
     * @param int             $limit           The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
     * @param bool            $captureOffset   If this option is set, for every occurring match the appendant string offset will also be returned. Note that this changes the return value in an array where
     *                                         every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
     * @param bool            $unmatchedAsNull If this option is set, unmatched subpatterns are reported as null; otherwise they are reported as an empty string.
     * @param bool            $utf8            If this option is set, the subject is treated as UTF-8 string.
     *
     * @throws RegexpException if an error occurred.
     */
    public static function replaceDynamically(
        string $subject,
        string|array $pattern,
        callable $replacement,
        int $limit = -1,
        bool $captureOffset = false,
        bool $unmatchedAsNull = false,
        bool $utf8 = false
    ): string {
        if (!is_callable($replacement, false, $textual)) {
            throw new InvalidStateException(sprintf('Callback "%s" is not callable.', $textual));
        }

        $flags = ($captureOffset ? PREG_OFFSET_CAPTURE : 0) | ($unmatchedAsNull ? PREG_UNMATCHED_AS_NULL : 0);

        if ($utf8) {
            $pattern .= 'u';
            if ($captureOffset) {
                $replacement = fn ($match) => $replacement(self::convertBytesToChars($subject, [$match])[0]);
            }
        }

        return self::invokePcre('replace_callback', [$pattern, $replacement, $subject, $limit, 0, $flags]);
    }

    /**
     * Converts all characters of a UTF-8 string to lower case.
     *
     * @param string $string The input string
     */
    public static function lower(string $string): string
    {
        return mb_strtolower($string, 'UTF-8');
    }

    /**
     * Converts the first character of a UTF-8 string to lower case and leaves the other characters unchanged.
     *
     * @param string $string The input string
     */
    public static function lowerFirst(string $string): string
    {
        return self::lower(self::substring($string, 0, 1)) . self::substring($string, 1);
    }

    /**
     * Converts all characters of a UTF-8 string to upper case.
     *
     * @param string $string The input string
     */
    public static function upper(string $string): string
    {
        return mb_strtoupper($string, 'UTF-8');
    }

    /**
     * Converts the first character of a UTF-8 string to upper case and leaves the other characters unchanged.
     *
     * @param string $string The input string
     */
    public static function upperFirst(string $string): string
    {
        return self::upper(self::substring($string, 0, 1)) . self::substring($string, 1);
    }

    /**
     * Converts the first character of every word of a UTF-8 string to upper case and the others to lower case.
     *
     * @param string $string The input string
     */
    public static function capitalize(string $string): string
    {
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Converts the given UTF-8 encoded HTML code to plain text.
     *
     * @param string $string The input string
     */
    public static function stripHtml(string $string): string
    {
        return html_entity_decode(strip_tags($string), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Removes control characters, normalizes line breaks to `\n`, removes leading and trailing blank lines,
     * trims end spaces on lines, normalizes UTF-8 to the normal form of NFC.
     *
     * @param string $string The input string
     */
    public static function normalize(string $string): string
    {
        // convert to compressed normal form (NFC)
        if (class_exists('Normalizer', false) && ($n = \Normalizer::normalize($string, \Normalizer::FORM_C)) !== false) {
            $string = $n;
        }

        $string = self::normalizeNewLines($string);

        // remove control characters; leave \t + \n
        $string = self::invokePcre('replace', ['#[\x00-\x08\x0B-\x1F\x7F-\x9F]+#u', '', $string]);

        // right trim
        $string = self::invokePcre('replace', ['#[\t ]+$#m', '', $string]);

        // leading and trailing blank lines
        $string = trim($string, "\n");

        return $string;
    }

    /**
     * Standardize line endings to unix-like.
     *
     * @param string $string The input string
     */
    public static function normalizeNewLines(string $string): string
    {
        return str_replace(["\r\n", "\r"], "\n", $string);
    }

    /**
     * Checks if the string is valid in UTF-8 encoding.
     *
     * @param string $string The string to check
     */
    public static function isValidUtf8(string $string): bool
    {
        return $string === self::fixUtf8($string);
    }

    /**
     * Removes all invalid UTF-8 characters from a string.
     *
     * @param string $string The input string
     */
    public static function fixUtf8(string $string): string
    {
        // removes xD800-xDFFF, x110000 and higher
        return htmlspecialchars_decode(htmlspecialchars($string, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
    }

    /**
     * Converts an ISO-8859-1 string to UTF-8.
     *
     * @param string $string The input string
     */
    public static function fromIso88591(string $string): string
    {
        $string .= $string;
        $len = \strlen($string);

        for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
            switch (true) {
                case $string[$i] < "\x80": $string[$j] = $string[$i]; break;
                case $string[$i] < "\xC0": $string[$j] = "\xC2"; $string[++$j] = $string[$i]; break;
                default: $string[$j] = "\xC3"; $string[++$j] = \chr(\ord($string[$i]) - 64); break;
            }
        }

        return substr($string, 0, $j);
    }

    /**
     * Converts a UTF-8 string to ISO-8859-1.
     *
     * @param string $string The input string
     */
    public static function toIso88591(string $string): string
    {
        $len = \strlen($string);

        for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
            switch ($string[$i] & "\xF0") {
                case "\xC0":
                case "\xD0":
                    $c = (\ord($string[$i] & "\x1F") << 6) | \ord($string[++$i] & "\x3F");
                    $string[$j] = $c < 256 ? \chr($c) : '?';
                    break;

                case "\xF0":
                    ++$i;
                // no break

                case "\xE0":
                    $string[$j] = '?';
                    $i += 2;
                    break;

                default:
                    $string[$j] = $string[$i];
            }
        }

        return substr($string, 0, $j);
    }

    /**
     * @param string $function
     * @param array  $args
     *
     * @throws RegexpException
     *
     * @internal
     */
    public static function invokePcre(string $function, array $args)
    {
        $result = Callback::invokeSafe('preg_' . $function, $args, function (string $message) use ($args): void {
            // compile-time error, not detectable by preg_last_error
            throw new RegexpException($message);
        });

        if (($code = preg_last_error()) && ($result === null || !in_array($function, ['filter', 'replace_callback', 'replace'], true))) {
            throw new RegexpException(RegexpException::MESSAGES[$code] ?? 'Unknown error', $code);
        }

        return $result;
    }

    /**
     * Gets the position in bytes of the `$nth` occurrence of the given substring in a string.
     *
     * @param string $haystack The string to search in
     * @param string $needle   The substring to search for in the `$haystack`
     * @param int    $nth      Search from the `$nth` occurrence of the substring. A negative value of `$nth` means searching from the end.
     *
     * @return int|null Returns the position of the substring or `NULL` if the `$needle` was not found.
     */
    private static function pos(string $haystack, string $needle, int $nth = 1): ?int
    {
        if ($nth === 0) {
            return null;
        }

        if ($nth > 0) {
            if ($needle === '') {
                return 0;
            }

            $pos = 0;
            while (($pos = strpos($haystack, $needle, $pos)) !== false && --$nth) {
                $pos++;
            }
        } else {
            $len = strlen($haystack);
            if ($needle === '') {
                return $len;
            }

            $pos = $len - 1;
            while (($pos = strrpos($haystack, $needle, $pos - $len)) !== false && ++$nth) {
                $pos--;
            }
        }

        return $pos !== false ? $pos : null;
    }

    private static function convertBytesToChars(string $string, array $groups): array
    {
        $lastBytes = $lastChars = 0;
        foreach ($groups as &$matches) {
            foreach ($matches as &$match) {
                if ($match[1] > $lastBytes) {
                    $lastChars += self::length(substr($string, $lastBytes, $match[1] - $lastBytes));
                } elseif ($match[1] < $lastBytes) {
                    $lastChars -= self::length(substr($string, $match[1], $lastBytes - $match[1]));
                }

                $lastBytes = $match[1];
                $match[1] = $lastChars;
            }
        }

        return $groups;
    }
}
