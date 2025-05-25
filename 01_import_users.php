<?php
// 01_import_users.php
// Import b2evolution users into WordPress

require_once 'wp-load.php';

// DB connection for b2evolution
$b2evo = new mysqli('localhost', 'b2evo_user', 'b2evo_pass', 'b2evo_db');
if ($b2evo->connect_error) {
    die("Connection failed: " . $b2evo->connect_error);
}

echo "🔄 Importing users from b2evolution...\n";

$count = 0;

$result = $b2evo->query("
    SELECT user_ID, user_login, user_email, user_firstname, user_lastname, user_nickname, user_url
    FROM evo_users
    WHERE user_email != '' AND user_login != ''
");

while ($row = $result->fetch_assoc()) {
    $user_login = sanitize_user($row['user_login']);
    $user_email = sanitize_email($row['user_email']);

    // Check if user already exists
    if (username_exists($user_login) || email_exists($user_email)) {
        continue;
    }

    $userdata = [
        'user_login'    => $user_login,
        'user_pass'     => wp_generate_password(),
        'user_email'    => $user_email,
        'first_name'    => $row['user_firstname'],
        'last_name'     => $row['user_lastname'],
        'display_name'  => $row['user_nickname'] ?: $row['user_login'],
        'user_url'      => $row['user_url'],
        'role'          => 'author',
    ];

    $wp_user_id = wp_insert_user($userdata);

    if (!is_wp_error($wp_user_id)) {
        $count++;
    }
}

echo "✅ Imported {$count} new user(s).\n";
