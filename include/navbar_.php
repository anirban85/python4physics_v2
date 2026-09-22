<nav class="navbar">
    <ul class="navbar-list">
        <li><a href="<?php echo $siteurl; ?>index.php">Home</a></li>
		<li><a href="<?php echo $siteurl; ?>python.php">Python</a></li>
        <li><a href="<?php echo $siteurl; ?>latex.php">LaTeX</a></li>
        <li><a href="<?php echo $siteurl; ?>gnuplot.php">GNUPlot</a></li>
        <li><a href="<?php echo $siteurl; ?>arduino.php">Arduino</a></li>
		<li><a href="<?php echo $siteurl; ?>helpdesk.php">Helpdesk</a></li>
		<li><a href="<?php echo $siteurl; ?>contact.php">Contact Us</a></li>
    </ul>
</nav>

<style>
    .navbar {
        background-color: #C70039;
        color: #f7f7f7;
        padding: 10px 20px;
        font-family: Arial, sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .navbar-list {
        list-style-type: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 15px;
    }

    .navbar-list li a {
        text-decoration: none;
        color: #f7f7f7;
        font-size: 16px;
        padding: 10px;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .navbar-list li a:hover {
        background-color: #555;
    }

    .navbar-list li a:active {
        background-color: #333;
    }
</style>
