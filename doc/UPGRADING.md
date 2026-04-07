# Upgrading to Tree 3.0 (PSR-4)

## Overview

Version 3.0 introduces a modern PSR-4 implementation alongside the existing PSR-0 `lib/` code.
The legacy API remains fully functional for backward compatibility. Callers are encouraged to
migrate to the new `Horde\Tree` namespace for strict types, immutability, and testability.

## Requirements

- PHP 8.1 or later
- `horde/exception` ^3 (with PSR-4 `Horde\Exception\HordeThrowable`)
- `psr/http-message` ^1.1 || ^2.0 (optional, for PSR-7 toggle detection)

## Architecture Changes

### Separation of Concerns

The legacy API mixes tree construction and rendering into mutable objects. The modern API cleanly
separates these into distinct immutable/mutable components:

| Responsibility | Legacy (lib/) | Modern (src/) |
|---|---|---|
| Tree data | `Horde_Tree` (mutable) | `Horde\Tree\Tree` (immutable) |
| Tree construction | `Horde_Tree::addNode()` | `Horde\Tree\TreeBuilder` (mutable builder) |
| Node representation | Array hash | `Horde\Tree\Node` (value object) |
| Renderer factory | `Horde_Tree_Renderer::factory()` | Direct instantiation |
| HTML renderer | `Horde_Tree_Renderer_Html` | `Horde\Tree\Renderer\HtmlRenderer` |
| Select renderer | `Horde_Tree_Renderer_Select` | `Horde\Tree\Renderer\SelectRenderer` |
| Simple HTML renderer | `Horde_Tree_Renderer_Simplehtml` | `Horde\Tree\Renderer\SimplehtmlRenderer` |
| State storage | Callback arrays | `Horde\Tree\State\StateStorageInterface` |
| Toggle detection | `Horde_Util::getFormData()` | `Psr\Http\Message\ServerRequestInterface` |
| Exception | `Horde_Tree_Exception` | `Horde\Tree\Exception` (implements `HordeThrowable`) |

### Immutability

The modern `Tree` is immutable once built. Sorting returns a new `Tree` instance:

```php
$sorted = $tree->sorted('label');
// $tree is unchanged, $sorted is a new instance
```

Nodes are value objects with readonly properties. Modification returns new instances:

```php
$updated = $node->withParams(['icon' => '/new.png']);
// $node is unchanged
```

### No Global State

The legacy API reads `$_GET`/`$_POST` via `Horde_Util::getFormData()` and `$_SERVER['PHP_SELF']`
via `Horde_Url`. The modern API has zero global state access:

- Toggle detection: Inject `ServerRequestInterface` into the renderer
- State persistence: Inject `StateStorageInterface` into the renderer
- Toggle URLs: Pass `toggleUrl` option to the renderer

## Breaking Changes

### Type Signature Incompatibility

`Horde_Tree` and `Horde\Tree\Tree` are distinct classes. Code with type hints for one will not
accept the other:

```php
// This function expects PSR-0 class
function renderNav(Horde_Tree $tree) { }

// FAILS at runtime with TypeError
$modernTree = (new TreeBuilder('nav'))->build();
renderNav($modernTree); // TypeError
```

### Renderer Factory Removed

The modern API does not use a factory. Instantiate renderers directly:

```php
// Legacy
$renderer = Horde_Tree_Renderer::factory('Html', ['name' => 'nav']);

// Modern
$renderer = new HtmlRenderer($stateStorage, $request);
```

### Session Callbacks Replaced

Legacy callback arrays are replaced by the `StateStorageInterface`:

```php
// Legacy
$session = [
    'get' => function ($instance, $id) { /* ... */ },
    'set' => function ($instance, $id, $value) { /* ... */ },
];
$tree = new Horde_Tree('nav', $session);

// Modern
$stateStorage = new CallbackStateStorage(
    fn ($treeName, $nodeId) => $session->get("tree_$treeName/$nodeId"),
    fn ($treeName, $nodeId, $expanded) => $session->set("tree_$treeName/$nodeId", $expanded),
);
$renderer = new HtmlRenderer($stateStorage, $request);
```

### jQuery Mobile Renderer Removed

`Horde_Tree_Renderer_Jquerymobile` was deprecated and has no modern equivalent.
Consider using the new Horde\Tree\Responsive renderer instead if targeting mobile-friendly presentations.

## Migration Guide

### Step 1: Replace Tree Construction

```php
// BEFORE (legacy)
$renderer = Horde_Tree_Renderer::factory('Html', ['name' => 'nav']);
$renderer->addNode([
    'id' => 'inbox',
    'parent' => null,
    'label' => 'Inbox',
    'expanded' => true,
    'params' => ['icon' => '/mail.png', 'url' => '/mail/inbox'],
]);
$renderer->addNode([
    'id' => 'unread',
    'parent' => 'inbox',
    'label' => 'Unread (5)',
]);
echo $renderer->getTree();

// AFTER (modern)
use Horde\Tree\Node;
use Horde\Tree\TreeBuilder;
use Horde\Tree\Renderer\HtmlRenderer;

$tree = (new TreeBuilder('nav'))
    ->addNode(new Node('inbox', 'Inbox', params: [
        'icon' => '/mail.png',
        'url' => '/mail/inbox',
    ]))
    ->addNode(new Node('unread', 'Unread (5)', parentId: 'inbox'))
    ->build();

$renderer = new HtmlRenderer();
echo $renderer->render($tree);
```

### Step 2: Replace State Management

```php
// BEFORE (legacy) - session callbacks
$tree = new Horde_Tree('nav', [
    'get' => [$session, 'get'],
    'set' => [$session, 'set'],
]);

// AFTER (modern) - StateStorageInterface
use Horde\Tree\State\CallbackStateStorage;

$stateStorage = new CallbackStateStorage(
    fn ($name, $id) => $session->get($name, $id),
    fn ($name, $id, $val) => $session->set($name, $id, $val),
);
$renderer = new HtmlRenderer($stateStorage, $request);
```

### Step 3: Replace Toggle Detection

```php
// BEFORE (legacy) - reads $_GET/$_POST automatically
// Toggle detection happens inside Horde_Tree::addNode()

// AFTER (modern) - inject PSR-7 request
$renderer = new HtmlRenderer($stateStorage, $request);
echo $renderer->render($tree);
// Toggle detection reads from $request->getQueryParams()
```

### Step 4: Replace Select Renderer

```php
// BEFORE
$renderer = Horde_Tree_Renderer::factory('Select', ['name' => 'folders']);
$renderer->addNode(['id' => 'inbox', 'parent' => null, 'label' => 'Inbox']);
echo '<select>' . $renderer->getTree() . '</select>';

// AFTER
use Horde\Tree\Renderer\SelectRenderer;

$tree = (new TreeBuilder('folders'))
    ->addNode(new Node('inbox', 'Inbox', params: ['selected' => true]))
    ->build();

echo '<select>' . (new SelectRenderer())->render($tree) . '</select>';
```

### Step 5: Using Structured Output (New)

The modern `HtmlRenderer` supports structured output for testing or custom assembly:

```php
$renderer = new HtmlRenderer();
$structured = $renderer->buildStructured($tree);

// $structured is an array of RenderedNode objects:
// - $structured[0]->nodeId    // 'root'
// - $structured[0]->content   // '<img ...>Label'
// - $structured[0]->indent    // 0
// - $structured[0]->children  // [RenderedNode, ...]
```

## Extra Columns

Extra columns (left/right content per node) are passed as render options:

```php
// Legacy
$renderer->addNode([
    'id' => 'item',
    'label' => 'Item',
    'left' => ['[icon]'],
    'right' => ['Active', 'Edit'],
]);

// Modern
echo $renderer->render($tree, [
    'extraLeft' => ['item' => ['[icon]']],
    'extraRight' => ['item' => ['Active', 'Edit']],
]);
```

## NullStateStorage

If you don't need state persistence (e.g., for static tree rendering), use `NullStateStorage`
(the default when no storage is provided):

```php
$renderer = new HtmlRenderer(); // Uses NullStateStorage internally
echo $renderer->render($tree);
```

---

## Historical Changes

### Upgrading from 1.0 to 2.0

The following changes were made in the 2.0 release:

- The tree renderers were separated from the tree representation.
  `Horde_Tree` represents a tree structure. The `Horde_Tree_Renderer_*`
  classes are the actual tree renderers.

- `Horde_Tree::factory()` was moved to `Horde_Tree_Renderer::factory()`
  with a different signature.

- The `Horde_Tree_Renderer` constructor has a different signature than
  the old `Horde_Tree` constructor.

- The `Horde_Tree::EXTRA_*` constants were moved to
  `Horde_Tree_Renderer::EXTRA_*`.

- `Horde_Tree::addNode()` was changed to take a single hash as the parameter.
