<?php include 'site_config.php'; ?>

<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include 'db.php'; // This file should define $conn

// Check if the database connection is established
if (!$conn) {
    die("Database connection failed.");
}

// Check if the search form is submitted
if (isset($_GET['query'])) {
    $search = trim($_GET['query']); // Get the search input

    // SQL queries to search across all three tables: 'python', 'latex', 'gnuplot'
    $sql_python = "SELECT 'python' AS source, menu_id, submenu_id, content, algo, explanation FROM python 
                   WHERE content LIKE ? 
                   OR algo LIKE ? 
                   OR explanation LIKE ?";

    $sql_latex = "SELECT 'latex' AS source, menu_id, submenu_id, content, algo, explanation FROM latex 
                  WHERE content LIKE ? 
                  OR algo LIKE ? 
                  OR explanation LIKE ?";

    $sql_gnuplot = "SELECT 'gnuplot' AS source, menu_id, submenu_id, content, algo, explanation FROM gnuplot 
                    WHERE content LIKE ? 
                    OR algo LIKE ? 
                    OR explanation LIKE ?";

    // Combine the results of all three queries using UNION ALL
    $combined_sql = "($sql_python) UNION ALL ($sql_latex) UNION ALL ($sql_gnuplot)";

    try {
        $stmt = $conn->prepare($combined_sql);
        $searchTerm = '%' . $search . '%';
        $stmt->execute(array_fill(0, 9, $searchTerm));
        
        if ($stmt->rowCount() > 0) {
            // Include the navbar at the top
            include 'include/navbar.php';

            // Display the search results
            echo "<h3>Search Results for: '" . htmlspecialchars($search) . "'</h3>";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Determine the source and generate the appropriate URL based on the table
                $source = $row['source'];
                if ($source == 'python') {
                    $page_url = "https://python4physics.in/program/python/program.php?menu_id=" . $row['menu_id'] . "&submenu_id=" . $row['submenu_id'];
                } elseif ($source == 'latex') {
                    $page_url = "https://python4physics.in/program/latex/program.php?menu_id=" . $row['menu_id'] . "&submenu_id=" . $row['submenu_id'];
                } elseif ($source == 'gnuplot') {
                    $page_url = "https://python4physics.in/program/gnuplot/program.php?menu_id=" . $row['menu_id'] . "&submenu_id=" . $row['submenu_id'];
                }

                // Ensure the values are not null before using substr
                $content = !is_null($row['content']) ? substr($row['content'], 0, 150) : 'No content available';
                $algo = !is_null($row['algo']) ? substr($row['algo'], 0, 150) : 'No algorithm available';
                $explanation = !is_null($row['explanation']) ? substr($row['explanation'], 0, 150) : 'No explanation available';

                // Display the result with a link to the appropriate page
                echo "<div>";
                echo "<h4><a href='$page_url' target='_blank'>View $source Page: Menu ID " . htmlspecialchars($row['menu_id']) . " - Submenu ID " . htmlspecialchars($row['submenu_id']) . "</a></h4>";
                echo "<p><strong>Content Preview:</strong> " . htmlspecialchars($content) . "...</p>"; // Show a preview of the content
                echo "<p><strong>Algorithm Preview:</strong> " . htmlspecialchars($algo) . "...</p>"; // Show a preview of the algorithm
                echo "<p><strong>Explanation Preview:</strong> " . htmlspecialchars($explanation) . "...</p>"; // Show a preview of the explanation
                echo "</div><hr>";
            }
        } else {
            echo "<p>No results found for '" . htmlspecialchars($search) . "'</p>";
        }
        include 'include/footer.php';
    } catch(PDOException $e) {
        echo "<p>SQL Error: " . $e->getMessage() . "</p>";
    }
}

// Close the database connection
$conn = null;
?>
