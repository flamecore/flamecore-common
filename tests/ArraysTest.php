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

use FlameCore\Common\Arrays;
use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase
{
    public function testContains()
    {
        $this->assertFalse(Arrays::contains([], 'a'));
        $this->assertTrue(Arrays::contains(['a'], 'a'));
        $this->assertTrue(Arrays::contains([1, 2, 'a'], 'a'));
        $this->assertFalse(Arrays::contains([1, 2, 3], 'a'));
        $this->assertFalse(Arrays::contains([1, 2, 3], '1'));
    }

    public function testFirst()
    {
        $this->assertNull(Arrays::first([]));
        $this->assertNull(Arrays::first([null]));
        $this->assertFalse(Arrays::first([false]));
        $this->assertSame(1, Arrays::first([1, 2, 3]));

        // The method shall not manipulate the pointer of the original array
        $array = [1, 2, 3];
        end($array);
        $this->assertSame(1, Arrays::first($array));
        $this->assertSame(3, current($array));
    }

    public function testLast()
    {
        $this->assertNull(Arrays::last([]));
        $this->assertNull(Arrays::last([null]));
        $this->assertFalse(Arrays::last([false]));
        $this->assertSame(3, Arrays::last([1, 2, 3]));

        // The method shall not manipulate the pointer of the original array
        $array = [1, 2, 3];
        $this->assertSame(3, Arrays::last($array));
        $this->assertSame(1, current($array));
    }

    public function testIsAccessible()
    {
        $this->assertTrue(Arrays::isAccessible([]));
        $this->assertTrue(Arrays::isAccessible(new \ArrayObject()));
        $this->assertFalse(Arrays::isAccessible('foo'));
    }

    public function testKeyExists()
    {
        $array = ['foo' => 'bar'];

        $this->assertTrue(Arrays::keyExists($array, 'foo'));
        $this->assertFalse(Arrays::keyExists($array, 'bar'));
    }

    public function testIsList()
    {
        $this->assertTrue(Arrays::isList(['x', 'y', 'z']));
        $this->assertFalse(Arrays::isList(['x', 'y', 3 => 'z']));
        $this->assertFalse(Arrays::isList(['x', 'y', 'c' => 'z']));
    }

    public function testIsAssociative()
    {
        $this->assertTrue(Arrays::isAssociative(['a' => 'x', 'b' => 'y', 'c' => 'z']));
        $this->assertFalse(Arrays::isAssociative(['x', 'y', 'z']));
    }

    public function testSome()
    {
        $array = [];
        $log = [];
        $result = Arrays::some(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return false;
            },
        );
        $this->assertFalse($result);
        $this->assertSame([], $log);

        $array = [];
        $log = [];
        $result = Arrays::some(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return true;
            },
        );
        $this->assertFalse($result);
        $this->assertSame([], $log);

        $array = ['a', 'b'];
        $log = [];
        $result = Arrays::some(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return false;
            },
        );
        $this->assertFalse($result);
        $this->assertSame([['a', 0, $array], ['b', 1, $array]], $log);

        $array = ['a', 'b'];
        $log = [];
        $result = Arrays::some(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return true;
            },
        );
        $this->assertTrue($result);
        $this->assertSame([['a', 0, $array]], $log);

        $array = ['a', 'b'];
        $log = [];
        $result = Arrays::some(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return $v === 'a';
            },
        );
        $this->assertTrue($result);
        $this->assertSame([['a', 0, $array]], $log);

        $array = ['x' => 'a', 'y' => 'b'];
        $log = [];
        $result = Arrays::some(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return $v === 'a';
            },
        );
        $this->assertTrue($result);
        $this->assertSame([['a', 'x', $array]], $log);

        $array = new \ArrayIterator(['x' => 'a', 'y' => 'b']);
        $log = [];
        $result = Arrays::some(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return $v === 'a';
            },
        );
        $this->assertTrue($result);
        $this->assertSame([['a', 'x', $array]], $log);
    }

    public function testAll()
    {
        $array = [];
        $log = [];
        $result = Arrays::all(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return false;
            },
        );
        $this->assertTrue($result);
        $this->assertSame([], $log);

        $array = [];
        $log = [];
        $result = Arrays::all(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return true;
            },
        );
        $this->assertTrue($result);
        $this->assertSame([], $log);

        $array = ['a', 'b'];
        $log = [];
        $result = Arrays::all(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return false;
            },
        );
        $this->assertFalse($result);
        $this->assertSame([['a', 0, $array]], $log);

        $array = ['a', 'b'];
        $log = [];
        $result = Arrays::all(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return true;
            },
        );
        $this->assertTrue($result);
        $this->assertSame([['a', 0, $array], ['b', 1, $array]], $log);

        $array = ['a', 'b'];
        $log = [];
        $result = Arrays::all(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return $v === 'a';
            },
        );
        $this->assertFalse($result);
        $this->assertSame([['a', 0, $array], ['b', 1, $array]], $log);

        $array = ['x' => 'a', 'y' => 'b'];
        $log = [];
        $result = Arrays::all(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return true;
            },
        );
        $this->assertTrue($result);
        $this->assertSame([['a', 'x', $array], ['b', 'y', $array]], $log);

        $array = new \ArrayIterator(['x' => 'a', 'y' => 'b']);
        $log = [];
        $result = Arrays::all(
            $array,
            function ($v, $k, $array) use (&$log) {
                $log[] = func_get_args();
                return true;
            },
        );
        $this->assertTrue($result);
        $this->assertSame([['a', 'x', $array], ['b', 'y', $array]], $log);
    }
}
