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

/**
 * Functions to check and manipulate arrays.
 *
 * @author Nette Framework team and contributors
 * @author Christian Neff <christian.neff@gmail.com>
 */
class Arrays
{
    use StaticClass;

    /**
     * Tests an array for the presence of the given value.
     *
     * @param array $array The array to test
     * @param mixed $value The searched value
     */
    public static function contains(array $array, mixed $value): bool
    {
        return in_array($value, $array, true);
    }

    /**
     * Gets the first item of the given array.
     *
     * @template T
     *
     * @param array<T> $array The input array
     *
     * @return ?T Returns the first item of the array or NULL if the array is empty.
     */
    public static function first(array $array): mixed
    {
        return count($array) ? reset($array) : null;
    }

    /**
     * Gets the last item of the given array.
     *
     * @template T
     *
     * @param array<T> $array The input array
     *
     * @return ?T Returns the last item of the array or NULL if the array is empty.
     */
    public static function last(array $array): mixed
    {
        return count($array) ? end($array) : null;
    }

    /**
     * Determines whether the given value is accessible as array.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function isAccessible($value)
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    /**
     * Determines if the given key exists in the provided array or object of `ArrayAccess`.
     *
     * @param array|\ArrayAccess $array The array to test
     * @param string|int         $key
     *
     * @return bool
     */
    public static function keyExists(array|\ArrayAccess $array, string|int $key)
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Determines whether the given array is a list.
     *
     * @param iterable $array The array to test
     *
     * @return bool
     */
    public static function isList(iterable $array): bool
    {
        $i = -1;
        foreach ($array as $key => $val) {
            if ($key !== ++$i) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether the given array is purely associative.
     *
     * @param iterable $array The array to test
     *
     * @return bool
     */
    public static function isAssociative(iterable $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach ($array as $key => $val) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tests whether at least one element in the array passes the test implemented by the provided callback function.
     *
     * @param iterable $array    The array to test
     * @param callable $callback The test callback with signature `function ($value, $key, array $array): bool`
     */
    public static function some(iterable $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key, $array)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tests whether all elements in the array pass the test implemented by the provided callback function.
     *
     * @param iterable $array    The array to test
     * @param callable $callback The test callback with signature `function ($value, $key, array $array): bool`
     */
    public static function all(iterable $array, callable $callback): bool
    {
        foreach ($array as $k => $v) {
            if (!$callback($v, $k, $array)) {
                return false;
            }
        }

        return true;
    }
}
