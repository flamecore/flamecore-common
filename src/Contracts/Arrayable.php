<?php
/*
 * FlameCore Common Library
 * Copyright (C) 2022 FlameCore Team
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 */

namespace FlameCore\Common\Contracts;

/**
 * The Arrayable interface allows exporting of object data to an array.
 *
 * @author Christian Neff <christian.neff@gmail.com>
 */
interface Arrayable
{
    /*+
     * Exports object data to an array.
     *
     * @return array Returns the object data.
     */
    public function toArray(): array;
}
