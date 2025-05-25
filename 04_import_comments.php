<?php
// 04_import_comments.php
// Import published comments from b2evolution into WordPress

require_once 'wp-load.php';

$b2evo = new mysqli('localhost', 'b2evo_user', 'b2evo_pass', 'b2evo_db');
if ($b2evo->connect_error) {
    die("Connection failed: " . $b2evo->connect_error);
}

echo "🔄 Importing published comments from b2evolution...\n";

// Get WordPress post map: b2evo_post_id → wp_post_id
$post_map = [];
$results = $wpdb->get_results("
    SELECT post_id, meta_value AS b2evo_post_id
    FROM {$wpdb->postmeta}
    WHERE meta_key = '_b2evo_post_id'
");
foreach ($results as $row) {
    $post_map[(int)$row->b2evo_post_id] = (int)$row->post_id;
}

// Get WordPress user map: b2evo_user_login → wp_user_id
$wp_users = [];
$wp_user_results = $wpdb->get_results("SELECT ID, user_login FROM {$wpdb->users}");
foreach ($wp_user_results as $user) {
    $wp_users[strtolower($user->user_login)] = (int)$user->ID;
}

$count = 0;
$skipped = 0;

$query = "
    SELECT 
        c.comment_ID,
        c.comment_item_ID,
        c.comment_author_user_ID,
        c.comment_author,
        c.comment_author_email,
        c.comment_author_url,
        c.comment_author_IP,
        c.comment_date,
        c.comment_content
    FROM evo_comments c
    WHERE c.comment_status = 'published'
";

$result = $b2evo->query($query);

while ($row = $result->fetch_assoc()) {
    $b2_comment_id = (int)$row['comment_ID'];

    // Skip if already imported
    $exists = $wpdb->get_var($wpdb->prepare("
        SELECT comment_id FROM {$wpdb->commentmeta}
        WHERE meta_key = '_b2evo_comment_id' AND meta_value = %d
    ", $b2_comment_id));

    if ($exists) {
        $skipped++;
        continue;
    }

    $b2_post_id = (int)$row['comment_item_ID'];
    if (!isset($post_map[$b2_post_id])) {
        continue;
    }

    $post_id = $post_map[$b2_post_id];

    // Detect if comment author is a registered user
    $user_id = 0;
    $b2_user_id = (int)$row['comment_author_user_ID'];
    if ($b2_user_id > 0) {
        $user_login_result = $b2evo->query("SELECT user_login FROM evo_users WHERE user_ID = $b2_user_id LIMIT 1");
        if ($user_login_result && $u = $user_login_result->fetch_assoc()) {
            $login = strtolower($u['user_login']);
            if (isset($wp_users[$login])) {
                $user_id = $wp_users[$login];
            }
        }
    }

    $commentdata = [
        'comment_post_ID'      => $post_id,
        'comment_author'       => $row['comment_author'],
        'comment_author_email' => $row['comment_author_email'],
        'comment_author_url'   => $row['comment_author_url'],
        'comment_author_IP'    => $row['comment_author_IP'],
        'comment_date'         => $row['comment_date'],
        'comment_date_gmt'     => get_gmt_from_date($row['comment_date']),
        'comment_content'      => $row['comment_content'],
        'comment_approved'     => 1,
        'user_id'              => $user_id,
    ];

    $comment_id = wp_insert_comment($commentdata);

    if ($comment_id) {
        add_comment_meta($comment_id, '_b2evo_comment_id', $b2_comment_id);
        $count++;
    }
}

echo "✅ Imported {$count} comments.\n";
echo "⏭️ Skipped {$skipped} comments already imported.\n";
