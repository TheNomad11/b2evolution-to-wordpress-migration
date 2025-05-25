# b2evolution to Wordpress Migration
How to migrate from b2evolution to Wordpress step by step. 

Place these scripts in the root folder of your Wordpress installation and run them in the command line: 
php 01_import_users.php (recommended) 
or by using the browser.

Here you have to enter your credentials: 

$b2evo = new mysqli('localhost', 'b2evo_user', 'b2evo_pass', 'b2evo_db');

I migrated successfully with the help by Chatgpt as no migration scripts were available. It took many hours and lots of corrections, testing, corrections. In the end I had many corrected scripts, many versions, here are the updated and corrected versions (I hope at least). Let me know if you have any issues!


## Finalized Script Sequence (Complete)

| #    | Script Name                               | Purpose                                                      |
| ---- | ----------------------------------------- | ------------------------------------------------------------ |
| 01   | `01_import_users.php`                     | Imports active b2evo users (from `evo_users`) into WordPress, avoiding overwrites. |
| 02   | `02_import_collections.php`               | Imports b2evo collections (`evo_blogs`) as top-level WordPress categories. |
| 03   | `03_import_posts.php`                     | Imports only published posts from `evo_items__item`, assigning main collection category. |
| 04   | `04_import_categories.php`                | Imports b2evo categories (`evo_categories`) into WordPress. Stores `cat_map.json`. |
| 05   | `05_import_comments.php`                  | Imports only **published** comments. Tracks via `_b2evo_comment_id`. |
| 06   | `06_link_media_to_posts.php`              | Connects media files (in `/media/`) already loaded externally, linking them to posts. |
| 07   | `07_link_additional_categories.php`       | Links posts to additional b2evo categories (from `evo_postcats`) beyond the main category. |
| 08   | `08_fix_comment_ownership.php`            | Ensures WordPress comment `user_id` is assigned based on `comment_author_user_ID`. |
| 09   | `09_assign_categories_to_collections.php` | Optionally sets each imported category’s parent to its collection (based on slug). Supports `--dry-run`. |
