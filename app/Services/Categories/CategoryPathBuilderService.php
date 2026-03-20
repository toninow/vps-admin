<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Support\CategoryPathFormatter;

class CategoryPathBuilderService
{
    public function buildExportPath(Category|int|null $category): ?string
    {
        if ($category === null) {
            return null;
        }

        $leaf = $category instanceof Category
            ? $category
            : Category::query()->find($category);

        if (! $leaf) {
            return null;
        }

        $lineage = [];
        $current = $leaf;

        while ($current) {
            $name = trim((string) $current->name);
            if ($name !== '') {
                $lineage[] = $name;
            }

            $current->loadMissing('parent');
            $current = $current->parent;
        }

        if ($lineage === []) {
            return null;
        }

        $leafName = array_shift($lineage);
        $parts = array_merge([$leafName], array_reverse($lineage));

        $seen = [];
        $uniqueParts = [];
        foreach ($parts as $part) {
            $key = mb_strtolower(trim($part));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $uniqueParts[] = $part;
        }

        return $uniqueParts === [] ? null : CategoryPathFormatter::formatForDisplay(implode(', ', $uniqueParts));
    }
}
