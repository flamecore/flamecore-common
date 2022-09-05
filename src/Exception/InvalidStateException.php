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
 * This exception is thrown when a method call is invalid for the object's
 * current state (method has been invoked at an illegal or inappropriate time).
 */
class InvalidStateException extends \RuntimeException implements ExceptionInterface
{
}
