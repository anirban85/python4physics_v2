<?php
sleep(300); // Wait for 5 minutes (300 seconds)
array_map('unlink', glob("user_1dd9bcb4146d82d98de98a1ffc61e886/*.*")); // Delete all files in the directory
rmdir("user_1dd9bcb4146d82d98de98a1ffc61e886"); // Remove the directory itself
?>