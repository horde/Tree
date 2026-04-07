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

use Horde\Tree\Exception;
use Horde\Tree\Node;
use Horde\Tree\Tree;
use Horde\Tree\TreeBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the immutable Tree data structure.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Tree::class)]
class TreeTest extends TestCase
{
    private function buildSimpleTree(): Tree
    {
        return (new TreeBuilder('test'))
            ->addNode(new Node('root', 'Root'))
            ->addNode(new Node('child1', 'Child 1', 'root'))
            ->addNode(new Node('child2', 'Child 2', 'root'))
            ->addNode(new Node('grandchild', 'Grandchild', 'child1'))
            ->build();
    }

    public function testName(): void
    {
        $tree = (new TreeBuilder('mytree'))->build();
        $this->assertSame('mytree', $tree->name);
    }

    public function testCountEmptyTree(): void
    {
        $tree = (new TreeBuilder('empty'))->build();
        $this->assertCount(0, $tree);
    }

    public function testCountWithNodes(): void
    {
        $tree = $this->buildSimpleTree();
        $this->assertCount(4, $tree);
    }

    public function testGetRootNodeIds(): void
    {
        $tree = $this->buildSimpleTree();
        $this->assertSame(['root'], $tree->getRootNodeIds());
    }

    public function testMultipleRoots(): void
    {
        $tree = (new TreeBuilder('multi'))
            ->addNode(new Node('r1', 'Root 1'))
            ->addNode(new Node('r2', 'Root 2'))
            ->build();
        $this->assertSame(['r1', 'r2'], $tree->getRootNodeIds());
    }

    public function testGetNode(): void
    {
        $tree = $this->buildSimpleTree();
        $node = $tree->getNode('child1');
        $this->assertSame('child1', $node->id);
        $this->assertSame('Child 1', $node->label);
        $this->assertSame('root', $node->parentId);
    }

    public function testGetNodeThrowsOnMissing(): void
    {
        $tree = $this->buildSimpleTree();
        $this->expectException(Exception::class);
        $tree->getNode('nonexistent');
    }

    public function testHasNode(): void
    {
        $tree = $this->buildSimpleTree();
        $this->assertTrue($tree->hasNode('root'));
        $this->assertFalse($tree->hasNode('missing'));
    }

    public function testGetChildIds(): void
    {
        $tree = $this->buildSimpleTree();
        $this->assertSame(['child1', 'child2'], $tree->getChildIds('root'));
        $this->assertSame(['grandchild'], $tree->getChildIds('child1'));
        $this->assertSame([], $tree->getChildIds('child2'));
    }

    public function testGetChildren(): void
    {
        $tree = $this->buildSimpleTree();
        $children = $tree->getChildren('root');
        $this->assertCount(2, $children);
        $this->assertSame('child1', $children[0]->id);
        $this->assertSame('child2', $children[1]->id);
    }

    public function testGetIndentLevel(): void
    {
        $tree = $this->buildSimpleTree();
        $this->assertSame(0, $tree->getIndentLevel('root'));
        $this->assertSame(1, $tree->getIndentLevel('child1'));
        $this->assertSame(2, $tree->getIndentLevel('grandchild'));
    }

    public function testSortedByLabel(): void
    {
        $tree = (new TreeBuilder('sort'))
            ->addNode(new Node('root', 'Root'))
            ->addNode(new Node('c', 'Cherry', 'root'))
            ->addNode(new Node('a', 'Apple', 'root'))
            ->addNode(new Node('b', 'Banana', 'root'))
            ->build();

        $sorted = $tree->sorted('label');

        // Original unchanged
        $this->assertSame(['c', 'a', 'b'], $tree->getChildIds('root'));
        // Sorted returns new tree
        $this->assertSame(['a', 'b', 'c'], $sorted->getChildIds('root'));
    }

    public function testSortedIsImmutable(): void
    {
        $tree = (new TreeBuilder('sort'))
            ->addNode(new Node('root', 'Root'))
            ->addNode(new Node('z', 'Zebra', 'root'))
            ->addNode(new Node('a', 'Alpha', 'root'))
            ->build();

        $sorted = $tree->sorted('label');
        $this->assertNotSame($tree, $sorted);
        // Original order preserved
        $this->assertSame(['z', 'a'], $tree->getChildIds('root'));
    }

    public function testSortedRecursive(): void
    {
        $tree = (new TreeBuilder('sort'))
            ->addNode(new Node('root', 'Root'))
            ->addNode(new Node('b', 'B', 'root'))
            ->addNode(new Node('a', 'A', 'root'))
            ->addNode(new Node('b2', 'B2', 'b'))
            ->addNode(new Node('b1', 'B1', 'b'))
            ->build();

        $sorted = $tree->sorted('label');
        $this->assertSame(['a', 'b'], $sorted->getChildIds('root'));
        $this->assertSame(['b1', 'b2'], $sorted->getChildIds('b'));
    }

    public function testSortedByParam(): void
    {
        $tree = (new TreeBuilder('sort'))
            ->addNode(new Node('root', 'Root'))
            ->addNode(new Node('low', 'Low', 'root', params: ['priority' => '3']))
            ->addNode(new Node('high', 'High', 'root', params: ['priority' => '1']))
            ->addNode(new Node('mid', 'Mid', 'root', params: ['priority' => '2']))
            ->build();

        $sorted = $tree->sorted('priority');
        $this->assertSame(['high', 'mid', 'low'], $sorted->getChildIds('root'));
    }

    public function testSortedRootNodes(): void
    {
        $tree = (new TreeBuilder('sort'))
            ->addNode(new Node('z', 'Zebra'))
            ->addNode(new Node('a', 'Alpha'))
            ->addNode(new Node('m', 'Middle'))
            ->build();

        $sorted = $tree->sorted('label');
        $this->assertSame(['a', 'm', 'z'], $sorted->getRootNodeIds());
    }

    public function testGetNodes(): void
    {
        $tree = $this->buildSimpleTree();
        $nodes = $tree->getNodes();
        $this->assertCount(4, $nodes);
        $this->assertArrayHasKey('root', $nodes);
        $this->assertArrayHasKey('grandchild', $nodes);
    }

    public function testGetChildIdsOfLeafNode(): void
    {
        $tree = $this->buildSimpleTree();
        $this->assertSame([], $tree->getChildIds('grandchild'));
    }

    public function testGetChildIdsOfNonexistentParent(): void
    {
        $tree = $this->buildSimpleTree();
        $this->assertSame([], $tree->getChildIds('nonexistent'));
    }
}
