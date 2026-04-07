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
 * Mutable builder for constructing an immutable Tree.
 *
 * Usage:
 *   $tree = (new TreeBuilder('nav'))
 *       ->addNode(new Node('root', 'Root'))
 *       ->addNode(new Node('child', 'Child', parentId: 'root'))
 *       ->build();
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class TreeBuilder
{
    /** @var array<string, Node> */
    private array $nodes = [];

    /** @var list<string> */
    private array $rootNodeIds = [];

    /** @var array<string, list<string>> */
    private array $childMap = [];

    public function __construct(
        private readonly string $name,
    ) {}

    /**
     * Add a node to the tree being built.
     *
     * If a node with the same ID exists, it will be replaced.
     */
    public function addNode(Node $node): self
    {
        $id = $node->id;
        $this->nodes[$id] = $node;

        if ($node->parentId === null) {
            if (!in_array($id, $this->rootNodeIds, true)) {
                $this->rootNodeIds[] = $id;
            }
        } else {
            if (!isset($this->childMap[$node->parentId])) {
                $this->childMap[$node->parentId] = [];
            }
            if (!in_array($id, $this->childMap[$node->parentId], true)) {
                $this->childMap[$node->parentId][] = $id;
            }
        }

        return $this;
    }

    /**
     * Update parameters on an existing node.
     *
     * @param array<string, mixed> $params
     * @throws Exception If node not found.
     */
    public function addNodeParams(string $id, array $params): self
    {
        if (!isset($this->nodes[$id])) {
            throw new Exception('Node not found: ' . $id);
        }
        $this->nodes[$id] = $this->nodes[$id]->withParams($params);
        return $this;
    }

    /**
     * Build the immutable Tree from the current state.
     */
    public function build(): Tree
    {
        return new Tree(
            $this->name,
            $this->nodes,
            $this->rootNodeIds,
            $this->childMap,
        );
    }
}
