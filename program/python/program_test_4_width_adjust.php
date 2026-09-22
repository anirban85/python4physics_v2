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
$menu_text = $menu_titles[$menu_id];
$submenu_titles_for_menu = $sub_menu_titles[$menu_id] ?? [];
$header_text = $submenu_titles_for_menu[$submenu_id] ?? $header_text;

// Fetch the programs for the given menu_id and submenu_id
$sql = "SELECT id, program_id, content, algo, explanation FROM python WHERE menu_id = ? AND submenu_id = ? ORDER BY program_id";
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
<!-- Google auto Ad start--->
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9571171868366621"
     crossorigin="anonymous"></script>
<!-- Google auto Ad end--->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Master computational physics with Python">
    <meta name="keywords" content="<?php echo htmlspecialchars($header_text); ?>">
    <title><?php echo htmlspecialchars($menu_text); ?>: <?php echo htmlspecialchars($header_text); ?></title>
	<link rel="icon" href="python.ico" type="image/x-icon">
    <link rel="shortcut icon" href="python.ico" type="image/x-icon">
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
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
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

			width: 100%;
            overflow: hidden; /* Hide overflow to respect max-height */
        }
		/* Ensure consistent styling for both program and output sections */
.program, .output {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Style for the text areas and code section */
textarea {
    width: 100%;
    height: 200px;
    font-family: 'Courier New', Courier, monospace;
    font-size: 16px;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    resize: vertical;
}

/* Button style */
button {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #0056b3;
}

/* Ensure proper display of images and outputs */
img {
    max-width: 100%;
    height: auto;
    display: block;
    border-radius: 4px;
    margin-top: 10px;
}

pre {
    background-color: #f1f1f1;
    padding: 15px;
    border-left: 4px solid #007bff;
    white-space: pre-wrap;
    word-wrap: break-word;
    border-radius: 4px;
    overflow-y: auto;
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
			.content {
                padding: 5px;
            }
        }
        /* CSS for the loading spinner */
        #loading-spinner {
            display: none; /* Hidden by default */
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000; /* Ensure it's on top of everything */
            width: 50px;
            height: 50px;
            border: 6px solid #f3f3f3;
            border-radius: 50%;
            border-top: 6px solid #3498db;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.0/addon/selection/active-line.min.js"></script>
    <script>
        
        function initCodeMirror() {
            document.querySelectorAll('textarea').forEach(function(textarea) {
                var editor = CodeMirror.fromTextArea(textarea, {
                    mode: 'python',
                    theme: 'monokai',
                    lineNumbers: true,
                    styleActiveLine: true,
                    matchBrackets: true,
					lineWrapping: true,  // Enable line wrapping
					scrollbarStyle: null // Disable horizontal scrolling
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
            var newHeight = Math.min(lineCount * lineHeight+70, 500); // Maximum height of 500px
            editor.getWrapperElement().style.height = newHeight + 'px';
        }

        window.onload = function() {
            initCodeMirror();
			adjustEditorHeight(editor);
        };
    </script>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NRDD47HL"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php include '../../include/navbar.php'; ?>
<header class='secTitle'>
    <?php echo htmlspecialchars($menu_text); ?>: <?php echo htmlspecialchars($header_text); ?>
</header>

<main>
    <!-- Loading spinner -->
    <div id="loading-spinner"></div>

    <aside class="col-12 col-md-4 col-lg-3 sidebar">
        <?php include 'sidebar.php'; ?>
    </aside>

    <div class="col-12 col-md-8 col-lg-9 content">
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

		foreach ($programs as $program) {
    $id = $program['id'];
    $program_id = $program['program_id'];
    $content = $program['content'];
    $algo = $program['algo'];
    $explanation = $program['explanation'];
    $code_name = "python_code$id";
    $run_name = "run_code$id";
    $output_id = "output$id";

    // Program and Output Section
    echo "<div class='row'>
            <div class='col-12 col-md-4 col-lg-5 program'>
                <h1>Program $program_id</h1>
                <a>$algo</a>
                <form method='POST'>
                    <textarea name='$code_name'>" . htmlspecialchars($_POST[$code_name] ?? $content) . "</textarea>
                    <button type='submit' name='$run_name'>Run Code</button>
                </form>
            </div>
          

            <div class='col-12 col-md-4 col-lg-4 program'>
                <h1 id='$output_id'>Output for Program $program_id</h1>";

    if (isset($_POST[$run_name])) {
        $_SESSION['last_output_id'] = $output_id;

        $python_code = $_POST[$code_name];
        $tmp_file = tempnam(sys_get_temp_dir(), 'python_code_') . '.py';
        file_put_contents($tmp_file, $python_code);

        // Extract all image file names from 'savefig' and 'ani.save' commands
        preg_match_all("/(?:savefig|ani\.save)\s*\(\s*[\"'](.*?)[\"']\s*\)/", $python_code, $matches);
        $image_files = $matches[1] ?? [];

        // Ensure unique filenames for generated images
        $user_dir = "user_" . session_id();
        if (!file_exists($user_dir)) {
            mkdir($user_dir);
        }

        $unique_images = [];
        foreach ($image_files as $image_file) {
            $extension = pathinfo($image_file, PATHINFO_EXTENSION);
            $unique_id = uniqid();
            $unique_images[$image_file] = "$user_dir/image_$unique_id.$extension";
        }

        foreach ($unique_images as $original => $unique_image) {
            $python_code = str_replace($original, $unique_image, $python_code);
        }

        file_put_contents($tmp_file, $python_code);

        $cpu_list = trim(shell_exec("grep -oP '^cpu\\d+' /proc/stat | sed 's/cpu//g'"));
        $cpu_array = explode("\n", $cpu_list);

        if (empty($cpu_array)) {
            die("No available CPUs found.");
        }

        $random_cpu = $cpu_array[array_rand($cpu_array)];

        if (file_exists($tmp_file)) {
            $output = shell_exec("timeout 15s taskset -c $random_cpu python3 $tmp_file 2>&1");

            $show_timeout_message = empty($output);

            foreach ($unique_images as $original => $unique_image) {
                if (file_exists($unique_image)) {
                    $show_timeout_message = false;
                    $_SESSION['generated_images'][] = $unique_image;
                    echo "<script>
                            var img = document.createElement('img');
                            img.src = '$unique_image?ts=' + new Date().getTime();
                            document.getElementById('$output_id').appendChild(img);
                          </script>";
                }
            }

            if ($show_timeout_message) {
                echo "<script>alert('Program is taking too much time to execute. Adjust the parameters or run locally.');</script>";
            } else {
                echo "<pre>$output</pre>";
            }
        } else {
            echo "<pre>Failed to execute Python code.</pre>";
        }

        unlink($tmp_file);

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
	echo "</div>";

    // Explanation Section
    echo "<div class='row'>
            <div class='col-12'>
                <a>$explanation</a>
            
          </div>";
}

        ?>

        <script>
            // Show loading spinner when the form is submitted
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    document.getElementById('loading-spinner').style.display = 'block';
                });
            });

            // Hide loading spinner after page load
            window.addEventListener('load', function() {
                document.getElementById('loading-spinner').style.display = 'none';
            });

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
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9571171868366621"
     crossorigin="anonymous"></script>
<!-- rectangular -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-9571171868366621"
     data-ad-slot="2019320028"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
<!-- Ad end --> 
<?php include '../../include/footer.php'; ?>

</body>
</html>
