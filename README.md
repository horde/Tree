# Horde Tree

Tree data-structure and rendering library for PHP 8.1+.

Provides both a legacy PSR-0 API (`Horde_Tree`) and a modern PSR-4 API (`Horde\Tree`) 

## Install

```bash
composer require horde/tree
```

## Quick Start

```php
use Horde\Tree\Node;
use Horde\Tree\TreeBuilder;
use Horde\Tree\Renderer\ResponsiveRenderer;

$tree = (new TreeBuilder('nav'))
    ->addNode(new Node('inbox', 'Inbox', params: ['icon' => '/mail.svg', 'url' => '/mail']))
    ->addNode(new Node('unread', 'Unread (5)', parentId: 'inbox'))
    ->build();

echo (new ResponsiveRenderer())->render($tree);
```

## Renderers

| Renderer | Output |
|---|---|
| `HtmlRenderer` | Div-based HTML with row classes |
| `SelectRenderer` | `<option>` elements for `<select>` |
| `SimplehtmlRenderer` | Lightweight divs with `[+]/[-]` toggles |
| `ResponsiveRenderer` | Semantic HTML5 with `<details>/<summary>` and ARIA *(unstable)* |

## Links

- [Migration guide](doc/UPGRADING.md) — upgrading from v2 to v3
- [Example CSS](doc/example/responsive-tree.css) — reference stylesheet for ResponsiveRenderer
- [Example PHP](doc/example/responsive-tree.php) — runnable demo page
- [Changelog](doc/Horde/Tree/changelog.yml)

## License

LGPL-2.1-only — see [LICENSE](LICENSE).
