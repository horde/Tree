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

namespace Horde\Tree\Renderer;

use Horde\Tree\Tree;

/**
 * Interface for tree renderers.
 *
 * Each renderer converts a Tree into a specific output format (HTML, select
 * options, etc.).
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
interface RendererInterface
{
    /**
     * Render a tree to a string representation.
     *
     * @param array<string, mixed> $options Renderer-specific options
     */
    public function render(Tree $tree, array $options = []): string;
}
