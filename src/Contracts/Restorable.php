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
 * The Restorable interface allows restoring of object data.
 *
 * @author Christian Neff <christian.neff@gmail.com>
 */
interface Restorable
{
    /**
     * Restores object data from an array.
     *
     * @param array $data The data to restore.
     */
    public function restore(array $data);
}
