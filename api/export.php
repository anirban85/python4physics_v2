<?php
/**
 * Python4Physics - Program Exporter
 * Dynamically exports any computational physics program as Jupyter Notebook (.ipynb), Python (.py), LaTeX (.tex), or GNUplot (.gp).
 */
require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lang = isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : 'python';
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'ipynb';

$valid_tables = ['python', 'gnuplot', 'latex'];
if (!in_array($lang, $valid_tables) || !$id || !isset($conn)) {
    die("Invalid request parameters.");
}

try {
    $stmt = $conn->prepare("SELECT * FROM {$lang} WHERE id = ?");
    $stmt->execute([$id]);
    $prog = $stmt->fetch();

    if (!$prog) {
        die("Program not found.");
    }

    $title = strip_tags($prog['algo'] ?: "Physics Program #{$prog['program_id']}");
    $code = $prog['content'];
    $explanation = strip_tags($prog['explanation'] ?? '');

    if ($format === 'ipynb') {
        $notebook = [
            'cells' => [
                [
                    'cell_type' => 'markdown',
                    'metadata'  => (object)[],
                    'source'    => [
                        "# {$title}\n\n",
                        "**Computational Physics with Python**\n",
                        "*Authors: Dr. Alorika Chatterjee & Dr. Anirban Shaw*\n\n",
                        "Website: https://python4physics.in\n\n",
                        $explanation ? "---\n### Theoretical Formulation\n" . $explanation . "\n\n" : ""
                    ]
                ],
                [
                    'cell_type' => 'code',
                    'execution_count' => null,
                    'metadata'  => (object)[],
                    'outputs'   => [],
                    'source'    => array_map(function($line) { return $line . "\n"; }, explode("\n", $code))
                ]
            ],
            'metadata' => [
                'language_info' => [
                    'name' => 'python',
                    'version' => '3.11'
                ],
                'kernelspec' => [
                    'name' => 'python3',
                    'display_name' => 'Python 3'
                ]
            ],
            'nbformat' => 4,
            'nbformat_minor' => 5
        ];

        $filename = "python4physics_prog_{$prog['id']}.ipynb";
        header('Content-Type: application/x-ipynb+json');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        echo json_encode($notebook, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($format === 'py') {
        $header = "# ================================================================\n" .
                  "# Python4Physics - {$title}\n" .
                  "# Authors: Dr. Alorika Chatterjee & Dr. Anirban Shaw\n" .
                  "# Website: https://python4physics.in\n" .
                  "# ================================================================\n\n";
        $filename = "physics_prog_{$prog['id']}.py";
        header('Content-Type: text/x-python');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        echo $header . $code;
        exit;
    }

    if ($format === 'tex') {
        $filename = "physics_doc_{$prog['id']}.tex";
        header('Content-Type: application/x-tex');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        echo $code;
        exit;
    }

    if ($format === 'gp') {
        $filename = "physics_plot_{$prog['id']}.gp";
        header('Content-Type: text/plain');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        echo $code;
        exit;
    }

    die("Unsupported format: {$format}");
} catch (Exception $e) {
    die("Export error: " . $e->getMessage());
}
