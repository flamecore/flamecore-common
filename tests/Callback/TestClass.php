<?php
/*
 * FlameCore Common Library
 * Copyright (C) 2022 FlameCore Team
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 */

namespace FlameCore\Common\Tests\Callback;

/**
 * The TestClass class.
 *
 * @author Christian Neff <christian.neff@gmail.com>
 */
class TestClass
{
    public function __invoke($a)
    {
        return __METHOD__ . $a;
    }

    public function publicFun($a)
    {
        return __METHOD__ . $a;
    }

    private function privateFun($a)
    {
        return __METHOD__ . $a;
    }

    public static function publicStatic($a)
    {
        return __METHOD__ . $a;
    }

    private static function privateStatic($a)
    {
        return __METHOD__ . $a;
    }

    public function __call($nm, $args)
    {
        return __METHOD__ . " $nm $args[0]";
    }

    public static function __callStatic($nm, $args)
    {
        return __METHOD__ . " $nm $args[0]";
    }

    public function ref(&$a)
    {
        $a = __METHOD__;
        return $a;
    }
}
