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
 * This exception is thrown when an argument does not match with the expected value.
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
