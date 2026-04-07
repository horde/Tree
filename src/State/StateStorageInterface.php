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

namespace Horde\Tree\State;

/**
 * Interface for tree node expand/collapse state persistence.
 *
 * Implementations store and retrieve the expanded state of tree nodes,
 * allowing tree state to survive across requests (e.g. via session storage).
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
interface StateStorageInterface
{
    /**
     * Get the stored expanded state of a node.
     *
     * @return bool|null True if expanded, false if collapsed, null if no state stored.
     */
    public function isExpanded(string $treeName, string $nodeId): ?bool;

    /**
     * Store the expanded state of a node.
     */
    public function setExpanded(string $treeName, string $nodeId, bool $expanded): void;
}
