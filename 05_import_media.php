<?php
// 05_import_media.php
// Import b2evolution media files into WordPress Media Library

require_once 'wp-load.php';

$b2evo = new mysqli('localhost', 'b2evo_user', 'b2evo_pass', 'b2evo_db');
if ($b2evo->connect_error) {
    die("Connection failed: " . $b2evo->connect_error);
}

echo "🔄 Importing media files from b2evolution...\n";

$uploads_dir = wp_upload_dir()['basedir'];
$media_root = __DIR__ . '/media'; // assumes b2evo media is available at ./media

$count = 0;
$skipped = 0;

$query = "SELECT * FROM evo_files";
$result = $b2evo->query($query);

while ($row = $result->fetch_assoc()) {
    $b2evo_file_id = (int)$row['file_ID'];

    // Skip if already imported
    $existing = $wpdb->get_var($wpdb->prepare("
        SELECT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = '_b2evo_file_id' AND meta_value = %d
    ", $b2evo_file_id));
    if ($existing) {
        $skipped++;
        continue;
    }

    $rel_path = ltrim($row['file_path'], '/');
    $source_path = $media_root . '/' . $rel_path;

    if (!file_exists($source_path)) {
        echo "⚠️  Missing file: $source_path\n";
        continue;
    }

    // Copy file to WordPress uploads directory
    $filename = basename($source_path);
    $destination_path = $uploads_dir . '/' . $filename;
    if (!copy($source_path, $destination_path)) {
        echo "❌ Failed to copy: $filename\n";
        continue;
    }

    // Prepare file info
    $filetype = wp_check_filetype($filename, null);

    $attachment = [
        'guid'           => wp_upload_dir()['baseurl'] . '/' . $filename,
        'post_mime_type' => $filetype['type'],
        'post_title'     => $row['file_title'] ?: $filename,
        'post_content'   => $row['file_desc'],
        'post_excerpt'   => $row['file_alt'],
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($attachment, $destination_path);

    // Generate metadata
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $destination_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Add reference to original b2evo file ID
    add_post_meta($attach_id, '_b2evo_file_id', $b2evo_file_id);

    $count++;
}

echo "✅ Imported {$count} media file(s).\n";
echo "⏭️ Skipped {$skipped} already imported.\n";
