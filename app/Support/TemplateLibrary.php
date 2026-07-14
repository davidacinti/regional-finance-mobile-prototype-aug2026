<?php

namespace App\Support;

class TemplateLibrary
{
  public static function pages(): array
  {
    $pages = [];

    foreach (self::menu() as $item) {
      self::collectPages($item->submenu ?? [], $pages, $item->name ?? 'Templates');
    }

    return array_values($pages);
  }

  public static function categories(): array
  {
    return array_values(array_unique(array_map(fn ($page) => $page['category'], self::pages())));
  }

  private static function menu(): array
  {
    $path = base_path('resources/menu/verticalMenu.json');

    if (!is_file($path)) {
      return [];
    }

    return json_decode(file_get_contents($path))->menu ?? [];
  }

  private static function collectPages(iterable $items, array &$pages, string $category): void
  {
    foreach ($items as $item) {
      $itemCategory = isset($item->menuHeader) ? $item->menuHeader : $category;

      if (isset($item->url) && str_starts_with($item->url, 'templates/')) {
        $path = substr($item->url, strlen('templates/'));
        $pages[$path] = [
          'name' => $item->name ?? $path,
          'category' => $itemCategory,
          'route' => '/' . $item->url,
          'url' => url($item->url),
        ];
      }

      if (isset($item->submenu)) {
        self::collectPages($item->submenu, $pages, $itemCategory);
      }
    }
  }
}
