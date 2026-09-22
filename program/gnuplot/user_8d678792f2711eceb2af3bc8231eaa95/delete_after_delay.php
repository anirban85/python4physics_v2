<?php
sleep(300); // Wait for 5 minutes (300 seconds)
array_map('unlink', glob("user_8d678792f2711eceb2af3bc8231eaa95/*.*")); // Delete all files in the directory
rmdir("user_8d678792f2711eceb2af3bc8231eaa95"); // Remove the directory itself
?>