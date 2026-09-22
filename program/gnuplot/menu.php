<?php
// Main Menu Titles
$menu_titles = [
    '1' => 'Plot of 2D Graph',
    '2' => 'User Defined Function Plot',
    '3' => 'Data File Plot',
    '4' => 'Data Fitting',
    '5' => 'Special Plot'
];

// Sub-menu items and their titles
$sub_menu_titles = [
    '1' => [
        1 => 'Function Plot',
        2 => 'Data File Plot'
    ],
    '2' => [
        1 => 'Function Plot',
        2 => 'Piecewise Function Plot'
    ],
    '3' => [
        1 => 'Conditional Plotting of Data File'
    ],
    '4' => [
        1 => 'Fitting Data Files Using Gnuplot'
    ],
    '5' => [
        1 => 'Polar Plot',
        2 => 'Parametric Plot'
    ]
];

// Mapping submenu to respective pages
$menu = [
    // Menu 1: Plot of 2D Graph
    '1' => [
        'function_plot' => 1,
        'data_file_plot' => 2
    ],
    // Menu 2: User Defined Function Plot
    '2' => [
        'function_plot' => 1,
        'piecewise_function_plot' => 2
    ],
    // Menu 3: Data File Plot
    '3' => [
        'conditional_plotting_of_data_file' => 1
    ],
    // Menu 4: Data Fitting
    '4' => [
        'fitting_data_files_using_gnuplot' => 1
    ],
    // Menu 5: Special Plot
    '5' => [
        'polar_plot' => 1,
        'parametric_plot' => 2
    ]
];


?>
