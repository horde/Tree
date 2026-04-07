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

use Countable;

/**
 * Immutable tree data structure holding nodes in a parent-child hierarchy.
 *
 * Use TreeBuilder to construct a Tree instance.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class Tree implements Countable
{
    /** @var array<string, Node> */
    private readonly array $nodes;

    /** @var array<string, list<string>> Parent ID => child IDs */
    private readonly array $childMap;

    /** @var list<string> */
    private readonly array $rootNodeIds;

    /**
     * @param array<string, Node>        $nodes
     * @param list<string>               $rootNodeIds
     * @param array<string, list<string>> $childMap
     */
    public function __construct(
        public readonly string $name,
        array $nodes,
        array $rootNodeIds,
        array $childMap,
    ) {
        $this->nodes = $nodes;
        $this->rootNodeIds = $rootNodeIds;
        $this->childMap = $childMap;
    }

    /**
     * @return list<string>
     */
    public function getRootNodeIds(): array
    {
        return $this->rootNodeIds;
    }

    /**
     * @throws Exception If node ID not found.
     */
    public function getNode(string $id): Node
    {
        if (!isset($this->nodes[$id])) {
            throw new Exception('Node not found: ' . $id);
        }
        return $this->nodes[$id];
    }

    public function hasNode(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    /**
     * @return array<string, Node>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * Get direct child node IDs of the given parent.
     *
     * @return list<string>
     */
    public function getChildIds(string $parentId): array
    {
        return $this->childMap[$parentId] ?? [];
    }

    /**
     * Get direct child Node objects of the given parent.
     *
     * @return list<Node>
     */
    public function getChildren(string $parentId): array
    {
        $children = [];
        foreach ($this->getChildIds($parentId) as $childId) {
            $children[] = $this->nodes[$childId];
        }
        return $children;
    }

    /**
     * Calculate the indent level (depth) of a node.
     */
    public function getIndentLevel(string $id): int
    {
        $level = 0;
        $node = $this->getNode($id);
        while ($node->parentId !== null) {
            $level++;
            $node = $this->getNode($node->parentId);
        }
        return $level;
    }

    /**
     * Return a new Tree with children sorted by a node property.
     *
     * Sorts recursively through all levels.
     *
     * @param string $property The Node property or param key to sort by.
     *                         Supports 'label', 'id', or any params key.
     */
    public function sorted(string $property): self
    {
        $sortedChildMap = $this->sortChildMap($this->childMap, $property);
        $sortedRoots = $this->sortNodeIds($this->rootNodeIds, $property);

        return new self($this->name, $this->nodes, $sortedRoots, $sortedChildMap);
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    /**
     * @param array<string, list<string>> $childMap
     * @return array<string, list<string>>
     */
    private function sortChildMap(array $childMap, string $property): array
    {
        $sorted = [];
        foreach ($childMap as $parentId => $childIds) {
            $sorted[$parentId] = $this->sortNodeIds($childIds, $property);
        }
        return $sorted;
    }

    /**
     * @param list<string> $nodeIds
     * @return list<string>
     */
    private function sortNodeIds(array $nodeIds, string $property): array
    {
        usort($nodeIds, function (string $a, string $b) use ($property): int {
            $valueA = $this->getNodeSortValue($a, $property);
            $valueB = $this->getNodeSortValue($b, $property);
            return strcoll($valueA, $valueB);
        });
        return $nodeIds;
    }

    private function getNodeSortValue(string $nodeId, string $property): string
    {
        $node = $this->nodes[$nodeId];
        return match ($property) {
            'label' => $node->label,
            'id' => $node->id,
            default => (string) ($node->params[$property] ?? ''),
        };
    }
}
