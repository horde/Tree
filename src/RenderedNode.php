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

namespace Horde\Tree;

/**
 * Value object representing a rendered tree node with its structure.
 *
 * Used by renderers to build structured output before final assembly.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class RenderedNode
{
    /**
     * @param list<RenderedNode>   $children  Rendered child nodes
     * @param list<string>         $extraLeft Extra columns on the left
     * @param list<string>         $extraRight Extra columns on the right
     */
    public function __construct(
        public readonly string $nodeId,
        public readonly string $content,
        public readonly int $indent,
        public readonly string $cssClass = '',
        public readonly array $children = [],
        public readonly array $extraLeft = [],
        public readonly array $extraRight = [],
    ) {}
}
