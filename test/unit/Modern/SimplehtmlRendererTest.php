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
use Horde\Tree\Renderer\SimplehtmlRenderer;
use Horde\Tree\State\CallbackStateStorage;
use Horde\Tree\TreeBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tests for the modern SimplehtmlRenderer.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(SimplehtmlRenderer::class)]
class SimplehtmlRendererTest extends TestCase
{
    public function testEmptyTree(): void
    {
        $tree = (new TreeBuilder('empty'))->build();
        $renderer = new SimplehtmlRenderer();
        $this->assertSame('', $renderer->render($tree));
    }

    public function testSingleRootNode(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('root', 'Root Node'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('<div', $output);
        $this->assertStringContainsString('Root Node', $output);
    }

    public function testNestedNodesIndented(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('root', 'Root'))
            ->addNode(new Node('child', 'Child', 'root'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('&nbsp;&nbsp;Child', $output);
    }

    public function testNodeWithUrl(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: ['url' => 'http://example.com']))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('href="http://example.com"', $output);
        $this->assertStringContainsString('Link', $output);
    }

    public function testNodeWithCssClass(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: ['class' => 'highlight']))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('class="highlight"', $output);
    }

    public function testExpandedShowsMinus(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree, ['toggleUrl' => '/page']);
        $this->assertStringContainsString('-</a>]', $output);
    }

    public function testCollapsedShowsPlus(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: false))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree, ['toggleUrl' => '/page']);
        $this->assertStringContainsString('+</a>]', $output);
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testToggleLinkContainsTreeName(): void
    {
        $tree = (new TreeBuilder('mytree'))
            ->addNode(new Node('p', 'Parent'))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree, ['toggleUrl' => '/test.php']);
        $this->assertStringContainsString('ht_toggle_mytree', $output);
    }

    public function testStaticRenderingNoToggle(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent'))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree, ['static' => true]);
        $this->assertStringNotContainsString('[', $output);
        $this->assertStringNotContainsString(']', $output);
    }

    public function testLeafNodeNoToggle(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('leaf', 'Leaf'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree, ['toggleUrl' => '/page']);
        $this->assertStringNotContainsString('[', $output);
    }

    public function testExtraColumnsLeft(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree, [
            'extraLeft' => ['n' => ['[icon]']],
        ]);
        $this->assertStringContainsString('[icon]', $output);
    }

    public function testExtraColumnsRight(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();

        $renderer = new SimplehtmlRenderer();
        $output = $renderer->render($tree, [
            'extraRight' => ['n' => ['status']],
        ]);
        $this->assertStringContainsString('status', $output);
    }

    public function testToggleViaRequest(): void
    {
        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['ht_toggle_nav' => 'p']);
        $request->method('getParsedBody')->willReturn(null);

        $renderer = new SimplehtmlRenderer(null, $request);
        $output = $renderer->render($tree, ['toggleUrl' => '/page']);

        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testToggleWithStateStorage(): void
    {
        $store = [];
        $stateStorage = new CallbackStateStorage(
            fn($t, $id) => $store[$t][$id] ?? null,
            function ($t, $id, $expanded) use (&$store) {
                $store[$t][$id] = $expanded;
            },
        );

        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['ht_toggle_nav' => 'p']);
        $request->method('getParsedBody')->willReturn(null);

        $renderer = new SimplehtmlRenderer($stateStorage, $request);
        $renderer->render($tree, ['toggleUrl' => '/page']);

        $this->assertFalse($store['nav']['p']);
    }
}
