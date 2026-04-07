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

use Horde\Tree\State\CallbackStateStorage;
use Horde\Tree\State\NullStateStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for state storage implementations.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(NullStateStorage::class)]
#[CoversClass(CallbackStateStorage::class)]
class StateStorageTest extends TestCase
{
    public function testNullStorageAlwaysReturnsNull(): void
    {
        $storage = new NullStateStorage();
        $this->assertNull($storage->isExpanded('tree', 'node'));
    }

    public function testNullStorageSetDoesNothing(): void
    {
        $storage = new NullStateStorage();
        $storage->setExpanded('tree', 'node', true);
        // Still null after set
        $this->assertNull($storage->isExpanded('tree', 'node'));
    }

    public function testCallbackStorageGet(): void
    {
        $store = ['mytree' => ['n1' => true]];
        $storage = new CallbackStateStorage(
            fn(string $tree, string $id) => $store[$tree][$id] ?? null,
            function () {},
        );

        $this->assertTrue($storage->isExpanded('mytree', 'n1'));
        $this->assertNull($storage->isExpanded('mytree', 'n2'));
        $this->assertNull($storage->isExpanded('other', 'n1'));
    }

    public function testCallbackStorageSet(): void
    {
        $store = [];
        $storage = new CallbackStateStorage(
            fn(string $tree, string $id) => $store[$tree][$id] ?? null,
            function (string $tree, string $id, bool $expanded) use (&$store) {
                $store[$tree][$id] = $expanded;
            },
        );

        $storage->setExpanded('mytree', 'n1', false);
        $this->assertFalse($store['mytree']['n1']);

        $storage->setExpanded('mytree', 'n1', true);
        $this->assertTrue($store['mytree']['n1']);
    }

    public function testCallbackStorageRoundTrip(): void
    {
        $store = [];
        $storage = new CallbackStateStorage(
            function (string $tree, string $id) use (&$store) {
                return $store[$tree][$id] ?? null;
            },
            function (string $tree, string $id, bool $expanded) use (&$store) {
                $store[$tree][$id] = $expanded;
            },
        );

        $this->assertNull($storage->isExpanded('t', 'n'));
        $storage->setExpanded('t', 'n', true);
        $this->assertTrue($storage->isExpanded('t', 'n'));
        $storage->setExpanded('t', 'n', false);
        $this->assertFalse($storage->isExpanded('t', 'n'));
    }
}
