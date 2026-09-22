<?php
// Main Menu Titles
$menu_titles = [
    '1' => 'Introduction to Latex',
    '2' => 'Document Classes',
    '3' => 'Page Layout and Sectioning',
    '4' => 'List Structure',
    '5' => 'Fonts and Size',
    '6' => 'Mathematical Representations',
    '7' => 'Creating Tables',
    '8' => 'Figures',
    '9' => 'Bibliography',
    '10' => 'Graphics'
];

// Sub-menu items and their titles
$sub_menu_titles = [
    '1' => [
        1 => 'Basic LaTeX File'
    ],
    '2' => [
        1 => 'Report Class',
        2 => 'Article Class',
        3 => 'Book Class'
    ],
    '3' => [
        1 => 'Page Layout',
        2 => 'Document Sectioning'
    ],
    '4' => [
        1 => 'Item List',
        2 => 'Enumerate List',
        3 => 'Description List'
    ],
    '5' => [
        1 => 'Font Types',
        2 => 'Font Sizes'
    ],
    '6' => [
        1 => 'Symbols and Special Characters',
        2 => 'Mathematical Functions',
        3 => 'Mathematical Equations',
        4 => 'Calculus',
        5 => 'Matrices'
    ],
    '7' => [
        1 => 'Table Create',
        2 => 'Table Borders',
        3 => 'Table Formatting'
    ],
    '8' => [
        1 => 'Inserting Figures',
        2 => 'Figure Alignment',
        3 => 'Figure Captions'
    ],
    '9' => [
        1 => 'Basic Bibliography',
        2 => 'Bibliography Styles'
    ],
    '10' => [
        1 => 'Quotations',
        2 => 'TikZ Pictures',
        3 => 'Drawing Curves and Shapes',
        4 => 'Coloring'
    ]
];

// Mapping submenu to respective pages
$menu = [
    '1' => [
        'basic_latex_file' => 1
    ],
    '2' => [
        'report_class' => 1,
        'article_class' => 2,
        'book_class' => 3
    ],
    '3' => [
        'page_layout' => 1,
        'document_sectioning' => 2
    ],
    '4' => [
        'item_list' => 1,
        'enumerate_list' => 2,
        'description_list' => 3
    ],
    '5' => [
        'font_types' => 1,
        'font_sizes' => 2
    ],
    '6' => [
        'symbol_and_special_characters' => 1,
        'mathematical_functions' => 2,
        'mathematical_equations' => 3,
        'calculus' => 4,
        'matrices' => 5
    ],
    '7' => [
        'table_create' => 1,
        'table_borders' => 2,
        'table_formatting' => 3
    ],
    '8' => [
        'inserting_figures' => 1,
        'figure_alignment' => 2,
        'figure_captions' => 3
    ],
    '9' => [
        'basic_bibliography' => 1,
        'bibliography_styles' => 2
    ],
    '10' => [
        'quotations' => 1,
        'tikz_pictures' => 2,
        'drawing_curves_and_shapes' => 3,
        'coloring' => 4
    ]
];

?>
