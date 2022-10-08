<?php
/*
 * FlameCore Common Library
 * Copyright (C) 2022 FlameCore Team
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 */

namespace FlameCore\Common\Arrays;

use FlameCore\Common\Arrays;
use FlameCore\Common\Exception\ArrayEntryUnavailableException;
use FlameCore\Common\Exception\ArrayNotAccessibleException;

/**
 * The ArrayEntry class.
 *
 * @author Christian Neff <christian.neff@gmail.com>
 */
class ArrayEntry
{
    private array $array;
    private array $pathSegments;
    private string $path;

    /**
     * Creates a new ArrayEntry object.
     *
     * @param array|\ArrayAccess $array The input array
     * @param int|string|string[]    $key   The key as string or a multi level key path as array
     */
    public function __construct(array|\ArrayAccess &$array, int|string|array $key)
    {
        $this->array = &$array;

        $pathSegments = is_array($key) ? $key : [$key];

        if (!Arrays::all($pathSegments, fn($key) => is_string($key) || is_int($key))) {
            throw new \InvalidArgumentException('The key must be a string or an integer or an array of strings and/or integers.');
        }

        $this->pathSegments = $pathSegments;
        $this->path = implode('.', $this->pathSegments);
    }

    /**
     * Creates a new ArrayEntry object using a string path.
     *
     * @param array|\ArrayAccess $array     The input array
     * @param string             $path      The multi level key path as string
     * @param string             $separator The path separator
     */
    public static function fromPath(array|\ArrayAccess &$array, string $path, string $separator = '.')
    {
        return new static($array, explode($separator, $path));
    }

    /**
     * Returns the key path as string.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Determines whether the entry exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        try {
            $this->get();

            return true;
        } catch (ArrayEntryUnavailableException) {
            return false;
        }
    }

    /**
     * Returns the value of the array entry.
     *
     * @return mixed
     *
     * @throws ArrayNotAccessibleException
     * @throws ArrayEntryUnavailableException
     *
     * @see https://stackoverflow.com/a/39118759/2039901
     */
    public function get()
    {
        $value = $this->array;

        foreach ($this->pathSegments as $i => $segment) {
            if (!Arrays::isAccessible($value)) {
                $subPath = implode('.', array_slice($this->pathSegments, 0, $i));
                throw new ArrayNotAccessibleException(sprintf('Cannot get entry "%s" because entry "%s" is not accessible.', $this->path, $subPath));
            }

            if (!Arrays::keyExists($value, $segment)) {
                $subPath = implode('.', array_slice($this->pathSegments, 0, $i + 1));
                if ($subPath === $this->path) {
                    throw new ArrayEntryUnavailableException(sprintf('Cannot get entry "%s" because it does not exist.', $this->path));
                } else {
                    throw new ArrayEntryUnavailableException(sprintf('Cannot get entry "%s" because entry "%s" does not exist.', $this->path, $subPath));
                }
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set the value of the array entry.
     *
     * @param mixed $newValue The new value
     *
     * @throws ArrayNotAccessibleException
     *
     * @see https://stackoverflow.com/a/27930028/2039901
     */
    public function set($newValue): void
    {
        $value = &$this->array;

        foreach ($this->pathSegments as $i => $segment) {
            if (isset($value) && !Arrays::isAccessible($value)) {
                $subPath = implode('.', array_slice($this->pathSegments, 0, $i));
                throw new ArrayNotAccessibleException(sprintf('Cannot set entry "%s" because entry "%s" is not accessible.', $this->path, $subPath));
            }

            $value = &$value[$segment];
        }

        $value = $newValue;
    }

    /**
     * Remove the entry from the source array.
     *
     * @throws ArrayNotAccessibleException
     * @throws ArrayEntryUnavailableException
     */
    public function remove(): void
    {
        $last = count($this->pathSegments) - 1;
        $value = &$this->array;

        foreach ($this->pathSegments as $i => $segment) {
            if (!Arrays::isAccessible($value)) {
                $subPath = implode('.', array_slice($this->pathSegments, 0, $i));
                throw new ArrayNotAccessibleException(sprintf('Cannot remove entry "%s" because entry "%s" is not accessible.', $this->path, $subPath));
            }

            if (!Arrays::keyExists($value, $segment)) {
                $subPath = implode('.', array_slice($this->pathSegments, 0, $i + 1));
                if ($subPath === $this->path) {
                    throw new ArrayEntryUnavailableException(sprintf('Cannot remove entry "%s" because it does not exist.', $this->path));
                } else {
                    throw new ArrayEntryUnavailableException(sprintf('Cannot remove entry "%s" because entry "%s" does not exist.', $this->path, $subPath));
                }
                }

            if ($i < $last) {
                $value = &$value[$segment];
            } else {
                unset($value[$segment]);
            }
        }
    }
}
