<?php
/*
 * FlameCore Common
 * Copyright (C) 2022 FlameCore Team
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 */

namespace FlameCore\Common\Tests\Arrays;

use FlameCore\Common\Arrays\ArrayEntry;
use PHPUnit\Framework\TestCase;

class ArrayEntryTest extends TestCase
{
    public function testBasic()
    {
        $array = $this->makeArray();

        $entry1 = new ArrayEntry($array, 'foo');
        $this->assertEquals('foo', $entry1->getPath());
        $this->assertTrue($entry1->exists());
        $this->assertEquals(['bar' => ['baz' => 'qux']], $entry1->get());

        $entry2 = new ArrayEntry($array, ['foo', 'bar']);
        $this->assertEquals('foo.bar', $entry2->getPath());
        $this->assertTrue($entry2->exists());
        $this->assertEquals(['baz' => 'qux'], $entry2->get());
    }

    public function testSet()
    {
        $array = $this->makeArray();

        $entry = new ArrayEntry($array, ['foo', 'to_be_set']);
        $this->assertFalse($entry->exists());

        $entry->set('new value');
        $this->assertTrue($entry->exists());
        $this->assertEquals('new value', $entry->get());
    }

    public function testRemove()
    {
        $array = $this->makeArray();

        $entry = new ArrayEntry($array, ['foo', 'bar']);
        $this->assertTrue($entry->exists());

        $entry->remove();
        $this->assertFalse($entry->exists());
    }

    public function testFromPath()
    {
        $array = $this->makeArray();

        $entry1 = ArrayEntry::fromPath($array, 'foo');
        $this->assertEquals('foo', $entry1->getPath());

        $entry2 = ArrayEntry::fromPath($array, 'foo.bar');
        $this->assertEquals('foo.bar', $entry2->getPath());
    }

    protected function makeArray(): array
    {
        return [
            'foo' => [
                'bar' => [
                    'baz' => 'qux'
                ]
            ]
        ];
    }
}
