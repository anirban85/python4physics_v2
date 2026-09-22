<?php
// Initialize or update visitor counter
$counterFile = "counter.txt";
if (file_exists($counterFile)) {
    $counter = file_get_contents($counterFile);
    $counter++;
} else {
    $counter = 1;
}
file_put_contents($counterFile, $counter);
?>

<footer style="background-color: #343a40; padding: 30px; text-align: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 16px; color: #f8f9fa;">
    <p style="margin: 0; padding: 0; font-weight: 500;">&copy; 2024 All Rights Reserved</p>
    <p style="margin: 0; padding: 5px 0;">Dr. Alorika Chatterjee & Dr. Anirban Shaw</p>
    <p style="margin: 0; font-size: 14px; color: #adb5bd;">Designed with care and professionalism</p>
    <p style="margin: 20px 0 0; font-size: 14px; color: #f8f9fa;">Visitor Count: <?php echo $counter; ?></p>
</footer>
