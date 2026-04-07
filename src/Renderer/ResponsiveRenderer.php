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
 * Responsive HTML5 tree renderer using semantic markup.
 *
 * Produces accessible, mobile-friendly HTML using semantic elements:
 * {@code <nav>}, {@code <ul>}/{@code <li>} for structure and
 * {@code <details>}/{@code <summary>} for native expand/collapse.
 *
 * The renderer emits zero presentational markup. All visual styling
 * (indentation, icons, spacing, colors) is expected to be provided by
 * an external stylesheet targeting the BEM class names documented below.
 * See {@code doc/example/responsive-tree.css} for a reference stylesheet.
 *
 * ARIA tree roles ({@code role="tree"}, {@code role="treeitem"},
 * {@code role="group"}) and {@code aria-expanded} attributes are included
 * for screen reader support.
 *
 * Options:
 * - static:      (bool) Render all nodes expanded without details/summary.
 *                Default: false.
 * - hideHeaders: (bool) Suppress the header row. Default: false.
 * - alternate:   (bool) Add horde-tree--alternate class to root for CSS
 *                row striping. Default: false.
 * - target:      (string) Default link target for all nodes. Default: ''.
 * - ariaLabel:   (string) aria-label for the root nav. Default: tree name.
 * - headers:     (array) Header definitions [['class' => '...', 'html' => '...']].
 * - extraLeft:   (array<string, list<string>>) Per-node left extra content.
 * - extraRight:  (array<string, list<string>>) Per-node right extra content.
 *
 * Node params:
 * - class:    (string) Additional CSS class on the li element
 * - icon:     (string) Icon URL (empty string = no icon)
 * - iconalt:  (string) Alt text for the icon img
 * - iconopen: (string) Alternate icon URL when node is expanded
 * - url:      (string) Makes the label a link
 * - urlclass: (string) CSS class on the label link
 * - target:   (string) Link target attribute (overrides global option)
 * - title:    (string) Tooltip on the label link
 *
 * CSS classes (BEM):
 * - .horde-tree              Root nav element
 * - .horde-tree--alternate   Modifier for CSS row striping
 * - .horde-tree__list        ul at any nesting level
 * - .horde-tree__item        li for each node
 * - .horde-tree__toggle      details wrapper for expandable nodes
 * - .horde-tree__summary     summary element inside details
 * - .horde-tree__content     Inner content wrapper (icon + label + extras)
 * - .horde-tree__icon        img element for node icon
 * - .horde-tree__label       a or span element for node label
 * - .horde-tree__extra-left  Span for left extra column content
 * - .horde-tree__extra-right Span for right extra column content
 * - .horde-tree__header      li for the header row
 * - .horde-tree__header-cell Span for each header cell
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 *
 * @unstable  This renderer is experimental. No backward compatibility promise
 *            is made for its HTML output structure, CSS class names, ARIA
 *            attributes, or render option keys. Any of these may change without
 *            notice in future releases.
 */
final class ResponsiveRenderer implements RendererInterface
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
     * Render the tree as semantic HTML5.
     *
     * @param array<string, mixed> $options Renderer options (see class docblock)
     *
     * @unstable Output structure may change without notice.
     */
    public function render(Tree $tree, array $options = []): string
    {
        $opts = $this->mergeOptions($options, $tree);

        if ($opts['static']) {
            $expandedStates = $this->allExpanded($tree);
        } else {
            $toggledNodeId = $this->detectToggle($tree);
            $expandedStates = $this->resolveExpandedStates($tree, $toggledNodeId);
        }

        $rootIds = $tree->getRootNodeIds();
        if ($rootIds === [] && empty($opts['headers'])) {
            return '';
        }

        $navClass = 'horde-tree';
        if ($opts['alternate']) {
            $navClass .= ' horde-tree--alternate';
        }

        $html = '<nav class="' . $navClass . '" role="tree"'
            . ' aria-label="' . htmlspecialchars($opts['ariaLabel'], ENT_QUOTES) . '">'
            . "\n"
            . '<ul class="horde-tree__list" role="group">' . "\n";

        if (!empty($opts['headers']) && !$opts['hideHeaders']) {
            $html .= $this->renderHeaders($opts);
        }

        foreach ($rootIds as $rootId) {
            $html .= $this->buildNodeHtml($tree, $rootId, $expandedStates, $opts);
        }

        $html .= "</ul>\n</nav>\n";

        return $html;
    }

    /**
     * Build structured RenderedNode objects from a tree.
     *
     * @return list<RenderedNode>
     *
     * @unstable Return structure may change without notice.
     */
    public function buildStructured(Tree $tree, array $options = []): array
    {
        $opts = $this->mergeOptions($options, $tree);

        if ($opts['static']) {
            $expandedStates = $this->allExpanded($tree);
        } else {
            $toggledNodeId = $this->detectToggle($tree);
            $expandedStates = $this->resolveExpandedStates($tree, $toggledNodeId);
        }

        $result = [];
        foreach ($tree->getRootNodeIds() as $rootId) {
            $result[] = $this->buildStructuredNode($tree, $rootId, $expandedStates, $opts);
        }
        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function mergeOptions(array $options, Tree $tree): array
    {
        return array_merge([
            'static' => false,
            'hideHeaders' => false,
            'alternate' => false,
            'target' => '',
            'ariaLabel' => $tree->name,
            'headers' => [],
            'extraLeft' => [],
            'extraRight' => [],
        ], $options);
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
     * @return array<string, bool>
     */
    private function resolveExpandedStates(Tree $tree, ?string $toggledNodeId): array
    {
        $states = [];
        foreach ($tree->getNodes() as $id => $node) {
            $storedState = $this->stateStorage->isExpanded($tree->name, $id);

            if ($id === $toggledNodeId) {
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
     * @return array<string, bool>
     */
    private function allExpanded(Tree $tree): array
    {
        $states = [];
        foreach ($tree->getNodes() as $id => $node) {
            $states[$id] = true;
        }
        return $states;
    }

    private function buildNodeHtml(
        Tree $tree,
        string $nodeId,
        array $expandedStates,
        array $opts,
    ): string {
        $node = $tree->getNode($nodeId);
        $isExpanded = $expandedStates[$nodeId] ?? $node->expanded;
        $childIds = $tree->getChildIds($nodeId);
        $hasChildren = $childIds !== [];

        $liClass = 'horde-tree__item';
        $nodeClass = $node->params['class'] ?? '';
        if ($nodeClass !== '') {
            $liClass .= ' ' . htmlspecialchars($nodeClass, ENT_QUOTES);
        }

        $content = $this->renderNodeContent($tree, $node, $isExpanded, $hasChildren, $opts);

        if (!$hasChildren) {
            return '<li class="' . $liClass . '" role="treeitem"'
                . ' data-node-id="' . htmlspecialchars($nodeId, ENT_QUOTES) . '">'
                . "\n"
                . '<span class="horde-tree__content">' . $content . '</span>'
                . "\n</li>\n";
        }

        $ariaExpanded = $isExpanded ? 'true' : 'false';
        $childrenHtml = '';
        if ($isExpanded) {
            $childrenHtml = '<ul class="horde-tree__list" role="group">' . "\n";
            foreach ($childIds as $childId) {
                $childrenHtml .= $this->buildNodeHtml($tree, $childId, $expandedStates, $opts);
            }
            $childrenHtml .= "</ul>\n";
        }

        if ($opts['static']) {
            return '<li class="' . $liClass . '" role="treeitem"'
                . ' aria-expanded="' . $ariaExpanded . '"'
                . ' data-node-id="' . htmlspecialchars($nodeId, ENT_QUOTES) . '">'
                . "\n"
                . '<span class="horde-tree__content">' . $content . '</span>'
                . "\n"
                . $childrenHtml
                . "</li>\n";
        }

        $openAttr = $isExpanded ? ' open' : '';
        return '<li class="' . $liClass . '" role="treeitem"'
            . ' aria-expanded="' . $ariaExpanded . '"'
            . ' data-node-id="' . htmlspecialchars($nodeId, ENT_QUOTES) . '">'
            . "\n"
            . '<details class="horde-tree__toggle"' . $openAttr . '>'
            . "\n"
            . '<summary class="horde-tree__summary">'
            . "\n"
            . '<span class="horde-tree__content">' . $content . '</span>'
            . "\n"
            . '</summary>'
            . "\n"
            . $childrenHtml
            . "</details>\n"
            . "</li>\n";
    }

    private function renderNodeContent(
        Tree $tree,
        Node $node,
        bool $isExpanded,
        bool $hasChildren,
        array $opts,
    ): string {
        $content = '';

        // Extra left columns
        $extraLeft = $opts['extraLeft'][$node->id] ?? [];
        foreach ($extraLeft as $extra) {
            $content .= '<span class="horde-tree__extra-left">' . $extra . '</span>';
        }

        // Icon
        $icon = $this->resolveIcon($node, $isExpanded, $hasChildren);
        if ($icon !== '') {
            $alt = isset($node->params['iconalt'])
                ? htmlspecialchars($node->params['iconalt'], ENT_QUOTES)
                : '';
            $content .= '<img class="horde-tree__icon"'
                . ' src="' . htmlspecialchars($icon, ENT_QUOTES) . '"'
                . ' alt="' . $alt . '" />';
        }

        // Label
        $url = $node->params['url'] ?? '';
        if ($url !== '') {
            $target = $node->params['target'] ?? ($opts['target'] ?? '');
            $targetAttr = $target !== '' ? ' target="' . htmlspecialchars($target, ENT_QUOTES) . '"' : '';
            $urlclass = $node->params['urlclass'] ?? '';
            $labelClass = 'horde-tree__label';
            if ($urlclass !== '') {
                $labelClass .= ' ' . htmlspecialchars($urlclass, ENT_QUOTES);
            }
            $titleAttr = isset($node->params['title'])
                ? ' title="' . htmlspecialchars($node->params['title'], ENT_QUOTES) . '"'
                : '';
            $content .= '<a class="' . $labelClass . '"'
                . ' href="' . htmlspecialchars((string) $url, ENT_QUOTES) . '"'
                . $targetAttr
                . $titleAttr
                . '>' . $node->label . '</a>';
        } else {
            $content .= '<span class="horde-tree__label">' . $node->label . '</span>';
        }

        // Extra right columns
        $extraRight = $opts['extraRight'][$node->id] ?? [];
        foreach ($extraRight as $extra) {
            $content .= '<span class="horde-tree__extra-right">' . $extra . '</span>';
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

    private function renderHeaders(array $opts): string
    {
        $html = '<li class="horde-tree__header" role="presentation">' . "\n";
        foreach ($opts['headers'] as $header) {
            $cls = !empty($header['class'])
                ? ' ' . htmlspecialchars($header['class'], ENT_QUOTES)
                : '';
            $html .= '<span class="horde-tree__header-cell' . $cls . '">'
                . ($header['html'] ?? '')
                . '</span>';
        }
        $html .= "\n</li>\n";
        return $html;
    }

    private function buildStructuredNode(
        Tree $tree,
        string $nodeId,
        array $expandedStates,
        array $opts,
    ): RenderedNode {
        $node = $tree->getNode($nodeId);
        $isExpanded = $expandedStates[$nodeId] ?? $node->expanded;
        $childIds = $tree->getChildIds($nodeId);
        $hasChildren = $childIds !== [];

        $content = $this->renderNodeContent($tree, $node, $isExpanded, $hasChildren, $opts);
        $indent = $tree->getIndentLevel($nodeId);

        $children = [];
        if ($hasChildren && $isExpanded) {
            foreach ($childIds as $childId) {
                $children[] = $this->buildStructuredNode($tree, $childId, $expandedStates, $opts);
            }
        }

        return new RenderedNode(
            $nodeId,
            $content,
            $indent,
            $node->params['class'] ?? '',
            $children,
            $opts['extraLeft'][$nodeId] ?? [],
            $opts['extraRight'][$nodeId] ?? [],
        );
    }
}
