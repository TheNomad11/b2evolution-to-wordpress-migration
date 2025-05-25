<?php
require_once 'wp-load.php';

echo "🔄 Linking posts to b2evolution categories...\n";

$cat_map = json_decode(file_get_contents('cat_map.json'), true);

global $wpdb;
$b2evo_db = new wpdb('b2evo_user', 'b2evo_pass', 'b2evo_db', 'localhost');

$linked = 0;
$skipped = 0;

$results = $b2evo_db->get_results("
    SELECT postcat_post_ID AS b2evo_post_id, postcat_cat_ID AS b2evo_cat_id
    FROM evo_postcats
");

foreach ($results as $row) {
    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_b2evo_post_id' AND meta_value = %d",
        $row->b2evo_post_id
    ));

    if (!$post_id) {
        $skipped++;
        continue;
    }

    $term_id = $cat_map[$row->b2evo_cat_id] ?? null;

    if (!$term_id) {
        $skipped++;
        continue;
    }

    $current_terms = wp_get_post_categories($post_id);
    if (!in_array($term_id, $current_terms)) {
        $current_terms[] = $term_id;
        wp_set_post_categories($post_id, $current_terms);
        $linked++;
    }
}

echo "✅ Linked $linked category assignments.\n";
echo "ℹ️ Skipped $skipped entries (missing mapping).\n";
