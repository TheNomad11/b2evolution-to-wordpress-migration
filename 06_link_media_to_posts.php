<?php
// 06_link_media_to_posts.php
// Link imported media attachments to their respective posts

require_once 'wp-load.php';

global $wpdb;

echo "🔄 Linking media files to corresponding posts...\n";

$attachments = $wpdb->get_results("
    SELECT ID, guid
    FROM {$wpdb->posts}
    WHERE post_type = 'attachment'
");

$linked = 0;
$skipped = 0;

foreach ($attachments as $attachment) {
    $filename = basename($attachment->guid);

    // Search posts that mention this filename
    $post_id = $wpdb->get_var($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'post'
          AND post_content LIKE %s
        LIMIT 1
    ", '%' . $wpdb->esc_like($filename) . '%'));

    if ($post_id) {
        // Set attachment parent if not already set
        $current_parent = $wpdb->get_var($wpdb->prepare("
            SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d
        ", $attachment->ID));

        if ((int)$current_parent !== (int)$post_id) {
            $wpdb->update(
                $wpdb->posts,
                ['post_parent' => $post_id],
                ['ID' => $attachment->ID],
                ['%d'],
                ['%d']
            );
            $linked++;
        } else {
            $skipped++;
        }
    }
}

echo "✅ Linked {$linked} media item(s) to posts.\n";
echo "⏭️ Skipped {$skipped} already linked.\n";
