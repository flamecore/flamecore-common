<?php
/*
 * FlameCore Common Library
 * Copyright (C) 2022 FlameCore Team
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 */

namespace FlameCore\Common\Exception;

/**
 * This exception indicates an error of the last Regexp execution.
 */
class RegexpException extends \Exception implements ExceptionInterface
{
    public const MESSAGES = [
        PREG_INTERNAL_ERROR => 'Internal error',
        PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit was exhausted',
        PREG_RECURSION_LIMIT_ERROR => 'Recursion limit was exhausted',
        PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data',
        PREG_BAD_UTF8_OFFSET_ERROR => 'Offset didn\'t correspond to the begin of a valid UTF-8 code point',
        6 => 'Failed due to limited JIT stack space', // PREG_JIT_STACKLIMIT_ERROR
    ];
}
