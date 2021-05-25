<?php
/*
 * Copyright 2017 Google LLC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *     * Neither the name of Google Inc. nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
namespace Google\ApiCore\Tests\Unit;

use Google\ApiCore\ApiStatus;
use Google\Rpc\Code;
use PHPUnit\Framework\TestCase;

class ApiStatusTest extends TestCase
{
    /**
     * @dataProvider getValidStatus
     */
    public function testValidate($status)
    {
        $this->assertTrue(ApiStatus::isValidStatus($status));
    }

    /**
     * @dataProvider getInvalidStatus
     */
    public function testValidateInvalid($status)
    {
        $this->assertFalse(ApiStatus::isValidStatus($status));
    }

    public function getValidStatus()
    {
        return [
            ['OK'],
            ['CANCELLED'],
            ['UNKNOWN'],
            ['INVALID_ARGUMENT'],
            ['DEADLINE_EXCEEDED'],
            ['NOT_FOUND'],
            ['ALREADY_EXISTS'],
            ['PERMISSION_DENIED'],
            ['RESOURCE_EXHAUSTED'],
            ['FAILED_PRECONDITION'],
            ['ABORTED'],
            ['OUT_OF_RANGE'],
            ['UNIMPLEMENTED'],
            ['INTERNAL'],
            ['UNAVAILABLE'],
            ['DATA_LOSS'],
            ['UNAUTHENTICATED'],
        ];
    }

    public function getInvalidStatus()
    {
        return [
            ['UNRECOGNIZED_STATUS'],
            [''],
            ['NONSENSE'],
        ];
    }

    /**
     * @dataProvider getCodeAndStatus
     */
    public function testStatusFromRpcCode($code, $status)
    {
        $this->assertSame($status, ApiStatus::statusFromRpcCode($code));
    }

    public function getCodeAndStatus()
    {
        return [
            [Code::OK, ApiStatus::OK],
            [Code::CANCELLED, ApiStatus::CANCELLED],
            [Code::UNKNOWN, ApiStatus::UNKNOWN],
            [Code::INVALID_ARGUMENT, ApiStatus::INVALID_ARGUMENT],
            [Code::DEADLINE_EXCEEDED, ApiStatus::DEADLINE_EXCEEDED],
            [Code::NOT_FOUND, ApiStatus::NOT_FOUND],
            [Code::ALREADY_EXISTS, ApiStatus::ALREADY_EXISTS],
            [Code::PERMISSION_DENIED, ApiStatus::PERMISSION_DENIED],
            [Code::RESOURCE_EXHAUSTED, ApiStatus::RESOURCE_EXHAUSTED],
            [Code::FAILED_PRECONDITION, ApiStatus::FAILED_PRECONDITION],
            [Code::ABORTED, ApiStatus::ABORTED],
            [Code::OUT_OF_RANGE, ApiStatus::OUT_OF_RANGE],
            [Code::UNIMPLEMENTED, ApiStatus::UNIMPLEMENTED],
            [Code::INTERNAL, ApiStatus::INTERNAL],
            [Code::UNAVAILABLE, ApiStatus::UNAVAILABLE],
            [Code::DATA_LOSS, ApiStatus::DATA_LOSS],
            [Code::UNAUTHENTICATED, ApiStatus::UNAUTHENTICATED],
            [-1, ApiStatus::UNRECOGNIZED_STATUS]
        ];
    }
}
