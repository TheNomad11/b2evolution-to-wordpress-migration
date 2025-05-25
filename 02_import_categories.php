<?php
// 02_import_categories.php
// Import b2evolution categories into WordPress

require_once 'wp-load.php';

// Connect to b2evolution DB
$b2evo = new mysqli('localhost', 'b2evo_user', 'b2evo_pass', 'b2evo_db');
if ($b2evo->connect_error) {
    die("Connection failed: " . $b2evo->connect_error);
}

echo "🔄 Importing b2evolution categories into WordPress...\n";

$cat_map = [];
$count = 0;

$query = "
    SELECT cat_ID, cat_name, cat_description
    FROM evo_categories
    WHERE cat_name != ''
";

$result = $b2evo->query($query);

while ($row = $result->fetch_assoc()) {
    $cat_name = trim($row['cat_name']);
    $cat_desc = trim($row['cat_description']);
    $b2_cat_id = (int)$row['cat_ID'];

    // Check if category already exists
    $existing = get_term_by('name', $cat_name, 'category');
    if ($existing) {
        $wp_cat_id = (int)$existing->term_id;
    } else {
        $created = wp_insert_term($cat_name, 'category', [
            'description' => $cat_desc
        ]);

        if (is_wp_error($created)) {
            echo "❌ Error creating category '{$cat_name}': " . $created->get_error_message() . "\n";
            continue;
        }

        $wp_cat_id = (int)$created['term_id'];
        $count++;
    }

    $cat_map[$b2_cat_id] = $wp_cat_id;
}

// Write mapping to file
file_put_contents('cat_map.json', json_encode($cat_map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Imported {$count} new categories.\n";
echo "💾 Saved mapping to cat_map.json\n";
