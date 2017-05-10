<?php
/**
 * @package
 * @subpackage
 * @author      Chance Garcia <chance@garcia.codes>
 * @copyright   (C)Copyright 2013-2017 Chance Garcia, chancegarcia.com
 *
 *    The MIT License (MIT)
 *
 * Copyright (c) 2013-2017 Chance Garcia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace Chance\RestApi\BridgeBundle\Exception\Handler;

use Chance\RestApi\BridgeBundle\Exception\UuidException;

class HandlerException extends UuidException
{
    const ACCESS_DENIED = '36d0d55d-079f-44e6-92b9-024ab4ebebb4';
    const BAD_REQUEST = 'a2ccce5f-6268-41c1-b5a1-654f965242da';
    const MISSING_FORM_TYPE = '65d126dd-7bc5-4f67-b9a3-04e875fdc5fd';
    const INVALID_ID = 'b7c00792-4f01-4750-89db-21c828a39ab1';
    const MISSING_ENTITY_CLASS = '8579aa41-430a-4b9a-bba5-e7191cf0dad9';
    const ANONYMOUS_USER_NOT_ALLOWED = '73966900-482e-49f1-a44c-e264ff4089f0';
    const MISSING_DEPENDENCY = '218931d1-2055-4e91-b3ef-f146ee1e9ba8';
    const INVALID_ENTITY_ROUTE_KEY = 'ab769448-7426-482f-892f-fd4ab5ef4680';
    const INVALID_KEY = '9d654e29-7aba-49f3-b137-124c12ddaa22';
    const INVALID_TYPE = 'dd7a8d8c-eba8-449e-ae1e-7f64afc71c4d';
    const DUPLICATE_ENTRY = 'c3df04db-c3ee-4f62-b59c-04a142a06c91';
}