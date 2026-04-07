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
 * Immutable value object representing a single tree node.
 *
 * @category  Horde
 * @copyright 2026 The Horde Project
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Tree
 */
final class Node
{
    /**
     * @param array<string, mixed> $params Additional renderer-specific parameters
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly ?string $parentId = null,
        public readonly bool $expanded = true,
        public readonly array $params = [],
    ) {}

    /**
     * Return a new Node with additional or overridden parameters.
     *
     * @param array<string, mixed> $params
     */
    public function withParams(array $params): self
    {
        return new self(
            $this->id,
            $this->label,
            $this->parentId,
            $this->expanded,
            array_merge($this->params, $params),
        );
    }

    /**
     * Return a new Node with a different expanded state.
     */
    public function withExpanded(bool $expanded): self
    {
        return new self(
            $this->id,
            $this->label,
            $this->parentId,
            $expanded,
            $this->params,
        );
    }
}
