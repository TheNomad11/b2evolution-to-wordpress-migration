# b2evolution to Wordpress Migration
How to migrate from b2evolution to Wordpress step by step. 

Place these scripts in the root folder of your Wordpress installation and run them in the command line: 
php 01_import_users.php (recommended) 
or by using the browser.

Here you have to enter your credentials: 

$b2evo = new mysqli('localhost', 'b2evo_user', 'b2evo_pass', 'b2evo_db');

I migrated successfully with the help by Chatgpt as no migration scripts were available. It took many hours and lots of corrections, testing, corrections. In the end I had many corrected scripts, many versions, here are the updated and corrected versions (I hope at least). Let me know if you have any issues!
