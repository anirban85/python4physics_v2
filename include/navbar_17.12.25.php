<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Navbar with Google Search</title>
    <style>
        .navbar {
            background-color: #C70039;
            color: #f7f7f7;
            padding: 10px 20px;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .navbar-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 10px;
            flex-grow: 1;
            justify-content: flex-start;
            align-items: center;
        }

        .navbar-list li a {
            text-decoration: none;
            color: #f7f7f7;
            font-size: 16px;
            padding: 12px 16px;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .navbar-list li a:hover {
            background-color: #555;
            transform: scale(1.05);
        }

        .navbar-list li a:active {
            background-color: #333;
        }

        .navbar-toggle {
            display: none;
            background: none;
            border: none;
            color: #f7f7f7;
            font-size: 24px;
            cursor: pointer;
            padding: 10px;
        }

        .navbar-search {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            flex-grow: 1;
            max-width: 300px;
        }

        .navbar-search input {
            padding: 5px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-right: 2px;
            flex-grow: 1;
            width: 100%;
        }

        .navbar-search button {
            padding: 6px 12px;
            font-size: 16px;
            color: white;
            background-color: #555;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .navbar-search button:hover {
            background-color: #333;
        }

        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: row; /* Keep row layout for mobile */
                align-items: center;
                justify-content: space-between; /* Space out items */
                width: 100%;
            }

            .navbar-list {
                display: none;
                flex-direction: column;
                width: 100%;
                text-align: center;
                padding-top: 10px;
            }

            .navbar-list li {
                margin-bottom: 10px;
                width: 100%; /* Make the items take full width */
            }

            .navbar-list li a {
                font-size: 18px; /* Slightly larger font for better readability */
                padding: 14px 20px; /* Increase padding for easier tapping */
                display: block; /* Ensure full width clickable area */
            }

            .navbar-list.active {
                display: flex;
            }

            .navbar-toggle {
                display: block;
            }

            .navbar-search {
                width: auto;
                max-width: none;
                justify-content: flex-end;
                margin-top: 0;
                flex-shrink: 1;
            }

            .gcse-search {
                order: 1; /* Place the search before the menu */
                margin-left: auto;
                width: 50%; /* Adjust width as needed */
            }

            .navbar-toggle {
                font-size: 24px;
                padding: 5px;
            }

            .navbar-list li a:hover {
                background-color: #555;
                color: #fff;
                transform: none;
            }

            .navbar-list li a:active {
                background-color: #900C3F;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?php echo $siteurl; ?>index.php"></a>
        <button class="navbar-toggle" onclick="toggleNavbar()">☰ Menu</button>
    </div>
    <div class="navbar-content">
        <!-- Google Custom Search Start -->
        <div class="gcse-search"></div>
        <!-- Google Custom Search End -->
        <ul class="navbar-list">
            <li><a href="<?php echo $siteurl; ?>"><strong>Home</strong></a></li>
            <li><a href="<?php echo $siteurl; ?>program/python/index.php">Python</a></li>
            <li><a href="<?php echo $siteurl; ?>program/latex/index.php">LaTeX</a></li>
            <li><a href="<?php echo $siteurl; ?>program/gnuplot/index.php">GNUPlot</a></li>
            <li><a href="<?php echo $siteurl; ?>feedback.php">Feedback</a></li>
            <li><a href="<?php echo $siteurl; ?>contact.php">Contact Us</a></li>
        </ul>
    </div>
</nav>

<script async src="https://cse.google.com/cse.js?cx=47a616d83f2404c0f"></script>

<script>
    function toggleNavbar() {
        const navbarList = document.querySelector('.navbar-list');
        navbarList.classList.toggle('active');
    }
</script>

</body>
</html>
