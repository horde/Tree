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
use Horde\Tree\Renderer\HtmlRenderer;
use Horde\Tree\State\NullStateStorage;
use Horde\Tree\TreeBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tests for the modern HtmlRenderer.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(HtmlRenderer::class)]
class HtmlRendererTest extends TestCase
{
    private function buildTree(): \Horde\Tree\Tree
    {
        return (new TreeBuilder('nav'))
            ->addNode(new Node('root', 'Root'))
            ->addNode(new Node('child1', 'Child 1', 'root'))
            ->addNode(new Node('child2', 'Child 2', 'root'))
            ->addNode(new Node('grandchild', 'Grandchild', 'child1'))
            ->build();
    }

    public function testEmptyTree(): void
    {
        $tree = (new TreeBuilder('empty'))->build();
        $renderer = new HtmlRenderer();
        $this->assertSame('', $renderer->render($tree));
    }

    public function testSingleRootNode(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('root', 'Root Node'))
            ->build();
        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('Root Node', $output);
        $this->assertStringContainsString('horde-tree-row', $output);
    }

    public function testNestedNodes(): void
    {
        $renderer = new HtmlRenderer();
        $output = $renderer->render($this->buildTree());
        $this->assertStringContainsString('Root', $output);
        $this->assertStringContainsString('Child 1', $output);
        $this->assertStringContainsString('Grandchild', $output);
    }

    public function testCollapsedNodeHidesChildren(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: false))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testNodeWithUrl(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: ['url' => 'http://example.com']))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('href="http://example.com"', $output);
        $this->assertStringContainsString('Link', $output);
    }

    public function testNodeWithIcon(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: ['icon' => '/icon.png']))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('/icon.png', $output);
        $this->assertStringContainsString('horde-tree-icon', $output);
    }

    public function testNodeWithEmptyIconOmitsIcon(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: ['icon' => '']))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringNotContainsString('horde-tree-icon', $output);
    }

    public function testNodeWithIconOpen(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Folder', expanded: true, params: [
                'icon' => '/closed.png',
                'iconopen' => '/open.png',
            ]))
            ->addNode(new Node('c', 'File', 'p'))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('/open.png', $output);
    }

    public function testNodeWithCssClass(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: ['class' => 'highlight']))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('highlight', $output);
    }

    public function testNodeWithUrlTarget(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: [
                'url' => '/page',
                'target' => '_blank',
            ]))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('target="_blank"', $output);
    }

    public function testGlobalTargetOption(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: ['url' => '/page']))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree, ['target' => '_top']);
        $this->assertStringContainsString('target="_top"', $output);
    }

    public function testNodeWithUrlClass(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: [
                'url' => '/page',
                'urlclass' => 'nav-link',
            ]))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('class="nav-link"', $output);
    }

    public function testNodeWithIconAlt(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: [
                'icon' => '/icon.png',
                'iconalt' => 'Folder icon',
            ]))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('alt="Folder icon"', $output);
    }

    public function testAlternateRowShading(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('a', 'A'))
            ->addNode(new Node('b', 'B'))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree, ['alternate' => true]);
        $this->assertStringContainsString('item0', $output);
        $this->assertStringContainsString('item1', $output);
    }

    public function testHeaders(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree, [
            'headers' => [
                ['class' => 'col-name', 'html' => 'Name'],
                ['class' => 'col-status', 'html' => 'Status'],
            ],
        ]);
        $this->assertStringContainsString('horde-tree-row-header', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Status', $output);
    }

    public function testHideHeaders(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree, [
            'headers' => [['html' => 'Name']],
            'hideHeaders' => true,
        ]);
        $this->assertStringNotContainsString('horde-tree-row-header', $output);
    }

    public function testExtraColumnsLeft(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();

        $renderer = new HtmlRenderer();
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

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree, [
            'extraRight' => ['n' => ['Status', 'Action']],
        ]);
        $this->assertStringContainsString('Status', $output);
        $this->assertStringContainsString('Action', $output);
    }

    public function testBuildStructuredReturnsRenderedNodes(): void
    {
        $tree = $this->buildTree();
        $renderer = new HtmlRenderer();
        $structured = $renderer->buildStructured($tree);

        $this->assertCount(1, $structured); // 1 root
        $root = $structured[0];
        $this->assertSame('root', $root->nodeId);
        $this->assertCount(2, $root->children); // child1, child2
        $this->assertSame('child1', $root->children[0]->nodeId);
        $this->assertCount(1, $root->children[0]->children); // grandchild
    }

    public function testToggleViaRequest(): void
    {
        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        // Mock a request with toggle parameter
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['ht_toggle_nav' => 'p']);
        $request->method('getParsedBody')->willReturn(null);

        $renderer = new HtmlRenderer(null, $request);
        $output = $renderer->render($tree);

        // Toggle should collapse the parent, hiding children
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testToggleWithStateStorage(): void
    {
        $store = [];
        $stateStorage = new \Horde\Tree\State\CallbackStateStorage(
            fn($tree, $id) => $store[$tree][$id] ?? null,
            function ($tree, $id, $expanded) use (&$store) {
                $store[$tree][$id] = $expanded;
            },
        );

        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        // Toggle the parent via request
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['ht_toggle_nav' => 'p']);
        $request->method('getParsedBody')->willReturn(null);

        $renderer = new HtmlRenderer($stateStorage, $request);
        $renderer->render($tree);

        // State should be persisted
        $this->assertFalse($store['nav']['p']);
    }

    public function testDeeplyNestedTree(): void
    {
        $builder = new TreeBuilder('deep');
        $builder->addNode(new Node('l0', 'Level 0'));
        $builder->addNode(new Node('l1', 'Level 1', 'l0'));
        $builder->addNode(new Node('l2', 'Level 2', 'l1'));
        $builder->addNode(new Node('l3', 'Level 3', 'l2'));
        $tree = $builder->build();

        $renderer = new HtmlRenderer();
        $output = $renderer->render($tree);
        $this->assertStringContainsString('Level 0', $output);
        $this->assertStringContainsString('Level 3', $output);
    }
}
