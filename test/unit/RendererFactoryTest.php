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

use BadFunctionCallException;
use Horde_Tree;
use Horde_Tree_Exception;
use Horde_Tree_Renderer;
use Horde_Tree_Renderer_Html;
use Horde_Tree_Renderer_Select;
use Horde_Tree_Renderer_Simplehtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Horde_Tree_Renderer factory.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Horde_Tree_Renderer::class)]
class RendererFactoryTest extends TestCase
{
    public function testFactoryCreatesHtmlRenderer(): void
    {
        $renderer = Horde_Tree_Renderer::factory('Html', ['name' => 'test']);
        $this->assertInstanceOf(Horde_Tree_Renderer_Html::class, $renderer);
    }

    public function testFactoryCreatesSelectRenderer(): void
    {
        $renderer = Horde_Tree_Renderer::factory('Select', ['name' => 'test']);
        $this->assertInstanceOf(Horde_Tree_Renderer_Select::class, $renderer);
    }

    public function testFactoryCreatesSimplehtmlRenderer(): void
    {
        $renderer = Horde_Tree_Renderer::factory('Simplehtml', ['name' => 'test']);
        $this->assertInstanceOf(Horde_Tree_Renderer_Simplehtml::class, $renderer);
    }

    public function testFactoryWithExistingTree(): void
    {
        $tree = new Horde_Tree('existing');
        $tree->addNode(['id' => 'n1', 'parent' => null, 'label' => 'Node 1']);

        $renderer = Horde_Tree_Renderer::factory('Select', ['tree' => $tree]);
        $this->assertInstanceOf(Horde_Tree_Renderer_Select::class, $renderer);

        // The renderer should use the provided tree (which has 1 node)
        $renderer->addNode(['id' => 'n2', 'parent' => null, 'label' => 'Node 2']);
        $output = $renderer->getTree();
        $this->assertStringContainsString('Node 1', $output);
        $this->assertStringContainsString('Node 2', $output);
    }

    public function testFactoryCreatesTreeFromName(): void
    {
        $renderer = Horde_Tree_Renderer::factory('Select', ['name' => 'auto-created']);
        $this->assertInstanceOf(Horde_Tree_Renderer_Select::class, $renderer);
    }

    public function testFactoryThrowsOnInvalidRenderer(): void
    {
        $this->expectException(Horde_Tree_Exception::class);
        Horde_Tree_Renderer::factory('NonExistentRenderer', ['name' => 'test']);
    }

    public function testFactoryThrowsWithoutTreeOrName(): void
    {
        $this->expectException(BadFunctionCallException::class);
        Horde_Tree_Renderer::factory('Html');
    }

    public function testFactoryAcceptsFullClassName(): void
    {
        $renderer = Horde_Tree_Renderer::factory(
            Horde_Tree_Renderer_Select::class,
            ['name' => 'test']
        );
        $this->assertInstanceOf(Horde_Tree_Renderer_Select::class, $renderer);
    }
}
