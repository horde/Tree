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
 * State storage backed by callable get/set functions.
 *
 * This bridges the legacy Horde_Tree session callback approach to the modern
 * StateStorageInterface. Useful for transitional code that still uses
 * Horde session storage.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class CallbackStateStorage implements StateStorageInterface
{
    /** @var callable(string, string): (bool|null) */
    private $getter;

    /** @var callable(string, string, bool): void */
    private $setter;

    /**
     * @param callable(string, string): (bool|null) $getter (treeName, nodeId) => bool|null
     * @param callable(string, string, bool): void  $setter (treeName, nodeId, expanded) => void
     */
    public function __construct(callable $getter, callable $setter)
    {
        $this->getter = $getter;
        $this->setter = $setter;
    }

    public function isExpanded(string $treeName, string $nodeId): ?bool
    {
        $result = ($this->getter)($treeName, $nodeId);
        return $result === null ? null : (bool) $result;
    }

    public function setExpanded(string $treeName, string $nodeId, bool $expanded): void
    {
        ($this->setter)($treeName, $nodeId, $expanded);
    }
}
