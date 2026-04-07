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
use Horde_Tree_Renderer_Simplehtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Tree_Renderer_Simplehtml renderer.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Horde_Tree_Renderer_Simplehtml::class)]
class SimplehtmlRendererTest extends TestCase
{
    protected function setUp(): void
    {
        // Simplehtml uses $_SERVER['PHP_SELF'] for toggle URLs
        $_SERVER['PHP_SELF'] = '/test.php';
    }

    private function createRenderer(): Horde_Tree_Renderer_Simplehtml
    {
        $tree = new Horde_Tree('test');
        return new Horde_Tree_Renderer_Simplehtml($tree);
    }

    public function testEmptyTreeRendersEmpty(): void
    {
        $renderer = $this->createRenderer();
        $output = $renderer->getTree();
        $this->assertSame('', $output);
    }

    public function testSingleRootNode(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root Node']);

        $output = $renderer->getTree();
        $this->assertStringContainsString('<div', $output);
        $this->assertStringContainsString('Root Node', $output);
        $this->assertStringContainsString('</div>', $output);
    }

    public function testNestedNodesIndented(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root']);
        $renderer->addNode(['id' => 'child', 'parent' => 'root', 'label' => 'Child']);

        $output = $renderer->getTree();
        // Child should have indentation (2 * indent level = 2 nbsp)
        $this->assertStringContainsString('&nbsp;&nbsp;Child', $output);
    }

    public function testNodeWithUrl(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'linked',
            'parent' => null,
            'label' => 'Linked Node',
            'params' => ['url' => 'http://example.com'],
        ]);

        $output = $renderer->getTree();
        $this->assertStringContainsString('href="http://example.com"', $output);
        $this->assertStringContainsString('Linked Node', $output);
    }

    public function testNodeWithClass(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'styled',
            'parent' => null,
            'label' => 'Styled',
            'params' => ['class' => 'highlight'],
        ]);

        $output = $renderer->getTree();
        $this->assertStringContainsString('class="highlight"', $output);
    }

    public function testExpandedNodeShowsMinusSymbol(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'parent',
            'parent' => null,
            'label' => 'Parent',
            'expanded' => true,
        ]);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        $output = $renderer->getTree();
        // Expanded parent shows [-] toggle
        $this->assertStringContainsString('-</a>]', $output);
    }

    public function testCollapsedNodeShowsPlusSymbol(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'parent',
            'parent' => null,
            'label' => 'Parent',
            'expanded' => false,
        ]);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        $output = $renderer->getTree();
        // Collapsed parent shows [+] toggle
        $this->assertStringContainsString('+</a>]', $output);
        // Children should not be rendered
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testToggleLinkContainsTreeInstance(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'parent', 'parent' => null, 'label' => 'P']);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'C']);

        $output = $renderer->getTree();
        // Toggle link should contain the tree toggle parameter
        $this->assertStringContainsString('ht_toggle_test', $output);
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

        // Even with static=false, Simplehtml always renders children
        $output = $renderer->getTree(false);
        $this->assertStringContainsString('Child', $output);
    }

    public function testLeafNodeHasNoToggle(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'leaf', 'parent' => null, 'label' => 'Leaf']);

        $output = $renderer->getTree();
        // Leaf nodes should not have [+] or [-] toggle
        $this->assertStringNotContainsString('[', $output);
        $this->assertStringNotContainsString(']', $output);
    }

    public function testExtraColumnsLeft(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Node',
            'left' => ['[icon]'],
        ]);

        $output = $renderer->getTree();
        $this->assertStringContainsString('[icon]', $output);
    }

    public function testExtraColumnsRight(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Node',
            'right' => ['status'],
        ]);

        $output = $renderer->getTree();
        $this->assertStringContainsString('status', $output);
    }

    public function testNodeWithoutUrlRendersPlainLabel(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'plain', 'parent' => null, 'label' => 'Plain Text']);

        $output = $renderer->getTree();
        $this->assertStringContainsString('Plain Text', $output);
        // Should not have an <a> link for the label (there may be one for toggle though)
        // Count: if it's a leaf, no <a> at all
        $this->assertStringNotContainsString('href=', $output);
    }
}
