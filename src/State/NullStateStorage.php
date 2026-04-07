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
 * No-op state storage that never persists state.
 *
 * All nodes will use their default expanded state from the Node definition.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class NullStateStorage implements StateStorageInterface
{
    public function isExpanded(string $treeName, string $nodeId): ?bool
    {
        return null;
    }

    public function setExpanded(string $treeName, string $nodeId, bool $expanded): void
    {
        // No-op
    }
}
