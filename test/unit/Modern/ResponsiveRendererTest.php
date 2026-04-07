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
use Horde\Tree\RenderedNode;
use Horde\Tree\Renderer\ResponsiveRenderer;
use Horde\Tree\State\CallbackStateStorage;
use Horde\Tree\TreeBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tests for the modern ResponsiveRenderer.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(ResponsiveRenderer::class)]
class ResponsiveRendererTest extends TestCase
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

    // ─── Basic rendering ───

    public function testEmptyTree(): void
    {
        $tree = (new TreeBuilder('empty'))->build();
        $renderer = new ResponsiveRenderer();
        $this->assertSame('', $renderer->render($tree));
    }

    public function testSingleRootNode(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Hello'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('Hello', $output);
        $this->assertStringContainsString('<nav', $output);
        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<li', $output);
    }

    public function testNestedNodes(): void
    {
        $output = (new ResponsiveRenderer())->render($this->buildTree());
        $this->assertStringContainsString('Root', $output);
        $this->assertStringContainsString('Child 1', $output);
        $this->assertStringContainsString('Child 2', $output);
        $this->assertStringContainsString('Grandchild', $output);
    }

    public function testDeeplyNestedTree(): void
    {
        $tree = (new TreeBuilder('deep'))
            ->addNode(new Node('l1', 'Level 1'))
            ->addNode(new Node('l2', 'Level 2', 'l1'))
            ->addNode(new Node('l3', 'Level 3', 'l2'))
            ->addNode(new Node('l4', 'Level 4', 'l3'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('Level 4', $output);
    }

    // ─── Semantic HTML5 structure ───

    public function testOutputContainsNavElement(): void
    {
        $output = (new ResponsiveRenderer())->render($this->buildTree());
        $this->assertStringContainsString('<nav class="horde-tree"', $output);
    }

    public function testOutputContainsUnorderedList(): void
    {
        $output = (new ResponsiveRenderer())->render($this->buildTree());
        $this->assertStringContainsString('<ul class="horde-tree__list"', $output);
    }

    public function testLeafNodeUsesListItem(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('leaf', 'Leaf'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('<li class="horde-tree__item"', $output);
        $this->assertStringContainsString('role="treeitem"', $output);
        // Leaf node should not have details/summary
        $this->assertStringNotContainsString('<details', $output);
        $this->assertStringNotContainsString('<summary', $output);
    }

    public function testBranchNodeUsesDetailsElement(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent'))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('<details class="horde-tree__toggle"', $output);
    }

    public function testBranchNodeUsesSummaryElement(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent'))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('<summary class="horde-tree__summary">', $output);
    }

    public function testChildrenInsideNestedUl(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent'))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        // There should be at least two <ul> elements (root + nested)
        $this->assertGreaterThanOrEqual(2, substr_count($output, '<ul class="horde-tree__list"'));
        // Nested ul has group role
        $this->assertGreaterThanOrEqual(2, substr_count($output, 'role="group"'));
    }

    // ─── ARIA attributes ───

    public function testRootHasTreeRole(): void
    {
        $output = (new ResponsiveRenderer())->render($this->buildTree());
        $this->assertStringContainsString('role="tree"', $output);
    }

    public function testListHasGroupRole(): void
    {
        $output = (new ResponsiveRenderer())->render($this->buildTree());
        $this->assertStringContainsString('role="group"', $output);
    }

    public function testItemHasTreeitemRole(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('role="treeitem"', $output);
    }

    public function testExpandedNodeHasAriaExpanded(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('aria-expanded="true"', $output);
    }

    public function testCollapsedNodeHasAriaExpandedFalse(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: false))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('aria-expanded="false"', $output);
    }

    public function testAriaLabelDefault(): void
    {
        $tree = (new TreeBuilder('mynavigation'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('aria-label="mynavigation"', $output);
    }

    public function testAriaLabelOption(): void
    {
        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, ['ariaLabel' => 'Site Navigation']);
        $this->assertStringContainsString('aria-label="Site Navigation"', $output);
    }

    // ─── Data attributes ───

    public function testNodeHasDataNodeId(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('inbox', 'Inbox'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('data-node-id="inbox"', $output);
    }

    // ─── Expand/collapse ───

    public function testCollapsedNodeHidesChildren(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: false))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testExpandedNodeShowsChildren(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringContainsString('Child', $output);
    }

    public function testCollapsedNodeNoOpenAttribute(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: false))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringNotContainsString(' open', $output);
    }

    public function testExpandedNodeHasOpenAttribute(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('<details class="horde-tree__toggle" open>', $output);
    }

    // ─── Node params ───

    public function testNodeWithUrl(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: ['url' => '/page']))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('href="/page"', $output);
        $this->assertStringContainsString('<a class="horde-tree__label"', $output);
    }

    public function testNodeWithoutUrl(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Plain'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('<span class="horde-tree__label">Plain</span>', $output);
        $this->assertStringNotContainsString('<a ', $output);
    }

    public function testNodeWithIcon(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: ['icon' => '/icon.png']))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('<img class="horde-tree__icon"', $output);
        $this->assertStringContainsString('src="/icon.png"', $output);
    }

    public function testNodeWithEmptyIconOmitsIcon(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: ['icon' => '']))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringNotContainsString('<img', $output);
    }

    public function testNodeWithIconOpen(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Folder', expanded: true, params: [
                'icon' => '/closed.png',
                'iconopen' => '/open.png',
            ]))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('src="/open.png"', $output);
        $this->assertStringNotContainsString('src="/closed.png"', $output);
    }

    public function testNodeWithIconAlt(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: [
                'icon' => '/icon.png',
                'iconalt' => 'Folder icon',
            ]))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('alt="Folder icon"', $output);
    }

    public function testNodeWithUrlClass(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: [
                'url' => '/page',
                'urlclass' => 'nav-link',
            ]))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('class="horde-tree__label nav-link"', $output);
    }

    public function testNodeWithTarget(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: [
                'url' => '/page',
                'target' => '_blank',
            ]))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('target="_blank"', $output);
    }

    public function testGlobalTargetOption(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: ['url' => '/page']))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, ['target' => '_top']);
        $this->assertStringContainsString('target="_top"', $output);
    }

    public function testNodeTargetOverridesGlobal(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: [
                'url' => '/page',
                'target' => '_self',
            ]))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, ['target' => '_top']);
        $this->assertStringContainsString('target="_self"', $output);
        $this->assertStringNotContainsString('target="_top"', $output);
    }

    public function testNodeWithCssClass(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node', params: ['class' => 'highlight']))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('horde-tree__item highlight', $output);
    }

    public function testNodeWithTitle(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: [
                'url' => '/page',
                'title' => 'Go to page',
            ]))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('title="Go to page"', $output);
    }

    // ─── Static mode ───

    public function testStaticModeNoDetailsElement(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent'))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, ['static' => true]);
        $this->assertStringNotContainsString('<details', $output);
        $this->assertStringNotContainsString('<summary', $output);
    }

    public function testStaticModeAllExpanded(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('p', 'Parent', expanded: false))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, ['static' => true]);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringContainsString('Child', $output);
    }

    // ─── Headers ───

    public function testHeaders(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, [
            'headers' => [
                ['class' => 'col-name', 'html' => 'Name'],
                ['class' => 'col-size', 'html' => 'Size'],
            ],
        ]);
        $this->assertStringContainsString('horde-tree__header', $output);
        $this->assertStringContainsString('horde-tree__header-cell col-name', $output);
        $this->assertStringContainsString('>Name<', $output);
        $this->assertStringContainsString('>Size<', $output);
    }

    public function testHideHeaders(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, [
            'headers' => [['html' => 'Name']],
            'hideHeaders' => true,
        ]);
        $this->assertStringNotContainsString('horde-tree__header', $output);
    }

    public function testEmptyHeadersNoHeaderRow(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, ['headers' => []]);
        $this->assertStringNotContainsString('horde-tree__header', $output);
    }

    // ─── Extra columns ───

    public function testExtraColumnsLeft(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, [
            'extraLeft' => ['n' => ['[icon]']],
        ]);
        $this->assertStringContainsString('horde-tree__extra-left', $output);
        $this->assertStringContainsString('[icon]', $output);
    }

    public function testExtraColumnsRight(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, [
            'extraRight' => ['n' => ['Active', 'Edit']],
        ]);
        $this->assertSame(2, substr_count($output, 'horde-tree__extra-right'));
        $this->assertStringContainsString('Active', $output);
        $this->assertStringContainsString('Edit', $output);
    }

    // ─── Alternate rows ───

    public function testAlternateAddsModifierClass(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree, ['alternate' => true]);
        $this->assertStringContainsString('horde-tree--alternate', $output);
    }

    public function testNoAlternateClassByDefault(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringNotContainsString('horde-tree--alternate', $output);
    }

    // ─── Toggle via PSR-7 request ───

    public function testToggleViaQueryParam(): void
    {
        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['ht_toggle_nav' => 'p']);
        $request->method('getParsedBody')->willReturn(null);

        $output = (new ResponsiveRenderer(null, $request))->render($tree);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testToggleViaPostBody(): void
    {
        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $request->method('getParsedBody')->willReturn(['ht_toggle_nav' => 'p']);

        $output = (new ResponsiveRenderer(null, $request))->render($tree);
        $this->assertStringNotContainsString('Child', $output);
    }

    // ─── State storage ───

    public function testToggleWithStateStorage(): void
    {
        $store = [];
        $stateStorage = new CallbackStateStorage(
            function (string $tree, string $id) use (&$store) {
                return $store[$tree][$id] ?? null;
            },
            function (string $tree, string $id, bool $expanded) use (&$store) {
                $store[$tree][$id] = $expanded;
            },
        );

        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['ht_toggle_nav' => 'p']);
        $request->method('getParsedBody')->willReturn(null);

        (new ResponsiveRenderer($stateStorage, $request))->render($tree);
        $this->assertFalse($store['nav']['p']);
    }

    public function testStoredStateOverridesDefault(): void
    {
        $store = ['nav' => ['p' => false]];
        $stateStorage = new CallbackStateStorage(
            function (string $tree, string $id) use (&$store) {
                return $store[$tree][$id] ?? null;
            },
            function (string $tree, string $id, bool $expanded) use (&$store) {
                $store[$tree][$id] = $expanded;
            },
        );

        $tree = (new TreeBuilder('nav'))
            ->addNode(new Node('p', 'Parent', expanded: true))
            ->addNode(new Node('c', 'Child', 'p'))
            ->build();

        $output = (new ResponsiveRenderer($stateStorage))->render($tree);
        $this->assertStringNotContainsString('Child', $output);
    }

    // ─── buildStructured() ───

    public function testBuildStructuredReturnsRenderedNodes(): void
    {
        $tree = $this->buildTree();
        $result = (new ResponsiveRenderer())->buildStructured($tree);
        $this->assertContainsOnlyInstancesOf(RenderedNode::class, $result);
    }

    public function testBuildStructuredRootCount(): void
    {
        $tree = $this->buildTree();
        $result = (new ResponsiveRenderer())->buildStructured($tree);
        $this->assertCount(1, $result);
        $this->assertSame('root', $result[0]->nodeId);
    }

    public function testBuildStructuredChildCount(): void
    {
        $tree = $this->buildTree();
        $result = (new ResponsiveRenderer())->buildStructured($tree);
        $this->assertCount(2, $result[0]->children);
        $this->assertSame('child1', $result[0]->children[0]->nodeId);
        $this->assertSame('child2', $result[0]->children[1]->nodeId);
    }

    // ─── HTML escaping ───

    public function testNodeIdEscapedInDataAttribute(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('a&b<c', 'Node'))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('data-node-id="a&amp;b&lt;c"', $output);
    }

    public function testUrlEscaped(): void
    {
        $tree = (new TreeBuilder('test'))
            ->addNode(new Node('n', 'Link', params: ['url' => '/page?a=1&b=2']))
            ->build();
        $output = (new ResponsiveRenderer())->render($tree);
        $this->assertStringContainsString('href="/page?a=1&amp;b=2"', $output);
    }
}
