<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CodeMirror LaTeX Example</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/stex/stex.min.js"></script>
</head>
<body>
    <textarea id="editor" name="editor">
\documentclass{article}
\begin{document}
Hello, world!
\end{document}
    </textarea>

    <script>
        var editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
            mode: "stex",
            lineNumbers: true,
            theme: "default"
        });
    </script>
</body>
</html>
