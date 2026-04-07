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

use Horde\Tree\Node;
use Horde\Tree\RenderedNode;
use Horde\Tree\State\NullStateStorage;
use Horde\Tree\State\StateStorageInterface;
use Horde\Tree\Tree;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTML tree renderer with graphical expand/collapse indicators.
 *
 * Produces div-based HTML output with CSS classes for styling.
 * Supports expand/collapse via toggle links that submit the toggled node ID
 * as a query parameter.
 *
 * Options:
 * - static:      (bool) Render without toggle links. Default: false.
 * - lines:       (bool) Show tree connector lines. Default: true.
 * - lines_base:  (bool) Show lines at root level. Default: false.
 * - alternate:   (bool) Alternate row shading. Default: false.
 * - class:       (string) CSS class for the tree container. Default: ''.
 * - hideHeaders: (bool) Suppress header rendering. Default: false.
 * - multiline:   (bool) Node labels may contain line breaks. Default: false.
 * - target:      (string) Default link target for all nodes. Default: ''.
 *
 * Node params:
 * - class:    (string) CSS class for the node row
 * - icon:     (string) Icon URL (empty string = no icon)
 * - iconalt:  (string) Alt text for icon
 * - iconopen: (string) Icon URL for expanded state
 * - url:      (string) Link URL for the node label
 * - urlclass: (string) CSS class for the label link
 * - target:   (string) Link target attribute
 * - title:    (string) Link tooltip
 *
 * Extra columns:
 * - Left and right extra columns can be provided per node.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class HtmlRenderer implements RendererInterface
{
    private StateStorageInterface $stateStorage;
    private ?ServerRequestInterface $request;

    public function __construct(
        ?StateStorageInterface $stateStorage = null,
        ?ServerRequestInterface $request = null,
    ) {
        $this->stateStorage = $stateStorage ?? new NullStateStorage();
        $this->request = $request;
    }

    /**
     * @param array<string, mixed> $options Renderer options (see class docblock)
     */
    public function render(Tree $tree, array $options = []): string
    {
        $opts = array_merge([
            'static' => false,
            'lines' => true,
            'lines_base' => false,
            'alternate' => false,
            'class' => '',
            'hideHeaders' => false,
            'multiline' => false,
            'target' => '',
            'headers' => [],
            'extraLeft' => [],
            'extraRight' => [],
        ], $options);

        $toggledNodeId = $this->detectToggle($tree);
        $expandedStates = $this->resolveExpandedStates($tree, $toggledNodeId);

        $renderedNodes = [];
        foreach ($tree->getRootNodeIds() as $rootId) {
            $renderedNodes = array_merge(
                $renderedNodes,
                $this->buildNodes($tree, $rootId, $expandedStates, $opts),
            );
        }

        return $this->assemble($renderedNodes, $opts);
    }

    /**
     * Build structured RenderedNode objects from a tree.
     *
     * @return list<RenderedNode>
     */
    public function buildStructured(Tree $tree, array $options = []): array
    {
        $opts = array_merge([
            'static' => false,
            'extraLeft' => [],
            'extraRight' => [],
        ], $options);

        $toggledNodeId = $this->detectToggle($tree);
        $expandedStates = $this->resolveExpandedStates($tree, $toggledNodeId);

        $renderedNodes = [];
        foreach ($tree->getRootNodeIds() as $rootId) {
            $renderedNodes[] = $this->buildStructuredNode($tree, $rootId, $expandedStates, $opts);
        }
        return $renderedNodes;
    }

    private function detectToggle(Tree $tree): ?string
    {
        if ($this->request === null) {
            return null;
        }

        $toggleKey = 'ht_toggle_' . $tree->name;
        $queryParams = $this->request->getQueryParams();
        if (isset($queryParams[$toggleKey])) {
            return (string) $queryParams[$toggleKey];
        }

        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody[$toggleKey])) {
            return (string) $parsedBody[$toggleKey];
        }

        return null;
    }

    /**
     * Resolve the effective expanded state for each node.
     *
     * @return array<string, bool>
     */
    private function resolveExpandedStates(Tree $tree, ?string $toggledNodeId): array
    {
        $states = [];
        foreach ($tree->getNodes() as $id => $node) {
            $storedState = $this->stateStorage->isExpanded($tree->name, $id);

            if ($id === $toggledNodeId) {
                // Toggle: invert stored state, or invert node default
                $currentState = $storedState ?? $node->expanded;
                $states[$id] = !$currentState;
                $this->stateStorage->setExpanded($tree->name, $id, $states[$id]);
            } elseif ($storedState !== null) {
                $states[$id] = $storedState;
            } else {
                $states[$id] = $node->expanded;
            }
        }
        return $states;
    }

    /**
     * Recursively build flat list of RenderedNode objects for assembly.
     *
     * @return list<RenderedNode>
     */
    private function buildNodes(
        Tree $tree,
        string $nodeId,
        array $expandedStates,
        array $opts,
    ): array {
        $node = $tree->getNode($nodeId);
        $indent = $tree->getIndentLevel($nodeId);
        $isExpanded = $expandedStates[$nodeId] ?? $node->expanded;
        $hasChildren = count($tree->getChildIds($nodeId)) > 0;

        $content = $this->renderNodeContent($tree, $node, $indent, $isExpanded, $hasChildren, $opts);

        $extraLeft = $opts['extraLeft'][$nodeId] ?? [];
        $extraRight = $opts['extraRight'][$nodeId] ?? [];

        $result = [new RenderedNode(
            $nodeId,
            $content,
            $indent,
            $node->params['class'] ?? '',
            [],
            $extraLeft,
            $extraRight,
        )];

        if ($hasChildren && $isExpanded) {
            foreach ($tree->getChildIds($nodeId) as $childId) {
                $result = array_merge($result, $this->buildNodes($tree, $childId, $expandedStates, $opts));
            }
        }

        return $result;
    }

    /**
     * Recursively build a tree-shaped RenderedNode for structured access.
     */
    private function buildStructuredNode(
        Tree $tree,
        string $nodeId,
        array $expandedStates,
        array $opts,
    ): RenderedNode {
        $node = $tree->getNode($nodeId);
        $indent = $tree->getIndentLevel($nodeId);
        $isExpanded = $expandedStates[$nodeId] ?? $node->expanded;
        $hasChildren = count($tree->getChildIds($nodeId)) > 0;

        $content = $this->renderNodeContent($tree, $node, $indent, $isExpanded, $hasChildren, $opts);

        $children = [];
        if ($hasChildren && $isExpanded) {
            foreach ($tree->getChildIds($nodeId) as $childId) {
                $children[] = $this->buildStructuredNode($tree, $childId, $expandedStates, $opts);
            }
        }

        $extraLeft = $opts['extraLeft'][$nodeId] ?? [];
        $extraRight = $opts['extraRight'][$nodeId] ?? [];

        return new RenderedNode(
            $nodeId,
            $content,
            $indent,
            $node->params['class'] ?? '',
            $children,
            $extraLeft,
            $extraRight,
        );
    }

    private function renderNodeContent(
        Tree $tree,
        Node $node,
        int $indent,
        bool $isExpanded,
        bool $hasChildren,
        array $opts,
    ): string {
        $content = '';

        // Icon
        $icon = $this->resolveIcon($node, $isExpanded, $hasChildren);
        if ($icon !== '') {
            $alt = isset($node->params['iconalt'])
                ? ' alt="' . htmlspecialchars($node->params['iconalt'], ENT_QUOTES) . '"'
                : '';
            $content .= '<img src="' . htmlspecialchars($icon, ENT_QUOTES) . '" class="horde-tree-icon"' . $alt . ' />';
        }

        // Label
        $label = $node->label;
        $url = $node->params['url'] ?? '';
        if ($url !== '') {
            $target = $node->params['target'] ?? ($opts['target'] ?? '');
            $targetAttr = $target !== '' ? ' target="' . htmlspecialchars($target, ENT_QUOTES) . '"' : '';
            $urlclass = $node->params['urlclass'] ?? '';
            $classAttr = $urlclass !== '' ? ' class="' . htmlspecialchars($urlclass, ENT_QUOTES) . '"' : '';
            $content .= '<a' . $classAttr . ' href="' . htmlspecialchars((string) $url, ENT_QUOTES) . '"' . $targetAttr . '>' . $label . '</a>';
        } else {
            $content .= $label;
        }

        return $content;
    }

    private function resolveIcon(Node $node, bool $isExpanded, bool $hasChildren): string
    {
        if (array_key_exists('icon', $node->params)) {
            if ($node->params['icon'] === '') {
                return '';
            }
            if (isset($node->params['iconopen']) && $isExpanded) {
                return (string) $node->params['iconopen'];
            }
            return (string) $node->params['icon'];
        }
        return '';
    }

    /**
     * Assemble flat RenderedNode list into final HTML.
     *
     * @param list<RenderedNode> $nodes
     */
    private function assemble(array $nodes, array $opts): string
    {
        $html = '';
        $altCount = 0;

        // Headers
        if (!empty($opts['headers']) && !$opts['hideHeaders']) {
            $headerClass = 'horde-tree-row-header';
            if ($opts['alternate']) {
                $headerClass .= ' item' . $altCount;
                $altCount = 1 - $altCount;
            }
            $html .= '<div class="' . $headerClass . '">';
            foreach ($opts['headers'] as $header) {
                $html .= '<span';
                if (!empty($header['class'])) {
                    $html .= ' class="' . htmlspecialchars($header['class'], ENT_QUOTES) . '"';
                }
                $html .= '>' . ($header['html'] ?? '&nbsp;') . '</span>';
            }
            $html .= '</div>';
        }

        // Nodes
        foreach ($nodes as $rendered) {
            $rowClass = 'horde-tree-row';
            if ($rendered->cssClass !== '') {
                $rowClass .= ' ' . $rendered->cssClass;
            }
            if ($opts['alternate']) {
                $rowClass .= ' item' . $altCount;
                $altCount = 1 - $altCount;
            }

            $line = '<div class="' . $rowClass . '">';

            // Left extra columns
            foreach ($rendered->extraLeft as $extra) {
                $line .= '<span>' . $extra . '</span>';
            }

            // Main content
            $line .= '<span>' . $rendered->content . '</span>';

            // Right extra columns
            foreach ($rendered->extraRight as $extra) {
                $line .= '<span>' . $extra . '</span>';
            }

            $html .= $line . "</div>\n";
        }

        return $html;
    }
}
