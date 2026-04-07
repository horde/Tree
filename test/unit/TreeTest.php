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

use Horde_Tree;
use Horde_Util;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the core Horde_Tree data structure.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Horde_Tree::class)]
class TreeTest extends TestCase
{
    public function testConstructorSetsInstance(): void
    {
        $tree = new Horde_Tree('mytree');
        $this->assertSame('mytree', $tree->instance);
    }

    public function testCountEmptyTree(): void
    {
        $tree = new Horde_Tree('empty');
        $this->assertCount(0, $tree);
    }

    public function testAddNodeIncrementsCount(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'a', 'label' => 'A']);
        $this->assertCount(1, $tree);

        $tree->addNode(['id' => 'b', 'label' => 'B']);
        $this->assertCount(2, $tree);
    }

    public function testAddRootNode(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'root1', 'parent' => null, 'label' => 'Root']);

        $rootNodes = $tree->getRootNodes();
        $this->assertCount(1, $rootNodes);
        $this->assertSame('root1', $rootNodes[0]);
    }

    public function testAddChildNode(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'parent', 'parent' => null, 'label' => 'Parent']);
        $tree->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        $rootNodes = $tree->getRootNodes();
        $this->assertCount(1, $rootNodes);
        $this->assertSame('parent', $rootNodes[0]);

        $nodes = $tree->getNodes();
        $this->assertContains('child', $nodes['parent']['children']);
    }

    public function testAddMultipleRootNodes(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'r1', 'parent' => null, 'label' => 'Root 1']);
        $tree->addNode(['id' => 'r2', 'parent' => null, 'label' => 'Root 2']);
        $tree->addNode(['id' => 'r3', 'parent' => null, 'label' => 'Root 3']);

        $this->assertCount(3, $tree->getRootNodes());
    }

    public function testGetNodesComputesIndents(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root']);
        $tree->addNode(['id' => 'child', 'parent' => 'root', 'label' => 'Child']);
        $tree->addNode(['id' => 'grandchild', 'parent' => 'child', 'label' => 'Grandchild']);

        $nodes = $tree->getNodes();
        $this->assertSame(0, $nodes['root']['indent']);
        $this->assertSame(1, $nodes['child']['indent']);
        $this->assertSame(2, $nodes['grandchild']['indent']);
    }

    public function testAddNodeParams(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'node1', 'parent' => null, 'label' => 'Node']);
        $tree->addNodeParams('node1', ['icon' => '/icon.png', 'url' => '/link']);

        $nodes = $tree->getNodes();
        $this->assertSame('/icon.png', $nodes['node1']['icon']);
        $this->assertSame('/link', $nodes['node1']['url']);
    }

    public function testAddNodeWithInlineParams(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode([
            'id' => 'node1',
            'parent' => null,
            'label' => 'Node',
            'params' => ['icon' => '/my-icon.png'],
        ]);

        $nodes = $tree->getNodes();
        $this->assertSame('/my-icon.png', $nodes['node1']['icon']);
    }

    public function testIsExpandedDefaultTrue(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'node1', 'parent' => null, 'label' => 'Node']);

        $this->assertTrue($tree->isExpanded('node1'));
    }

    public function testIsExpandedExplicitFalse(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode([
            'id' => 'node1',
            'parent' => null,
            'label' => 'Node',
            'expanded' => false,
        ]);

        $this->assertFalse($tree->isExpanded('node1'));
    }

    public function testIsExpandedReturnsFalseForUnknownNode(): void
    {
        $tree = new Horde_Tree('test');
        $this->assertFalse($tree->isExpanded('nonexistent'));
    }

    public function testNodeIdUrlEncodes(): void
    {
        $tree = new Horde_Tree('test');
        $this->assertSame('hello%20world', $tree->nodeId('hello world'));
        $this->assertSame('a%2Fb', $tree->nodeId('a/b'));
        $this->assertSame('simple', $tree->nodeId('simple'));
    }

    public function testSortByLabel(): void
    {
        $tree = new Horde_Tree('test');

        // Add a virtual parent to hold root nodes for sorting.
        // The sort() method sorts children of a given ID.
        // For root nodes, we need a different approach - use the renderer's sort.
        // Actually, Horde_Tree::sort() takes an $id param that defaults to -1.
        // It sorts _nodes[$id]['children']. For root nodes we need to check
        // how the caller uses it. Let's test child sorting.
        $tree->addNode(['id' => 'parent', 'parent' => null, 'label' => 'Parent']);
        $tree->addNode(['id' => 'c', 'parent' => 'parent', 'label' => 'Cherry']);
        $tree->addNode(['id' => 'a', 'parent' => 'parent', 'label' => 'Apple']);
        $tree->addNode(['id' => 'b', 'parent' => 'parent', 'label' => 'Banana']);

        $tree->sort('label', 'parent');
        $nodes = $tree->getNodes();

        $children = $nodes['parent']['children'];
        $labels = array_map(fn($id) => $nodes[$id]['label'], $children);
        $this->assertSame(['Apple', 'Banana', 'Cherry'], $labels);
    }

    public function testSortRecursive(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root']);
        $tree->addNode(['id' => 'z', 'parent' => 'root', 'label' => 'Zebra']);
        $tree->addNode(['id' => 'a', 'parent' => 'root', 'label' => 'Ant']);
        $tree->addNode(['id' => 'z1', 'parent' => 'z', 'label' => 'Zulu']);
        $tree->addNode(['id' => 'z2', 'parent' => 'z', 'label' => 'Zeta']);

        $tree->sort('label', 'root');
        $nodes = $tree->getNodes();

        // Children of root should be sorted
        $rootChildren = $nodes['root']['children'];
        $this->assertSame('a', $rootChildren[0]);
        $this->assertSame('z', $rootChildren[1]);

        // Children of 'z' should also be sorted
        $zChildren = $nodes['z']['children'];
        $this->assertSame('Zeta', $nodes[$zChildren[0]]['label']);
        $this->assertSame('Zulu', $nodes[$zChildren[1]]['label']);
    }

    public function testSessionCallbacksUsed(): void
    {
        $sessionStore = [];
        $getCalled = false;

        $session = [
            'get' => function (string $instance, string $id) use (&$sessionStore, &$getCalled) {
                $getCalled = true;
                return $sessionStore[$instance][$id] ?? null;
            },
            'set' => function (string $instance, string $id, bool $value) use (&$sessionStore) {
                $sessionStore[$instance][$id] = $value;
            },
        ];

        $tree = new Horde_Tree('mytree', $session);
        $tree->addNode(['id' => 'node1', 'parent' => null, 'label' => 'Node']);

        $this->assertTrue($getCalled);
    }

    public function testDuplicateRootNodeNotAddedTwice(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root']);
        $tree->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root Updated']);

        $this->assertCount(1, $tree->getRootNodes());
        $this->assertCount(1, $tree);
    }

    public function testDuplicateChildNotAddedTwice(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'parent', 'parent' => null, 'label' => 'Parent']);
        $tree->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);
        $tree->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child Again']);

        $nodes = $tree->getNodes();
        $this->assertCount(1, $nodes['parent']['children']);
    }

    public function testNullParamsNotStored(): void
    {
        $tree = new Horde_Tree('test');
        $tree->addNode(['id' => 'node1', 'parent' => null, 'label' => 'Node']);
        $tree->addNodeParams('node1', ['icon' => null, 'url' => '/link']);

        $nodes = $tree->getNodes();
        $this->assertArrayNotHasKey('icon', $nodes['node1']);
        $this->assertSame('/link', $nodes['node1']['url']);
    }
}
