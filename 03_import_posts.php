<?php
// 03_import_posts.php
// Import published posts from b2evolution into WordPress

require_once 'wp-load.php';

$b2evo = new mysqli('localhost', 'b2evo_user', 'b2evo_pass', 'b2evo_db');
if ($b2evo->connect_error) {
    die("Connection failed: " . $b2evo->connect_error);
}

// Load mappings
$blog_map = json_decode(file_get_contents('blog_map.json'), true);
$cat_map = json_decode(file_get_contents('cat_map.json'), true);

echo "🔄 Importing published posts from b2evolution...\n";

$query = "
    SELECT 
        i.post_ID,
        i.post_title,
        i.post_content,
        i.post_excerpt,
        i.post_datestart,
        i.post_creator_user_ID,
        i.post_main_cat_ID
    FROM evo_items__item i
    WHERE i.post_status = 'published'
";

$result = $b2evo->query($query);
$count = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $b2_id = (int)$row['post_ID'];

    // Skip if already imported
    $existing = $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("
        SELECT post_id FROM {$GLOBALS['wpdb']->postmeta}
        WHERE meta_key = '_b2evo_post_id' AND meta_value = %d
    ", $b2_id));

    if ($existing) {
        $skipped++;
        continue;
    }

    // Prepare post data
    $post_data = [
        'post_title'   => $row['post_title'],
        'post_content' => $row['post_content'],
        'post_excerpt' => $row['post_excerpt'],
        'post_status'  => 'publish',
        'post_author'  => 1, // default to admin
        'post_date'    => $row['post_datestart'],
        'post_date_gmt'=> get_gmt_from_date($row['post_datestart']),
    ];

    // Insert post
    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        echo "❌ Failed to insert post {$b2_id}: " . $post_id->get_error_message() . "\n";
        continue;
    }

    // Map postmeta
    add_post_meta($post_id, '_b2evo_post_id', $b2_id);

    // Assign collection category
    $main_cat = (int)$row['post_main_cat_ID'];
    $coll_cat_id = null;

    $cat_result = $b2evo->query("SELECT cat_blog_ID FROM evo_categories WHERE cat_ID = $main_cat");
    if ($cat_result && $cat_row = $cat_result->fetch_assoc()) {
        $blog_id = (int)$cat_row['cat_blog_ID'];
        if (isset($blog_map[$blog_id])) {
            $coll_cat_id = $blog_map[$blog_id];
        }
    }

    $term_ids = [];

    if ($coll_cat_id) {
        $term_ids[] = $coll_cat_id;
    }

    // Get all categories assigned via postcats
    $cats_result = $b2evo->query("
        SELECT postcat_cat_ID 
        FROM evo_postcats 
        WHERE postcat_post_ID = $b2_id
    ");

    while ($cats_result && $cat_row = $cats_result->fetch_assoc()) {
        $b2_cat_id = (int)$cat_row['postcat_cat_ID'];
        if (isset($cat_map[$b2_cat_id])) {
            $term_ids[] = $cat_map[$b2_cat_id];
        }
    }

    // Remove duplicates
    $term_ids = array_unique($term_ids);

    // Assign categories
    if (!empty($term_ids)) {
        wp_set_post_categories($post_id, $term_ids);
    }

    $count++;
}

echo "✅ Imported {$count} posts.\n";
echo "⏭️ Skipped {$skipped} posts already imported.\n";
