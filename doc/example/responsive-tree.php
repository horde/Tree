<?php

declare(strict_types=1);

/**
 * Example: Rendering a responsive tree with Horde\Tree.
 *
 * This self-contained script builds a realistic mailbox tree and renders it
 * using the ResponsiveRenderer. Link responsive-tree.css for the visual
 * styling. Run with any local PHP server:
 *
 *     php -S localhost:8080 doc/example/responsive-tree.php
 *
 * The ResponsiveRenderer is @unstable — HTML structure and class names may
 * change between releases.
 *
 * SPDX-License-Identifier: LGPL-2.1-only
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Horde\Tree\Node;
use Horde\Tree\Renderer\ResponsiveRenderer;
use Horde\Tree\TreeBuilder;

/*
 * Build a mailbox folder tree.
 *
 * The tree demonstrates:
 * - Root nodes with icons and URLs
 * - Nested hierarchies (Folders > Projects > individual items)
 * - Expanded and collapsed branches
 * - Leaf nodes with different params
 */
$tree = (new TreeBuilder('mailbox'))
    ->addNode(new Node('inbox', 'Inbox (12)', params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/inbox.svg',
        'iconalt' => 'Inbox',
        'url' => '#inbox',
    ]))
    ->addNode(new Node('inbox-unread', 'Unread (5)', parentId: 'inbox', params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/mail.svg',
        'iconalt' => 'Unread',
        'url' => '#inbox-unread',
    ]))
    ->addNode(new Node('inbox-flagged', 'Flagged (2)', parentId: 'inbox', params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/flag.svg',
        'iconalt' => 'Flagged',
        'url' => '#inbox-flagged',
    ]))
    ->addNode(new Node('sent', 'Sent', expanded: false, params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/send.svg',
        'iconalt' => 'Sent',
        'url' => '#sent',
    ]))
    ->addNode(new Node('sent-month', 'This Month', parentId: 'sent', params: [
        'url' => '#sent-month',
    ]))
    ->addNode(new Node('sent-older', 'Older', parentId: 'sent', params: [
        'url' => '#sent-older',
    ]))
    ->addNode(new Node('drafts', 'Drafts (3)', params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/file-edit.svg',
        'iconalt' => 'Drafts',
        'url' => '#drafts',
    ]))
    ->addNode(new Node('trash', 'Trash', params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/trash-2.svg',
        'iconalt' => 'Trash',
        'url' => '#trash',
        'class' => 'trash-folder',
    ]))
    ->addNode(new Node('folders', 'Folders', params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/folder.svg',
        'iconopen' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/folder-open.svg',
        'iconalt' => 'Folders',
    ]))
    ->addNode(new Node('projects', 'Projects', parentId: 'folders', params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/folder.svg',
        'iconopen' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/folder-open.svg',
        'iconalt' => 'Projects',
    ]))
    ->addNode(new Node('proj-horde', 'Horde', parentId: 'projects', params: [
        'url' => '#proj-horde',
    ]))
    ->addNode(new Node('proj-personal', 'Personal', parentId: 'projects', params: [
        'url' => '#proj-personal',
    ]))
    ->addNode(new Node('archives', 'Archives', parentId: 'folders', expanded: false, params: [
        'icon' => 'https://cdn.jsdelivr.net/npm/lucide-static@0.469.0/icons/archive.svg',
        'iconalt' => 'Archives',
    ]))
    ->addNode(new Node('archive-2025', '2025', parentId: 'archives', params: [
        'url' => '#archive-2025',
    ]))
    ->addNode(new Node('archive-2024', '2024', parentId: 'archives', params: [
        'url' => '#archive-2024',
    ]))
    ->build();

$renderer = new ResponsiveRenderer();

/*
 * Render in interactive mode (default).
 * <details>/<summary> elements allow client-side expand/collapse.
 */
$interactiveOutput = $renderer->render($tree, [
    'ariaLabel' => 'Mailbox folders',
]);

/*
 * Render in static mode.
 * All nodes are expanded, no <details>/<summary> elements.
 */
$staticOutput = $renderer->render($tree, [
    'static' => true,
    'ariaLabel' => 'Mailbox folders (static)',
]);

/*
 * Render with headers and extra columns.
 */
$headersOutput = $renderer->render($tree, [
    'ariaLabel' => 'Mailbox with columns',
    'headers' => [
        ['class' => 'col-name', 'html' => 'Folder'],
        ['class' => 'col-actions', 'html' => 'Actions'],
    ],
    'extraRight' => [
        'inbox' => ['<button type="button">Mark read</button>'],
        'trash' => ['<button type="button">Empty</button>'],
    ],
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ResponsiveRenderer Example — Horde\Tree</title>
    <link rel="stylesheet" href="responsive-tree.css" />
    <style>
        /* Page layout for the example — not part of the tree styles */
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 2rem;
            font-family: system-ui, sans-serif;
            background: var(--horde-tree-bg, #fff);
            color: var(--horde-tree-color, #1a1a2e);
        }
        h1 { font-size: 1.5rem; margin: 0 0 0.5rem; }
        h2 { font-size: 1.125rem; margin: 2rem 0 0.5rem; color: #64748b; }
        p { margin: 0 0 1rem; color: #64748b; font-size: 0.875rem; }
        .demo-container {
            max-width: 28rem;
            border: 1px solid var(--horde-tree-border-color, #e2e8f0);
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin-bottom: 2rem;
        }
        code { background: #f1f5f9; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 0.8125rem; }
    </style>
</head>
<body>

<h1>Horde\Tree ResponsiveRenderer</h1>
<p>
    Semantic HTML5 tree with <code>&lt;details&gt;</code>/<code>&lt;summary&gt;</code>
    for native expand/collapse. Resize the browser to see responsive behavior.
</p>

<h2>Interactive mode (default)</h2>
<p>Click the disclosure triangle or summary row to expand/collapse branches.</p>
<div class="demo-container">
    <?= $interactiveOutput ?>
</div>

<h2>Static mode</h2>
<p>All branches expanded, no toggle controls. Useful for print or export.</p>
<div class="demo-container">
    <?= $staticOutput ?>
</div>

<h2>With headers and extra columns</h2>
<p>Headers and right-side action buttons. Extra columns are hidden on narrow screens.</p>
<div class="demo-container">
    <?= $headersOutput ?>
</div>

</body>
</html>
