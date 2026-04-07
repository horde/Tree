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

use Error;
use Horde\Tree\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Node value object.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(Node::class)]
class NodeTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $node = new Node('id1', 'Label');
        $this->assertSame('id1', $node->id);
        $this->assertSame('Label', $node->label);
        $this->assertNull($node->parentId);
        $this->assertTrue($node->expanded);
        $this->assertSame([], $node->params);
    }

    public function testConstructorWithAllParams(): void
    {
        $node = new Node('child', 'Child Node', 'parent', false, ['icon' => '/a.png']);
        $this->assertSame('child', $node->id);
        $this->assertSame('Child Node', $node->label);
        $this->assertSame('parent', $node->parentId);
        $this->assertFalse($node->expanded);
        $this->assertSame(['icon' => '/a.png'], $node->params);
    }

    public function testReadonlyId(): void
    {
        $node = new Node('id1', 'Label');
        $this->expectException(Error::class);
        $node->id = 'other'; // @phpstan-ignore-line
    }

    public function testReadonlyLabel(): void
    {
        $node = new Node('id1', 'Label');
        $this->expectException(Error::class);
        $node->label = 'other'; // @phpstan-ignore-line
    }

    public function testWithParams(): void
    {
        $node = new Node('id1', 'Label', params: ['icon' => '/a.png']);
        $updated = $node->withParams(['url' => '/page', 'icon' => '/b.png']);

        // Original unchanged
        $this->assertSame(['icon' => '/a.png'], $node->params);
        // New instance has merged params
        $this->assertSame('/b.png', $updated->params['icon']);
        $this->assertSame('/page', $updated->params['url']);
        // Identity preserved
        $this->assertSame('id1', $updated->id);
        $this->assertSame('Label', $updated->label);
    }

    public function testWithExpanded(): void
    {
        $node = new Node('id1', 'Label', expanded: true);
        $collapsed = $node->withExpanded(false);

        $this->assertTrue($node->expanded);
        $this->assertFalse($collapsed->expanded);
        $this->assertSame('id1', $collapsed->id);
    }

    public function testWithParamsReturnsNewInstance(): void
    {
        $node = new Node('id1', 'Label');
        $updated = $node->withParams(['key' => 'val']);
        $this->assertNotSame($node, $updated);
    }

    public function testWithExpandedReturnsNewInstance(): void
    {
        $node = new Node('id1', 'Label');
        $toggled = $node->withExpanded(false);
        $this->assertNotSame($node, $toggled);
    }
}
