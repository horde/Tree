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
 * Renders a tree as HTML <option> elements for use inside a <select>.
 *
 * Node indentation is represented by non-breaking spaces.
 * Always renders statically (no expand/collapse interaction).
 *
 * Node params:
 * - selected: (bool) Mark this option as selected
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class SelectRenderer implements RendererInterface
{
    public function render(Tree $tree, array $options = []): string
    {
        $html = '';
        foreach ($tree->getRootNodeIds() as $rootId) {
            $html .= $this->buildNode($tree, $rootId);
        }
        return $html;
    }

    private function buildNode(Tree $tree, string $nodeId): string
    {
        $node = $tree->getNode($nodeId);
        $indent = $tree->getIndentLevel($nodeId);
        $selected = !empty($node->params['selected']) ? ' selected="selected"' : '';

        $output = '<option value="' . htmlspecialchars($nodeId, ENT_QUOTES) . '"'
            . $selected . '>'
            . str_repeat('&nbsp;', $indent * 2)
            . htmlspecialchars($node->label)
            . '</option>';

        if ($node->expanded) {
            foreach ($tree->getChildIds($nodeId) as $childId) {
                $output .= $this->buildNode($tree, $childId);
            }
        }

        return $output;
    }
}
