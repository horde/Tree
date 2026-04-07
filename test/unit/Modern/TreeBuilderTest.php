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
use Horde\Tree\TreeBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the TreeBuilder.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(TreeBuilder::class)]
class TreeBuilderTest extends TestCase
{
    public function testBuildEmptyTree(): void
    {
        $tree = (new TreeBuilder('empty'))->build();
        $this->assertCount(0, $tree);
        $this->assertSame('empty', $tree->name);
    }

    public function testAddNodeReturnsBuilder(): void
    {
        $builder = new TreeBuilder('test');
        $result = $builder->addNode(new Node('id', 'Label'));
        $this->assertSame($builder, $result);
    }

    public function testFluentApi(): void
    {
        $tree = (new TreeBuilder('fluent'))
            ->addNode(new Node('a', 'A'))
            ->addNode(new Node('b', 'B', 'a'))
            ->addNode(new Node('c', 'C', 'b'))
            ->build();

        $this->assertCount(3, $tree);
        $this->assertSame(['a'], $tree->getRootNodeIds());
        $this->assertSame(['b'], $tree->getChildIds('a'));
        $this->assertSame(['c'], $tree->getChildIds('b'));
    }

    public function testAddNodeParamsUpdatesExisting(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n1', 'Node'))
            ->addNodeParams('n1', ['icon' => '/icon.png', 'url' => '/page'])
            ->build();

        $node = $tree->getNode('n1');
        $this->assertSame('/icon.png', $node->params['icon']);
        $this->assertSame('/page', $node->params['url']);
    }

    public function testAddNodeParamsThrowsOnMissing(): void
    {
        $builder = new TreeBuilder('test');
        $this->expectException(Exception::class);
        $builder->addNodeParams('nonexistent', ['key' => 'val']);
    }

    public function testDuplicateNodeReplaces(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n1', 'Original'))
            ->addNode(new Node('n1', 'Replacement'))
            ->build();

        $this->assertCount(1, $tree);
        $this->assertSame('Replacement', $tree->getNode('n1')->label);
    }

    public function testDuplicateRootNotDuplicated(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('r', 'Root'))
            ->addNode(new Node('r', 'Root Again'))
            ->build();

        $this->assertSame(['r'], $tree->getRootNodeIds());
    }

    public function testDuplicateChildNotDuplicated(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent'))
            ->addNode(new Node('c', 'Child', 'p'))
            ->addNode(new Node('c', 'Child Again', 'p'))
            ->build();

        $this->assertSame(['c'], $tree->getChildIds('p'));
    }

    public function testBuildProducesImmutableTree(): void
    {
        $builder = new TreeBuilder('test');
        $builder->addNode(new Node('a', 'A'));
        $tree1 = $builder->build();

        $builder->addNode(new Node('b', 'B'));
        $tree2 = $builder->build();

        // tree1 should have 1 node, tree2 should have 2
        $this->assertCount(1, $tree1);
        $this->assertCount(2, $tree2);
    }
}
