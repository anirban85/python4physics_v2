<?php
include '../../site_config.php';
include '../../db.php';

// Start session to manage the last output ID
session_start();

// Fetch menu_id and submenu_id from GET parameters
$menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;  // Default to 1 if not provided
$submenu_id = isset($_GET['submenu_id']) ? intval($_GET['submenu_id']) : 1;  // Default to 1 if not provided

// Include menus
include 'menu.php';

// Determine the header text
$header_text = 'Interactive Python Programs';
$submenu_titles_for_menu = $sub_menu_titles[$menu_id] ?? [];
$header_text = $submenu_titles_for_menu[$submenu_id] ?? $header_text;

// Fetch the programs for the given menu_id and submenu_id
$sql = "SELECT id, program_id, content FROM latex WHERE menu_id = ? AND submenu_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing the statement: " . $conn->error);
}

$stmt->bind_param("ii", $menu_id, $submenu_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $programs = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Error executing query: " . $stmt->error);
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($header_text); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/stex/stex.min.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #eaeaea;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #333;
            color: #f7f7f7;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        main {
            display: flex;
            flex-wrap: wrap;
            padding: 10px;
            background-color: #ffffff;
            margin: 0 auto;
            max-width: calc(100% - 20px);
        }
        .sidebar {
            width: 250px;
            padding: 15px;
            background-color: #f4f4f4;
            border-right: 1px solid #ddd;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px 0 0 8px;
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
        }
        .content {
            flex: 1;
            padding: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .program {
            flex: 1;
            min-width: 400px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-sizing: border-box;
            overflow: hidden;
        }
        textarea {
            width: calc(100% - 5px);
            height: 200px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px; /* Increased font size */
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            resize: vertical;
            background-color: #fdfdfd;
        }
        button {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        pre {
            background-color: #f1f1f1;
            padding: 15px;
            border-left: 4px solid #007bff;
            white-space: pre-wrap;
            word-wrap: break-word;
            border-radius: 4px;
            overflow-y: auto;
            font-size: 18px; /* Increased font size */
        }
        h3 {
            margin-top: 0;
            font-size: 20px;
            color: #333;
        }
        img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 4px;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            position: relative;
            padding-left: 30px;
            margin-bottom: 10px;
        }
        .sidebar ul li::before {
            content: '\2713';
            position: absolute;
            left: 0;
            color: #007bff;
            font-size: 20px;
            line-height: 1;
            top: 50%;
            transform: translateY(-50%);
        }
        .sidebar ul li a {
            text-decoration: none;
            color: #007bff;
            font-size: 16px;
        }
        .sidebar ul li a:hover {
            text-decoration: underline;
        }
        .sidebar ul li.active::before {
            color: #28a745;
        }
        .CodeMirror {
            font-size: 16px; /* Increase font size */
            max-height: 600px; /* Maximum height constraint */
            overflow: hidden; /* Hide overflow to respect max-height */
        }
		.pdf-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .pdf-container > div {
            flex: 1;
            min-width: 350px; /* Increased minimum width */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .pdf-container iframe {
            width: 100%;
            height: 600px; /* Increased height */
            border: none;
        }
		@media only screen and (max-width: 768px) and (orientation: portrait) {
            .sidebar {
                position: static;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
                border-radius: 0;
                height: auto;
                box-shadow: none;
            }
			.CodeMirror {
				max-width: 340px;
				max-height: 600px;
				font-size: 11px;
				padding: 4px;
			}
            .content {
                padding: 5px;
            }
        }
        @media only screen and (max-width: 1268px) and (orientation: landscape) {
            .sidebar {
                position: static;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ddd;
                border-radius: 0;
                height: auto;
                box-shadow: none;
            }
			.CodeMirror {
				max-width: 800px;
				max-height: 600px;
				font-size: 12px;
				padding: 4px;
			}
            .content {
                padding: 5px;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/addon/selection/active-line.min.js"></script>
	<script>
    function scrollToLastExecutedCode() {
        var lastOutputId = "<?php echo isset($_SESSION['last_output_id']) ? $_SESSION['last_output_id'] : ''; ?>";
        if (lastOutputId) {
            var outputElement = document.getElementById(lastOutputId);
            if (outputElement) {
                outputElement.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    function initCodeMirror() {
        document.querySelectorAll('textarea').forEach(function(textarea) {
            var editor = CodeMirror.fromTextArea(textarea, {
                mode: 'stex', // 'stex' is used for LaTeX syntax highlighting
                theme: 'monokai',
                lineNumbers: true,
                styleActiveLine: true,
                matchBrackets: true,
                autoCloseBrackets: true, // Automatically close brackets, useful for LaTeX
                showHint: true, // Show hints for LaTeX commands
                extraKeys: {
                    "Ctrl-Space": "autocomplete", // Autocomplete commands
                    "Cmd-Space": "autocomplete"
                }
            });

            // Adjust the height based on the number of lines
            editor.on('change', function() {
                adjustEditorHeight(editor);
            });

            // Initial height adjustment
            adjustEditorHeight(editor);
        });
    }

    function adjustEditorHeight(editor) {
        var lineCount = editor.lineCount();
        var lineHeight = editor.defaultTextHeight();
        var newHeight = Math.min(lineCount * lineHeight + 100, 500); // Maximum height of 500px
        editor.getWrapperElement().style.height = newHeight + 'px';
    }

    window.onload = function() {
        scrollToLastExecutedCode();
        initCodeMirror();
    };
</script>

</head>
<body>
<?php include '../../include/navbar.php'; ?>
<header class='secTitle'>
    <?php echo htmlspecialchars($header_text); ?>
</header>

<main>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content">
        <?php
        // Prevent caching
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
        header("Pragma: no-cache"); // HTTP 1.0.
        header("Expires: 0"); // Proxies.

        foreach ($programs as $program) {
            $id = $program['id'];
            $program_id = $program['program_id'];
            $content = $program['content'];
            $code_name = "latex_code$id";
            $run_name = "run_code$id";
            $output_id = "output$id";

            echo "<div class='program'>
                <h3>Program $program_id</h3>
                <form method='POST'>
                    <textarea name='$code_name'>". htmlspecialchars(isset($_POST[$code_name]) ? $_POST[$code_name] : $content) ."</textarea>
                    <button type='submit' name='$run_name'>Run Code</button>
                </form>
            </div>
            <div class='program'>
                <h3 id='$output_id'>Output $program_id</h3>";
                if (isset($_POST[$run_name])) {
                    $latex_code = $_POST[$code_name];

                    // API URL for LaTeX Online
                    $api_url = 'https://latexonline.cc/compile';

                    // URL-encode the LaTeX code
                    $encoded_latex_code = urlencode($latex_code);

                    // Construct the full API request URL
                    $full_url = "$api_url?text=$encoded_latex_code&format=pdf";

                    // Initialize cURL session
                    $ch = curl_init();

                    // Set cURL options
                    curl_setopt($ch, CURLOPT_URL, $full_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HEADER, true); // Get the header as well as the content

                    // Execute cURL request
                    $response = curl_exec($ch);

                    // Get the HTTP status code
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    // Separate header and body
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $body = substr($response, $header_size);

                    // Close cURL session
                    curl_close($ch);

                    if ($http_code == 200) {
                        // Save the PDF file
                        $pdf_file = 'document.pdf';
                        file_put_contents($pdf_file, $body);
                     
                        echo "<div class='pdf-container'>";
                        // Display the PDF
                        echo "<iframe src='$pdf_file' frameborder='0'></iframe>";
                        echo "</div>";
                    } else {
                        // Display the error message
                        echo "<div class='error-message'>";
                        echo "<h2>Error generating PDF.</h2>";
                        echo "<h3>Error Details</h3>";
                        echo "<p>HTTP Status Code: $http_code</p>";
                        echo "<p>Response: " . htmlspecialchars($body) . "</p>";
                        echo "</div>";
                    }
                }
            echo "</div>";
        }
        ?>
    </div>
</main>
<?php include '../../include/footer.php'; ?>

</body>
</html>
