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

use FlameCore\Common\Tests\StaticClassTest\Test;
use PHPUnit\Framework\TestCase;

class StaticClassTest extends TestCase
{
    public function testStaticClass()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(sprintf('Class "%s" is static and cannot be instantiated.', Test::class));

        new Test();
    }
}
