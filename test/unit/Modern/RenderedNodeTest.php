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

use Horde\Tree\RenderedNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Error;

/**
 * Tests for the RenderedNode value object.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
#[CoversClass(RenderedNode::class)]
class RenderedNodeTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $node = new RenderedNode('id1', '<span>Label</span>', 0);
        $this->assertSame('id1', $node->nodeId);
        $this->assertSame('<span>Label</span>', $node->content);
        $this->assertSame(0, $node->indent);
        $this->assertSame('', $node->cssClass);
        $this->assertSame([], $node->children);
        $this->assertSame([], $node->extraLeft);
        $this->assertSame([], $node->extraRight);
    }

    public function testConstructorWithAllParams(): void
    {
        $child = new RenderedNode('child', 'Child', 1);
        $node = new RenderedNode(
            'root',
            'Root Content',
            0,
            'custom-class',
            [$child],
            ['[icon]'],
            ['status'],
        );

        $this->assertSame('root', $node->nodeId);
        $this->assertSame('custom-class', $node->cssClass);
        $this->assertCount(1, $node->children);
        $this->assertSame('child', $node->children[0]->nodeId);
        $this->assertSame(['[icon]'], $node->extraLeft);
        $this->assertSame(['status'], $node->extraRight);
    }

    public function testReadonlyProperties(): void
    {
        $node = new RenderedNode('id', 'content', 0);
        $this->expectException(Error::class);
        $node->nodeId = 'other'; // @phpstan-ignore-line
    }
}
