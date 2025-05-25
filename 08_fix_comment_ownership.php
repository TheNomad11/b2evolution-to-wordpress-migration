<?php
require_once 'wp-load.php';

echo "🔄 Fixing imported comments authored by registered users...\n";

global $wpdb;
$b2evo_db = new wpdb('b2evo_user', 'b2evo_pass', 'b2evo_db', 'localhost');

$fixed = 0;

$results = $wpdb->get_results("
    SELECT c.comment_ID, m.meta_value AS b2evo_comment_id
    FROM {$wpdb->comments} c
    JOIN {$wpdb->commentmeta} m ON c.comment_ID = m.comment_id
    WHERE m.meta_key = '_b2evo_comment_id' AND c.user_id = 0
");

foreach ($results as $row) {
    $author_user_id = $b2evo_db->get_var($b2evo_db->prepare(
        "SELECT comment_author_user_ID FROM evo_comments WHERE comment_ID = %d",
        $row->b2evo_comment_id
    ));

    if ($author_user_id) {
        $user_login = $b2evo_db->get_var($b2evo_db->prepare(
            "SELECT user_login FROM evo_users WHERE user_ID = %d",
            $author_user_id
        ));

        if ($user_login) {
            $wp_user = get_user_by('login', $user_login);
            if ($wp_user) {
                $wpdb->update(
                    $wpdb->comments,
                    ['user_id' => $wp_user->ID],
                    ['comment_ID' => $row->comment_ID]
                );
                $fixed++;
            }
        }
    }
}

echo "✅ Fixed $fixed comment(s) authored by registered users.\n";
