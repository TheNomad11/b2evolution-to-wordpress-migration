<?php
require_once 'wp-load.php';

$dry_run = in_array('--dry-run', $argv);
echo $dry_run ? "🔍 Running in DRY-RUN mode. No changes will be made.\n" : "🔧 Assigning categories to collection parents...\n";

$terms = get_terms([
    'taxonomy' => 'category',
    'hide_empty' => false,
]);

$collections = [];
foreach ($terms as $term) {
    if (str_starts_with($term->slug, 'collection-')) {
        $collections[$term->slug] = $term->term_id;
    }
}

$updated = 0;
foreach ($terms as $term) {
    if ($term->parent != 0) continue;

    foreach ($collections as $slug => $collection_id) {
        if (str_starts_with($term->slug, 'blog')) {
            if (str_contains($term->slug, substr($slug, 11))) {
                if ($dry_run) {
                    echo "Would assign parent of {$term->slug} to $slug\n";
                } else {
                    wp_update_term($term->term_id, 'category', ['parent' => $collection_id]);
                    $updated++;
                }
                break;
            }
        }
    }
}

if (!$dry_run) {
    echo "✅ Updated $updated categories to assign collection parents.\n";
} else {
    echo "✅ Dry run completed.\n";
}
