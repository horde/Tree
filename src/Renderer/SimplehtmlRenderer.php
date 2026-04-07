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

use Horde\Tree\State\NullStateStorage;
use Horde\Tree\State\StateStorageInterface;
use Horde\Tree\Tree;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Simple HTML renderer using divs and text-based [+]/[-] toggles.
 *
 * Produces lightweight HTML without graphical tree lines.
 * Toggle links include the tree toggle parameter for expand/collapse.
 *
 * Node params:
 * - class: (string) CSS class for the node div
 * - url:   (string) Link URL for the node label
 *
 * Options:
 * - static:     (bool) Render expanded, no toggle links. Default: false.
 * - toggleUrl:  (string) Base URL for toggle links. Default: ''.
 * - extraLeft:  (array<string, list<string>>) nodeId => left extra columns.
 * - extraRight: (array<string, list<string>>) nodeId => right extra columns.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class SimplehtmlRenderer implements RendererInterface
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

    public function render(Tree $tree, array $options = []): string
    {
        $opts = array_merge([
            'static' => false,
            'toggleUrl' => '',
            'extraLeft' => [],
            'extraRight' => [],
        ], $options);

        $toggledNodeId = $this->detectToggle($tree);
        $expandedStates = $this->resolveExpandedStates($tree, $toggledNodeId);

        $html = '';
        foreach ($tree->getRootNodeIds() as $rootId) {
            $html .= $this->buildNode($tree, $rootId, $expandedStates, $opts);
        }
        return $html;
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

    private function buildNode(Tree $tree, string $nodeId, array $expandedStates, array $opts): string
    {
        $node = $tree->getNode($nodeId);
        $indent = $tree->getIndentLevel($nodeId);
        $isExpanded = $expandedStates[$nodeId] ?? $node->expanded;
        $hasChildren = count($tree->getChildIds($nodeId)) > 0;

        $cssClass = $node->params['class'] ?? '';
        $output = '<div' . ($cssClass !== '' ? ' class="' . htmlspecialchars($cssClass, ENT_QUOTES) . '"' : '') . '>';

        // Left extra columns
        $extraLeft = $opts['extraLeft'][$nodeId] ?? [];
        if ($extraLeft !== []) {
            $output .= implode(' ', $extraLeft);
        }

        // Indentation
        $output .= str_repeat('&nbsp;', $indent * 2);

        // Label (with optional link)
        $url = $node->params['url'] ?? '';
        if ($url !== '') {
            $output .= '<a href="' . htmlspecialchars((string) $url, ENT_QUOTES) . '">' . $node->label . '</a>';
        } else {
            $output .= $node->label;
        }

        // Right extra columns
        $extraRight = $opts['extraRight'][$nodeId] ?? [];
        if ($extraRight !== []) {
            $output .= implode(' ', $extraRight);
        }

        // Toggle for nodes with children
        if ($hasChildren && !$opts['static']) {
            $toggleUrl = $opts['toggleUrl'];
            $separator = str_contains($toggleUrl, '?') ? '&' : '?';
            $toggleParam = 'ht_toggle_' . htmlspecialchars($tree->name, ENT_QUOTES);
            $toggleLink = htmlspecialchars($toggleUrl, ENT_QUOTES)
                . $separator . $toggleParam . '='
                . htmlspecialchars($nodeId, ENT_QUOTES);
            $symbol = $isExpanded ? '-' : '+';
            $output .= '&nbsp;[<a href="' . $toggleLink . '">' . $symbol . '</a>]';
        }

        $output .= '</div>';

        // Render children if expanded
        if ($hasChildren && $isExpanded) {
            foreach ($tree->getChildIds($nodeId) as $childId) {
                $output .= $this->buildNode($tree, $childId, $expandedStates, $opts);
            }
        }

        return $output;
    }
}
