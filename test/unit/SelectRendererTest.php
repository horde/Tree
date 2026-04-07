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
use Horde_Tree_Renderer_Select;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Tree_Renderer_Select renderer.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Horde_Tree_Renderer_Select::class)]
class SelectRendererTest extends TestCase
{
    private function createRenderer(): Horde_Tree_Renderer_Select
    {
        $tree = new Horde_Tree('test');
        return new Horde_Tree_Renderer_Select($tree);
    }

    public function testEmptyTreeRendersEmpty(): void
    {
        $renderer = $this->createRenderer();
        $output = $renderer->getTree();
        $this->assertSame('', $output);
    }

    public function testSingleNodeRendersOption(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'item1', 'parent' => null, 'label' => 'First Item']);

        $output = $renderer->getTree();
        $this->assertStringContainsString('<option', $output);
        $this->assertStringContainsString('value="item1"', $output);
        $this->assertStringContainsString('First Item', $output);
        $this->assertStringContainsString('</option>', $output);
    }

    public function testNestedNodesIndented(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'parent', 'parent' => null, 'label' => 'Parent']);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        $output = $renderer->getTree();
        // Parent at indent 0 has no nbsp
        $this->assertMatchesRegularExpression('/>Parent</', $output);
        // Child at indent 1 should have 2x &nbsp;
        $this->assertStringContainsString('&nbsp;&nbsp;Child', $output);
    }

    public function testSelectedNode(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'sel',
            'parent' => null,
            'label' => 'Selected',
            'params' => ['selected' => true],
        ]);
        $renderer->addNode([
            'id' => 'notsel',
            'parent' => null,
            'label' => 'Not Selected',
        ]);

        $output = $renderer->getTree();
        $this->assertStringContainsString('selected="selected"', $output);
        // Only one should be selected
        $this->assertSame(1, substr_count($output, 'selected="selected"'));
    }

    public function testMultipleLevelsIndentation(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'l0', 'parent' => null, 'label' => 'Level 0']);
        $renderer->addNode(['id' => 'l1', 'parent' => 'l0', 'label' => 'Level 1']);
        $renderer->addNode(['id' => 'l2', 'parent' => 'l1', 'label' => 'Level 2']);

        $output = $renderer->getTree();
        // Level 0: 0 * 2 = 0 nbsp
        // Level 1: 1 * 2 = 2 nbsp
        // Level 2: 2 * 2 = 4 nbsp
        $this->assertStringContainsString('&nbsp;&nbsp;&nbsp;&nbsp;Level 2', $output);
    }

    public function testAlwaysStatic(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'parent',
            'parent' => null,
            'label' => 'Parent',
            'expanded' => true,
        ]);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        // getTree() with static=false should still render children
        // because Select renderer is always static
        $output = $renderer->getTree(false);
        $this->assertStringContainsString('Child', $output);
    }

    public function testHtmlEntitiesEscaped(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'xss',
            'parent' => null,
            'label' => '<script>alert("xss")</script>',
        ]);

        $output = $renderer->getTree();
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testNodeIdEscapedInValue(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'a&b"c',
            'parent' => null,
            'label' => 'Special',
        ]);

        $output = $renderer->getTree();
        // The node ID is rawurlencode'd by Horde_Tree, then htmlspecialchars'd
        $this->assertStringNotContainsString('a&b"c', $output);
    }

    public function testCollapsedNodeHidesChildren(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'parent',
            'parent' => null,
            'label' => 'Parent',
            'expanded' => false,
        ]);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Hidden Child']);

        $output = $renderer->getTree();
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Hidden Child', $output);
    }
}
