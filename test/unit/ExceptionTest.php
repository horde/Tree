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

namespace Horde\Tree\Test;

use Horde_Exception_Wrapped;
use Horde_Tree_Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Tree_Exception class.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Horde_Tree_Exception::class)]
class ExceptionTest extends TestCase
{
    public function testIsHordeException(): void
    {
        $exception = new Horde_Tree_Exception('test error');
        $this->assertInstanceOf(Horde_Exception_Wrapped::class, $exception);
    }

    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(Horde_Tree_Exception::class);
        $this->expectExceptionMessage('Tree error occurred');

        throw new Horde_Tree_Exception('Tree error occurred');
    }
}
