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
use Horde_Tree_Renderer;
use Horde_Tree_Renderer_Html;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Tree_Renderer_Html renderer.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Horde_Tree_Renderer_Html::class)]
class HtmlRendererTest extends TestCase
{
    private function createRenderer(array $params = []): Horde_Tree_Renderer_Html
    {
        $tree = new Horde_Tree('test');
        return new Horde_Tree_Renderer_Html($tree, $params);
    }

    public function testEmptyTreeRendersMinimal(): void
    {
        $renderer = $this->createRenderer();
        $output = $renderer->getTree();
        // Empty tree should produce only the header (empty) - essentially empty string
        $this->assertSame('', $output);
    }

    public function testSingleRootNode(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root Node']);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('Root Node', $output);
        $this->assertStringContainsString('horde-tree-row', $output);
    }

    public function testNestedNodes(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'parent', 'parent' => null, 'label' => 'Parent']);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringContainsString('Child', $output);
    }

    public function testNodeWithUrl(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Linked Node',
            'params' => ['url' => 'http://example.com/page'],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('href="http://example.com/page"', $output);
        $this->assertStringContainsString('Linked Node', $output);
    }

    public function testNodeWithIcon(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Icon Node',
            'params' => ['icon' => '/images/folder.png'],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('/images/folder.png', $output);
        $this->assertStringContainsString('horde-tree-icon', $output);
    }

    public function testNodeWithEmptyIconRendersNoIcon(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'No Icon',
            'params' => ['icon' => ''],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringNotContainsString('horde-tree-icon', $output);
    }

    public function testNodeWithCssClass(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Styled',
            'params' => ['class' => 'my-custom-class'],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('my-custom-class', $output);
    }

    public function testExpandedNodeShowsChildren(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'parent',
            'parent' => null,
            'label' => 'Parent',
            'expanded' => true,
        ]);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('Child', $output);
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
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'Child']);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('Parent', $output);
        $this->assertStringNotContainsString('Child', $output);
    }

    public function testStaticRenderingNoToggleLinks(): void
    {
        // Setup $_SERVER for Horde_Url
        $_SERVER['PHP_SELF'] = '/test.php';

        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'root', 'parent' => null, 'label' => 'Root']);
        $renderer->addNode(['id' => 'child', 'parent' => 'root', 'label' => 'Child']);

        $staticOutput = $renderer->getTree(true);
        // Static mode should not contain toggle links (<a> tags for expand/collapse)
        $this->assertStringNotContainsString('ht_toggle_', $staticOutput);
    }

    public function testAlternateRowShading(): void
    {
        $renderer = $this->createRenderer(['alternate' => true]);
        $renderer->addNode(['id' => 'a', 'parent' => null, 'label' => 'A']);
        $renderer->addNode(['id' => 'b', 'parent' => null, 'label' => 'B']);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('item0', $output);
        $this->assertStringContainsString('item1', $output);
    }

    public function testHeaderRendering(): void
    {
        $renderer = $this->createRenderer();
        $renderer->setHeader([
            ['class' => 'col-name', 'html' => 'Name'],
            ['class' => 'col-status', 'html' => 'Status'],
        ]);
        $renderer->addNode(['id' => 'n1', 'parent' => null, 'label' => 'Node']);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('horde-tree-row-header', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Status', $output);
    }

    public function testHideHeaders(): void
    {
        $renderer = $this->createRenderer(['hideHeaders' => true]);
        $renderer->setHeader([
            ['class' => 'col-name', 'html' => 'Name'],
        ]);
        $renderer->addNode(['id' => 'n1', 'parent' => null, 'label' => 'Node']);

        $output = $renderer->getTree(true);
        $this->assertStringNotContainsString('horde-tree-row-header', $output);
    }

    public function testExtraColumnsRight(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Node',
            'right' => ['Status OK', 'Active'],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('Status OK', $output);
        $this->assertStringContainsString('Active', $output);
    }

    public function testExtraColumnsLeft(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Node',
            'left' => ['[*]'],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('[*]', $output);
    }

    public function testRenderTreeEchoes(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'n1', 'parent' => null, 'label' => 'Echo Test']);

        ob_start();
        $renderer->renderTree(true);
        $output = ob_get_clean();

        $this->assertStringContainsString('Echo Test', $output);
    }

    public function testNodeWithUrlTarget(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'External',
            'params' => [
                'url' => 'http://example.com',
                'target' => '_blank',
            ],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('target="_blank"', $output);
    }

    public function testNodeWithUrlClass(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Styled Link',
            'params' => [
                'url' => '/page',
                'urlclass' => 'nav-link',
            ],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('class="nav-link"', $output);
    }

    public function testNodeWithIconAlt(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Accessible',
            'params' => [
                'icon' => '/icon.png',
                'iconalt' => 'Folder icon',
            ],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('alt="Folder icon"', $output);
    }

    public function testNodeWithIconOpenExpanded(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode([
            'id' => 'parent',
            'parent' => null,
            'label' => 'Folder',
            'expanded' => true,
            'params' => [
                'icon' => '/closed.png',
                'iconopen' => '/open.png',
            ],
        ]);
        $renderer->addNode(['id' => 'child', 'parent' => 'parent', 'label' => 'File']);

        $output = $renderer->getTree(true);
        // When expanded and iconopen is set, it should use iconopen
        $this->assertStringContainsString('/open.png', $output);
    }

    public function testDeeplyNestedTree(): void
    {
        $renderer = $this->createRenderer();
        $renderer->addNode(['id' => 'l0', 'parent' => null, 'label' => 'Level 0']);
        $renderer->addNode(['id' => 'l1', 'parent' => 'l0', 'label' => 'Level 1']);
        $renderer->addNode(['id' => 'l2', 'parent' => 'l1', 'label' => 'Level 2']);
        $renderer->addNode(['id' => 'l3', 'parent' => 'l2', 'label' => 'Level 3']);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('Level 0', $output);
        $this->assertStringContainsString('Level 3', $output);
    }

    public function testHeaderWithAlternateShading(): void
    {
        $renderer = $this->createRenderer(['alternate' => true]);
        $renderer->setHeader([['html' => 'Header']]);
        $renderer->addNode(['id' => 'n1', 'parent' => null, 'label' => 'Node']);

        $output = $renderer->getTree(true);
        // Header row should also get alternating class
        $this->assertStringContainsString('horde-tree-row-header', $output);
    }

    public function testGlobalTargetOption(): void
    {
        $renderer = $this->createRenderer(['target' => '_top']);
        $renderer->addNode([
            'id' => 'n1',
            'parent' => null,
            'label' => 'Link',
            'params' => ['url' => '/page'],
        ]);

        $output = $renderer->getTree(true);
        $this->assertStringContainsString('target="_top"', $output);
    }
}
