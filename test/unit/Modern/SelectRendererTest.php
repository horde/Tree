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

use Horde\Tree\Node;
use Horde\Tree\Renderer\SelectRenderer;
use Horde\Tree\TreeBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the modern SelectRenderer.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(SelectRenderer::class)]
class SelectRendererTest extends TestCase
{
    public function testEmptyTree(): void
    {
        $tree = (new TreeBuilder('empty'))->build();
        $renderer = new SelectRenderer();
        $this->assertSame('', $renderer->render($tree));
    }

    public function testSingleNode(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('item1', 'First Item'))
            ->build();

        $renderer = new SelectRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('<option', $output);
        $this->assertStringContainsString('value="item1"', $output);
        $this->assertStringContainsString('First Item', $output);
    }

    public function testNestedNodesIndented(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('parent', 'Parent'))
            ->addNode(new Node('child', 'Child', 'parent'))
            ->build();

        $renderer = new SelectRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('&nbsp;&nbsp;Child', $output);
    }

    public function testSelectedNode(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('sel', 'Selected', params: ['selected' => true]))
            ->addNode(new Node('not', 'Not Selected'))
            ->build();

        $renderer = new SelectRenderer();
        $output = $renderer->render($tree);
        $this->assertSame(1, substr_count($output, 'selected="selected"'));
    }

    public function testMultipleLevelsIndentation(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('l0', 'Level 0'))
            ->addNode(new Node('l1', 'Level 1', 'l0'))
            ->addNode(new Node('l2', 'Level 2', 'l1'))
            ->build();

        $renderer = new SelectRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('&nbsp;&nbsp;&nbsp;&nbsp;Level 2', $output);
    }

    public function testHtmlEscaping(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('xss', '<script>alert("xss")</script>'))
            ->build();

        $renderer = new SelectRenderer();
        $output = $renderer->render($tree);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testNodeIdEscaped(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('a&b"c', 'Special'))
            ->build();

        $renderer = new SelectRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('value="a&amp;b&quot;c"', $output);
    }

    public function testCollapsedNodeHidesChildren(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: false))
            ->addNode(new Node('c', 'Hidden', 'p'))
            ->build();

        $renderer = new SelectRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Hidden', $output);
    }
}
