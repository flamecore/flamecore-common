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

use Error;

/**
 * The StaticClass trait helps to create purely static classes that cannot be instantiated.
 *
 * @author Christian Neff <christian.neff@gmail.com>
 */
trait StaticClass
{
    /**
     * The class should not be instantiated.
     */
    final public function __construct()
    {
        throw new Error(sprintf('Class "%s" is static and cannot be instantiated.', static::class));
    }
}
