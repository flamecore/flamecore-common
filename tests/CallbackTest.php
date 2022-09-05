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

use FlameCore\Common\Callback;
use FlameCore\Common\Tests\Callback\TestClass;
use PHPUnit\Framework\TestCase;

class CallbackTest extends TestCase
{
    private static $lastError = '';

    public function setUp(): void
    {
        set_error_handler(function ($severity, $message) use (&$lastMessage) {
            self::$lastError = $message;
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    public function testInvokeSafeWithoutError()
    {
        Callback::invokeSafe('trim', [''], function () {
        });

        // is error handler restored?
        trigger_error('OK', E_USER_WARNING);
        $this->assertSame('OK', self::$lastError);
    }

    public function testInvokeSafeWithSkippedError()
    {
        trigger_error('not in invokeSafe()', E_USER_WARNING);
        Callback::invokeSafe('preg_match', ['ab', 'foo'], function () {
        });

        $this->assertSame('not in invokeSafe()', self::$lastError);
    }

    public function testInvokeSafeWithErrorHandedOver()
    {
        Callback::invokeSafe('preg_match', ['ab', 'foo'], fn () => false);

        $this->assertStringContainsString('Delimiter must not be alphanumeric or backslash', self::$lastError);
    }

    public function testInvokeSafeWithErrorToException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delimiter must not be alphanumeric or backslash');

        Callback::invokeSafe('preg_match', ['ab', 'foo'], function ($message, $severity) {
            throw new \Exception($message, $severity);
        });
    }

    public function testInvokeSafeWithNestedError()
    {
        Callback::invokeSafe('preg_replace_callback', ['#.#', function () {
            $a++;
        }, 'x'], function () {
            throw new \Exception('Should not be thrown');
        });

        $this->assertSame(PHP_VERSION_ID < 80000 ? 'Undefined variable: a' : 'Undefined variable $a', self::$lastError);
    }

    public function testInvokeSafeWithNestedException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('in callback');

        Callback::invokeSafe('preg_replace_callback', ['#.#', function () {
            throw new \Exception('in callback');
        }, 'x'], function () {
        });
    }

    public function testToString()
    {
        // global function
        $this->assertSame('trim', Callback::toString('trim'));
        $this->assertSame('{closure trim}', Callback::toString(\Closure::fromCallable('trim')));
        $this->assertSame('undefined', Callback::toString('undefined'));

        // closure
        $closure = function (&$a) {
            $a = __FUNCTION__;
            return $a;
        };
        $this->assertSame('{closure}', Callback::toString($closure));

        // invokable object
        $test = new TestClass();
        $class = TestClass::class;
        $this->assertSame("{$class}::__invoke", Callback::toString($test));
        $this->assertSame("{closure {$class}::__invoke}", Callback::toString(\Closure::fromCallable($test)));

        // object methods
        $this->assertSame("{$class}::publicFun", Callback::toString([$test, 'publicFun']));
        $this->assertSame("{closure {$class}::publicFun}", Callback::toString(\Closure::fromCallable([$test, 'publicFun'])));
        $this->assertSame("{$class}::privateFun", Callback::toString([$test, 'privateFun']));
        $this->assertSame("{closure {$class}::privateFun}", Callback::toString(\Closure::fromCallable([$test, 'privateFun'])));

        // static methods
        $this->assertSame("{$class}::publicStatic", Callback::toString([$class, 'publicStatic']));
        $this->assertSame("{$class}::publicStatic", Callback::toString([$test, 'publicStatic']));
        $this->assertSame("{$class}::publicStatic", Callback::toString("{$class}::publicStatic"));
        $this->assertSame("{closure {$class}::publicStatic}", Callback::toString(\Closure::fromCallable("{$class}::publicStatic")));
        $this->assertSame("{$class}::privateStatic", Callback::toString("{$class}::privateStatic"));
        $this->assertSame("{closure {$class}::privateStatic}", Callback::toString(\Closure::fromCallable("{$class}::privateStatic")));

        // magic methods
        $this->assertSame("{$class}::magic", Callback::toString([$test, 'magic']));
        $this->assertSame('{closure ' . $class . '::magic}', Callback::toString(\Closure::fromCallable([$test, 'magic'])));
        $this->assertSame("{$class}::magic", Callback::toString("{$class}::magic"));
        $this->assertSame('{closure ' . $class . '::magic}', Callback::toString(\Closure::fromCallable("{$class}::magic")));

        // stdClass::__invoke()
        $this->assertSame('stdClass::__invoke', Callback::toString(new \stdClass()));
    }

    public function testToReflection()
    {
        // global function
        $this->assertSame('trim', $this->toReflName(Callback::toReflection('trim')));
        $this->assertSame('trim', $this->toReflName(Callback::toReflection(\Closure::fromCallable('trim'))));

        // closure
        $closure = function (&$a) {
            $a = __FUNCTION__;
            return $a;
        };
        $this->assertSame('FlameCore\Common\Tests\{closure}', $this->toReflName(Callback::toReflection($closure)));

        // invokable object
        $test = new TestClass();
        $class = TestClass::class;
        $this->assertSame("{$class}::__invoke", $this->toReflName(Callback::toReflection($test)));
        $this->assertSame("{$class}::__invoke", $this->toReflName(Callback::toReflection(\Closure::fromCallable($test))));

        // object methods
        $this->assertSame("{$class}::publicFun", $this->toReflName(Callback::toReflection([$test, 'publicFun'])));
        $this->assertSame("{$class}::publicFun", $this->toReflName(Callback::toReflection(\Closure::fromCallable([$test, 'publicFun']))));
        $this->assertSame("{$class}::privateFun", $this->toReflName(Callback::toReflection([$test, 'privateFun'])));
        $this->assertSame("{$class}::privateFun", $this->toReflName(Callback::toReflection(\Closure::fromCallable([$test, 'privateFun']))));

        // static methods
        $this->assertSame("{$class}::publicStatic", $this->toReflName(Callback::toReflection([$class, 'publicStatic'])));
        $this->assertSame("{$class}::publicStatic", $this->toReflName(Callback::toReflection([$test, 'publicStatic'])));
        $this->assertSame("{$class}::publicStatic", $this->toReflName(Callback::toReflection("{$class}::publicStatic")));
        $this->assertSame("{$class}::publicStatic", $this->toReflName(Callback::toReflection(\Closure::fromCallable("{$class}::publicStatic"))));
        $this->assertSame("{$class}::privateStatic", $this->toReflName(Callback::toReflection("{$class}::privateStatic")));
        $this->assertSame("{$class}::privateStatic", $this->toReflName(Callback::toReflection(\Closure::fromCallable("{$class}::privateStatic"))));
    }

    /**
     * @dataProvider provideDataForToReflectionException
     *
     * @param mixed  $param
     * @param string $message
     */
    public function testToReflectionException($param, string $message)
    {
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage($message);

        Callback::toReflection($param);
    }

    public function provideDataForToReflectionException()
    {
        $class = TestClass::class;

        return [
            ['undefined', 'Function undefined() does not exist'],

            // magic methods
            [[new TestClass(), 'magic'], "Method {$class}::magic() does not exist"],
            [\Closure::fromCallable([new TestClass(), 'magic']), "Method {$class}::magic() does not exist"],

            // stdClass::__invoke()
            [new \stdClass(), 'Method stdClass::__invoke() does not exist'],
        ];
    }

    public function testIsStatic()
    {
        $this->assertTrue(Callback::isStatic('trim'));
        $this->assertTrue(Callback::isStatic([\DateTime::class, 'createFromFormat']));
    }

    public function testUnwrap()
    {
        // global function
        $this->assertSame('trim', Callback::unwrap(\Closure::fromCallable('trim')));

        // closure
        $closure = function (&$a) {
            $a = __FUNCTION__;
            return $a;
        };
        $this->assertSame($closure, Callback::unwrap($closure));

        // invokable object
        $test = new TestClass();
        $this->assertSame([$test, '__invoke'], Callback::unwrap(\Closure::fromCallable($test)));

        // object methods
        $this->assertSame([$test, 'publicFun'], Callback::unwrap(\Closure::fromCallable([$test, 'publicFun'])));
        $this->assertSame([$test, 'privateFun'], Callback::unwrap(\Closure::fromCallable([$test, 'privateFun'])));

        // static methods
        $class = TestClass::class;
        $this->assertSame([$class, 'publicStatic'], Callback::unwrap(\Closure::fromCallable([$class, 'publicStatic'])));
        $this->assertSame([$class, 'publicStatic'], Callback::unwrap(\Closure::fromCallable("{$class}::publicStatic")));
        $this->assertSame([$class, 'privateStatic'], Callback::unwrap(\Closure::fromCallable("{$class}::privateStatic")));

        // magic methods
        $this->assertSame([$test, 'magic'], Callback::unwrap(\Closure::fromCallable([$test, 'magic'])));
        $this->assertSame([$class, 'magic'], Callback::unwrap(\Closure::fromCallable("{$class}::magic")));
    }

    private function toReflName(\ReflectionFunctionAbstract $refl)
    {
        if ($refl instanceof \ReflectionFunction) {
            return $refl->getName();
        } elseif ($refl instanceof \ReflectionMethod) {
            return $refl->getDeclaringClass()->getName() . '::' . $refl->getName();
        }

        return null;
    }
}
