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
 * Utilities for PHP callables.
 *
 * @author Nette Framework team and contributors
 */
class Callback
{
    use StaticClass;

    /**
     * Invokes an internal PHP function using the given error handler.
     *
     * @param string   $function  The function to be called
     * @param array    $arguments The parameters to be passed to the callback, as an indexed array
     * @param callable $onError   A callback of the error handler to use, with the following signature:
     *                            `function (string $message, int $severity): bool`
     *
     * @return mixed Returns the return value of the callback
     */
    public static function invokeSafe(string $function, array $arguments, callable $onError)
    {
        $prev = set_error_handler(function ($severity, $message, $file) use ($onError, &$prev, $function): ?bool {
            if ($file === __FILE__) {
                $message = ini_get('html_errors') ? Strings::stripHtml($message) : $message;
                $message = preg_replace("#^$function\\(.*?\\): #", '', $message);

                if ($onError($message, $severity) !== false) {
                    return null;
                }
            }

            return $prev ? $prev(...func_get_args()) : false;
        });

        try {
            return $function(...$arguments);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Converts the given callback to textual form. The class or method may not exist.
     *
     * @param callable|mixed $callback The callback or PHP callable notation to convert
     */
    public static function toString($callback): string
    {
        if ($callback instanceof \Closure) {
            $inner = self::unwrap($callback);

            return sprintf('{closure%s}', $inner instanceof \Closure ? '' : ' ' . self::toString($inner));
        } elseif (is_string($callback) && $callback[0] === "\0") {
            return '{lambda}';
        } else {
            is_callable(is_object($callback) ? [$callback, '__invoke'] : $callback, true, $textual);

            return $textual;
        }
    }

    /**
     * Returns the reflection for the method or function used in the given callback.
     *
     * @param callable $callback The callback to get the reflection for
     *
     * @return \ReflectionMethod|\ReflectionFunction
     *
     * @throws \ReflectionException if the callback is not valid
     */
    public static function toReflection($callback): \ReflectionFunctionAbstract
    {
        if ($callback instanceof \Closure) {
            $callback = self::unwrap($callback);
        }

        if (is_string($callback) && strpos($callback, '::')) {
            return new \ReflectionMethod($callback);
        } elseif (is_array($callback)) {
            return new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_object($callback) && !$callback instanceof \Closure) {
            return new \ReflectionMethod($callback, '__invoke');
        } else {
            return new \ReflectionFunction($callback);
        }
    }

    /**
     * Checks whether the given callback is a function or a static method.
     *
     * @param callable $callback The callback to check
     */
    public static function isStatic(callable $callback): bool
    {
        return is_array($callback) ? is_string($callback[0]) : is_string($callback);
    }

    /**
     * Unwraps the given closure created by Closure::fromCallable().
     *
     * @param \Closure $closure The closure to unwrap
     */
    public static function unwrap(\Closure $closure): callable
    {
        $refl = new \ReflectionFunction($closure);
        if (substr($refl->name, -1) === '}') {
            return $closure;
        } elseif ($obj = $refl->getClosureThis()) {
            return [$obj, $refl->name];
        } elseif ($class = $refl->getClosureScopeClass()) {
            return [$class->name, $refl->name];
        } else {
            return $refl->name;
        }
    }
}
