<?php

declare(strict_types=1);

/**
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Tree
 */

namespace Horde\Tree\Test\Modern;

use Horde\Exception\HordeThrowable;
use Horde\Tree\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the modern Horde\Tree\Exception.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Exception::class)]
class ExceptionTest extends TestCase
{
    public function testImplementsHordeThrowable(): void
    {
        $exception = new Exception('test');
        $this->assertInstanceOf(HordeThrowable::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new Exception('test');
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tree error');
        throw new Exception('Tree error');
    }

    public function testCatchableAsHordeThrowable(): void
    {
        $caught = false;
        try {
            throw new Exception('test');
        } catch (HordeThrowable) {
            $caught = true;
        }
        $this->assertTrue($caught);
    }
}
