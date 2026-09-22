
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
$header_text = 'Interactive GNUPlot Programs';
$menu_text = $menu_titles[$menu_id];
$submenu_titles_for_menu = $sub_menu_titles[$menu_id] ?? [];
$header_text = $submenu_titles_for_menu[$submenu_id] ?? $header_text;

// Fetch the programs for the given menu_id and submenu_id
$sql = "SELECT id, program_id, content, algo, explanation FROM gnuplot WHERE menu_id = ? AND submenu_id = ?";
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
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-NRDD47HL');</script>
    <!-- End Google Tag Manager -->
	<!-- Google Ad start -->
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1857733312974112"
     crossorigin="anonymous"></script>
	<!-- Google Ad End -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gnuplot for easy and powerful data visualization">
    <meta name="keywords" content="<?php echo htmlspecialchars($header_text); ?>">
    <title><?php echo htmlspecialchars($menu_text); ?>: <?php echo htmlspecialchars($header_text); ?></title>
    <link rel="icon" href="gnuplot.ico" type="image/x-icon">
    <link rel="shortcut icon" href="gnuplot.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/theme/monokai.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f8ff;
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
            background-color: #343a40;
			color: #ffffff;
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
            min-width: calc(100% - 10px);
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
        h1 {
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
            content: '\2714';
            position: absolute;
            left: 0;
            color: #ffffff;
            font-size: 20px;
            line-height: 1;
            top: 50%;
            transform: translateY(-50%);
        }
        .sidebar ul li a {
            text-decoration: none;
            color: #ffffff;
            font-size: 16px;
        }
        .sidebar ul li a:hover {
            text-decoration: underline;
			color: #ffffff;
        }
        .sidebar ul li.active::before {
            color: #28a745;
        }
        .CodeMirror {
            font-size: 16px; /* Increase font size */
            max-height: 900px; /* Maximum height constraint */
			min-width: 340px;
            overflow: hidden; /* Hide overflow to respect max-height */
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
				max-width: 280px;
				font-size: 11px;
				padding: 4px;
			}
            .content {
                padding: 5px;
            }
        }
        @media only screen and (max-width: 1168px) and (orientation: landscape) {
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
				min-width: 340px;
				max-width: 550px;
				font-size: 12px;
				padding: 4px;
			}
			.program {
            flex: 1;
            min-width: 350px;
			max-width: 550px;
			}
            .content {
                padding: 5px;
            }
        }
		@media only screen and (min-width: 1170px) and (orientation: landscape) {
            
			.CodeMirror {
			    flex: 1;
				min-width: 400px;
				max-width: 1800px;
				font-size: 15px;
				padding: 4px;
			}
            .content {
                padding: 5px;
            }
			.program {
            flex: 1;
            min-width: 400px;
			max-width: 1800px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-sizing: border-box;
            overflow: hidden;
			}
            
		}
		@media only screen and (min-width: 1550px) and (orientation: landscape) {
            
			.CodeMirror {
			    flex: 1;
				min-width: 600px;
				max-width: 1800px;
				font-size: 18px;
				padding: 4px;
			}
            .content {
                padding: 5px;
            }
			.program {
            flex: 1;
            min-width: 600px;
			max-width: 1800px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-sizing: border-box;
            overflow: hidden;
			}
            
		}
		.font-size-selector {
            margin-bottom: 5px;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/mode/shell/shell.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/addon/selection/active-line.min.js"></script>
<script>
    let editors = []; // Store CodeMirror editors

    function initCodeMirror(fontSize) {
        document.querySelectorAll('textarea').forEach(function(textarea) {
            var editor = CodeMirror.fromTextArea(textarea, {
                mode: 'shell',
                theme: 'monokai',
                lineNumbers: true,
                styleActiveLine: true,
                matchBrackets: true,
                lineWrapping: true,
                scrollbarStyle: null,
                extraKeys: { "Ctrl-Space": "autocomplete" },
                viewportMargin: Infinity,
            });

            // Set the initial font size
            editor.getWrapperElement().style.fontSize = fontSize + 'px';
            editors.push(editor); // Store editor instance

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

    function updateFontSize() {
        // Get selected font size
        var fontSize = document.getElementById('fontSizeSelect').value;
        editors.forEach(function(editor) {
            editor.getWrapperElement().style.fontSize = fontSize + 'px';
        });
        
        // Save the selected font size in sessionStorage
        sessionStorage.setItem('selectedFontSize', fontSize);
    }

    window.onload = function() {
    // Check for saved font size
    var savedFontSize = sessionStorage.getItem('selectedFontSize') || '14'; // Default font size if none is saved

    // Set the font size selector value to the saved value
    document.getElementById('fontSizeSelect').value = savedFontSize;

    // Initialize CodeMirror with the selected or default font size
    initCodeMirror(savedFontSize);
	};

</script>

</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NRDD47HL"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php include '../../include/navbar.php'; ?>
<header class='Title'>
    <?php echo htmlspecialchars($menu_text); ?>: <?php echo htmlspecialchars($header_text); ?>
</header>

<main>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content">
        <?php
        session_start(); // Start the session

        // Prevent caching
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Initialize an array in session to track generated images
        if (!isset($_SESSION['generated_images'])) {
            $_SESSION['generated_images'] = [];
        }

        // Display and delete previously generated images if the page is reloaded or redirected
        foreach ($_SESSION['generated_images'] as $image_file) {
            if (file_exists($image_file)) {
                unlink($image_file); // Delete the image after displaying it
            }
        }

        // Clear the session array after displaying the images
        $_SESSION['generated_images'] = [];

        // Flag to check if a program has been run
        $program_run = false;

        // Assume $programs is defined and populated elsewhere in your script
        foreach ($programs as $program) {
            $id = $program['id'];
            $program_id = $program['program_id'];
            $content = $program['content'];
            $algo = $program['algo'];
            $explanation = $program['explanation'];
            $code_name = "gnuplot_code$id";
            $run_name = "run_code$id";
            $output_id = "output$id";

            echo "<div class='program'>
                <h1>$header_text: Program $program_id</h1>
                <a> $algo </a>
                <form method='POST'>
                    <textarea name='$code_name'>" . htmlspecialchars($_POST[$code_name] ?? $content) . "</textarea>
                    <button type='submit' name='$run_name'>Run Code</button>
                </form>
            </div>
            <div class='program'>
                <h1 id='$output_id'>Output $program_id</h1>";

            if (isset($_POST[$run_name])) {
                $program_run = true; // Mark that a program was run
                $_SESSION['last_output_id'] = $output_id; // Store the last output ID in session

                $gnuplot_code = $_POST[$code_name];
                $tmp_file = tempnam(sys_get_temp_dir(), 'gnuplot_code_') . '.gp';
                file_put_contents($tmp_file, $gnuplot_code);

                // Extract the image file name from the 'set output' command
                preg_match("/set\s+output\s*['\"]([^'\"]+)['\"]/", $gnuplot_code, $matches);
                $image_file = $matches[1] ?? '';

                // Use unique filenames for generated images
                $user_dir = "user_" . session_id();
                if (!file_exists($user_dir)) {
                    mkdir($user_dir);
                }

                if ($image_file) {
                    // Extract the file extension from $image_file
                    $extension = pathinfo($image_file, PATHINFO_EXTENSION);

                    // Generate a unique ID
                    $unique_id = uniqid();

                    // Create the unique image filename with the same extension
                    $unique_image = "$user_dir/image_$unique_id.$extension";

                    // Replace the original output file name with the unique one in the Gnuplot code
                    $gnuplot_code = str_replace($image_file, $unique_image, $gnuplot_code);
                    file_put_contents($tmp_file, $gnuplot_code);

                    if (file_exists($tmp_file)) {
                        // Execute the Gnuplot script with a timeout of 15 seconds
                        $output = shell_exec("timeout 15s ~/gnuplot/bin/gnuplot $tmp_file 2>&1");
                        
                        // Check if the command was terminated due to timeout or failed
                        $exit_code = shell_exec("echo $?");
                        
                        if ($exit_code == 124) {
                            echo "<script>alert('Program is taking too much time to execute. Adjust the parameters or run locally.');</script>";
                        } elseif (!file_exists($unique_image)) {
                            echo "<pre>Gnuplot command did not generate the expected output file. Error: $output</pre>";
                        } else {
                            // Store the image filename in session for deletion later
                            $_SESSION['generated_images'][] = $unique_image;

                            // Use JavaScript to display the image or GIF
                            echo "<script>
                                    var img = document.createElement('img');
                                    img.src = '$unique_image?ts=' + new Date().getTime();
                                    document.getElementById('$output_id').appendChild(img);
                                  </script>";
                        }
                    } else {
                        echo "<pre>Failed to execute Gnuplot code.</pre>";
                    }

                    unlink($tmp_file); // Clean up the temporary Gnuplot file
                } else {
                    echo "<pre>Failed to find 'set output' in the Gnuplot code.</pre>";
                }

                // Schedule directory deletion after 5 minutes
                $delete_script = <<<EOD
                <?php
                sleep(300); // Wait for 5 minutes (300 seconds)
                array_map('unlink', glob("$user_dir/*.*")); // Delete all files in the directory
                rmdir("$user_dir"); // Remove the directory itself
                ?>
                EOD;

                $delete_script_file = "$user_dir/delete_after_delay.php";
                file_put_contents($delete_script_file, $delete_script);
                shell_exec("php $delete_script_file > /dev/null 2>&1 &");
            }

            echo "</div>";
            echo "<div style=\"width: 100%;\"><a> $explanation </a></div>";
        }
        ?>

        <script>
            // Scroll to the top on first load
            if (!<?php echo json_encode($program_run); ?>) {
                window.scrollTo(0, 0);
            } else {
                // Scroll to the last output section after execution
                var lastOutputId = <?php echo json_encode($_SESSION['last_output_id'] ?? null); ?>;
                if (lastOutputId) {
                    document.getElementById(lastOutputId).scrollIntoView();
                }
            }
        </script>
    </div>
</main>
<!-- Ad start -->

<!-- Ad end -->
<?php include '../../include/footer.php'; ?>

</body>
</html>