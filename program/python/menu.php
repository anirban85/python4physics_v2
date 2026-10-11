<?php
// Main Menu Titles
$menu_titles = [
    '1' => 'Introduction to Python',
    '2' => 'Iterable Data Type',
    '3' => 'Basic Python Programs',
    '4' => 'Matrix Operation',
    '5' => 'Plotting in Python',
    '6' => 'Ordinary Differential Equation-1',
    '7' => 'Introduction to Numpy',
    '8' => 'Introduction to Pandas for Physics',
    '9' => 'Solve Linear Equations',
    '10' => 'Introduction to Scipy',
    '11' => 'Interpolation',
    '12' => 'Numerical Integration',
    '13' => 'Ordinary Differential Equation-2',
    '14' => 'Curve Fitting',
    '15' => 'Special Functions',
    '16' => 'Fourier Series',
    '17' => 'Partial Differential Equations',
    '18' => 'Shooting Method for Boundary Value Problem',
    '19' => 'TISE Solution',
    '20' => 'TDSE Solution',
    '21' => 'Application in Statistical Mechanics'
];

// Sub-menu items and their titles
$sub_menu_titles = [
    '1' => [
        1 => 'Print Function',
        2 => 'Variable and Data Types',
        3 => 'Mathematical Operations',
        4 => 'Conditionals (if, elif, else)',
        5 => 'For Loop',
        6 => 'While Loop',
        7 => 'User Defined Function',
        8 => 'Module math and cmath',
        9 => 'I/O Operation'
    ],
    '2' => [
        1 => 'List',
        2 => 'Tuple',
        3 => 'String'
    ],
    '3' => [
        1 => 'Series Expansion',
        2 => 'Determining Whether an Integer is Prime or Not',
        3 => 'Finding Prime Number Greater or Lesser Than a Given Value',
        4 => 'Finding All Prime Numbers Within a Given Range',
        5 => 'Root Finding',
        6 => 'Sorting',
		7 => 'Finding Factors of an Integer',
		8 => 'Miscellaneous Problems'
    ],
    '4' => [
        1 => 'Matrix',
        2 => 'Matrix Addition',
        3 => 'Matrix Multiplication',
        4 => 'Matrix Transpose',
        5 => 'Matrix Determinant',
        6 => 'Matrix using List Comprehension'
    ],
    '5' => [
        1 => 'Basic Plot and formatting',
        2 => 'Subplot, Multiplot',
        3 => 'Colormaps in Plots',
		4 => 'Bar Plot',
		5 => 'Pie Chart',
		6 => 'Histogram',
        7 => 'Quiver plot to show Vector Fields',
		8 => 'Stream plot to Visualize Flow Data',
		9 => 'Polar, Parametric Plot',
		10 => 'Contour Plot',
		11 => '3D Plot',
		12 => 'Density Plot',
		13 => 'Animated Plot',
		14 => 'Miscellaneous'		
    ],
    '6' => [
        1 => 'Euler Algorithm',
        2 => 'Motion of a Harmonic Oscillator',
        3 => 'Capacitor Charging / Discharging',
        4 => 'Half Wave Rectifier with Capacitor Filter'
    ],
    '7' => [
        1 => 'Introduction to Numpy',
        2 => 'Array Creation',
        3 => 'Array Inspection',
        4 => 'Mathematical Operation',
        5 => 'Array Indexing and Slicing',
        6 => 'Numpy for Matrix Operators',
        7 => 'Linear Algebra'
    ],
    '8' => [
        1 => 'Pandas Series & Physical Quantities',
        2 => 'DataFrames & Experimental Lab Data',
        3 => 'File I/O with Experimental Datasets',
        4 => 'Data Cleaning & Missing Experimental Data',
        5 => 'Data Transformation, Filtering & Physical Slicing',
        6 => 'Descriptive Statistics & Uncertainty Analysis',
        7 => 'GroupBy Operations & Multi-Trial Experiments',
        8 => 'Reshaping Data & Pivot Tables in Grid Measurements'
    ],
    '9' => [
        1 => 'Gauss Elimination',
        2 => 'Using numpy linalg module',
		3 => 'Gauss-Seidel Method'
    ],
    '10' => [
        1 => 'Integration',
        2 => 'Interpolation',
        3 => 'Linear Algebra',
        4 => 'Optimization'
    ],
    '11' => [
        1 => 'Lagrange’s Interpolation',
        2 => 'Newton’s Forward and Backward Interpolation'
    ],
    '12' => [
        1 => 'Trapezoidal Method',
        2 => 'Simpson’s 1/3rd Rule',
        3 => 'Gaussian Quadrature method',
        4 => 'Convolution of two Gaussian Functions'     
    ],
    '13' => [
        1 => '1st Order ODE (RK2 Method)',
        2 => '2nd Order ODE (RK2 Method)',
        3 => '1st Order ODE (RK4 Method)',
        4 => '2nd Order ODE (RK4 Method)',
        5 => 'Solve ODE using scipy.integrate.odeint()'
    ],
    '14' => [
        1 => 'Using Numpy',
        2 => 'Using Scipy.optimize'
    ],
    '15' => [
        1 => 'Bessel Function',
        2 => 'Legendre Polynomial',
        3 => 'Hermite Polynomial'
    ],
    '16' => [
        1 => 'Square Wave',
        2 => 'Triangular Wave',
        3 => 'Sawtooth Wave'
    ],
    '17' => [
        1 => 'Heat Diffusion',
        2 => 'Laplace Equation',
        3 => 'Wave Equation',
        4 => 'Schrodinger Equation'
    ],
    '18' => [
        1 => 'Example-1',
        2 => 'Example-2'
    ],
    '19' => [
        1 => 'Shooting Method to Solve TISE',
        2 => 'Particle in 1D Potential Box',
        3 => 'Finite Square Well',
        4 => 'Harmonic Oscillator',
        5 => 'H-like Atom',
		6 => 'Particle in 1D Potential Well (Solving Transcendental Equation)',
		7 => 'TISE by Finite Difference Method'
    ],
    '20' => [
        1 => 'Explicit Methods',
        2 => 'Implicit Methods',
        3 => 'Crank-Nicolson Methods',
        4 => 'Time Evaluation of WavePacket',
        5 => 'Barrier Penetration and Tunneling for an Initially Gaussian Wavepacket'
    ],
    '21' => [
        1 => 'Study of Random Numbers and Time Series',
        2 => 'Generate Different Variate from Uniform Variate',
        3 => 'Coin Tossing Simulation',
        4 => 'Nuclear Decay Simulation',
        5 => '1D Random Walk Simulation',
        6 => '2D Random Walk Simulation',
		7 => 'Monte Carlo Method',
        8 => 'Specific Heat of Solids (Einstein’s Theory)',
        9 => 'Specific Heat of Solids (Debye’s Theory)',
        10 => 'Plots of Different Distribution Functions'
    ]
];

// Mapping submenu to respective pages
$menu = [
    // Menu 1: Introduction to Python
    '1' => [
        'print_function' => 1,
        'variable_data_types' => 2,
        'math_operations' => 3,
        'conditionals' => 4,
        'for_loop' => 5,
        'while_loop' => 6,
        'user_defined_function' => 7,
        'module_math_cmath' => 8,
        'IO_operation' => 9
    ],
    // Menu 2: Iterable Data Type
    '2' => [
        'list' => 1,
        'tuple' => 2,
        'string' => 3
    ],
    // Menu 3: Basic Python Programs
    '3' => [
        'series_expansion' => 1,
        'check_number_prime_or_not' => 2,
        'find_prime_number_greater_lesser_of_number' => 3,
        'prime_number_within_range' => 4,
        'root_finding' => 5,
        'sorting' => 6,
		'factors_of_integer' => 7,
		'miscellaneous_problems' => 8
    ],
    // Menu 4: Matrix Operation
    '4' => [
        'matrix' => 1,
        'matrix_addition' => 2,
        'matrix_multiplication' => 3,
        'matrix_transpose' => 4,
        'matrix_determinant' => 5,
        'matrix_list_comprehension' => 6
    ],
    // Menu 5: Plotting in Python
    '5' => [
        'basic_plot_formatting' => 1,
        'subplot_multiplot' => 2,
        'colormaps_in_plot' => 3,
        'bar_plot' => 4,
		'pie_chart' => 5,
		'histogram' => 6,
		'quiver_plot_to_show_vector_field' => 7,
		'stream_plot_to_visualize_flow_data' => 8,
		'polar_parametric_plot' => 9,
		'contour_plot' => 10,
		'3D_plot' => 11,
		'density_plot' => 12,
		'animated_plot' => 13,
		'miscellaneous' => 14
    ],
    // Menu 6: Ordinary Differential Equation-1
    '6' => [
        'euler_algorithm' => 1,
        'motion_of_harmonic_oscillator' => 2,
        'capacitor_charging_discharging' => 3,
        'half_wave_rectifier' => 4
    ],
    // Menu 7: Introduction to Numpy
    '7' => [
        'introduction_to_numpy' => 1,
        'array_creation' => 2,
        'array_inspection' => 3,
        'mathematical_operation' => 4,
        'array_indexing_slicing' => 5,
        'numpy_matrix_operators' => 6,
        'linear_algebra' => 7
    ],
    // Menu 8: Introduction to Pandas for Physics
    '8' => [
        'series_physical_quantities' => 1,
        'dataframes_lab_data' => 2,
        'file_io_datasets' => 3,
        'data_cleaning_missing_data' => 4,
        'data_transformation_filtering' => 5,
        'descriptive_statistics_uncertainty' => 6,
        'groupby_multi_trial_experiments' => 7,
        'reshaping_pivot_tables' => 8
    ],
    // Menu 9: Solve Linear Equations
    '9' => [
        'gauss_elimination' => 1,
        'numpy_linalg' => 2,
		'gauss_seidal' => 3
    ],
    // Menu 10: Introduction to Scipy
    '10' => [
        'integration' => 1,
        'interpolation' => 2,
        'linear_algebra' => 3,
        'optimization' => 4
    ],
    // Menu 11: Interpolation
    '11' => [
        'lagrange_interpolation' => 1,
        'newton_interpolation' => 2
    ],
    // Menu 12: Numerical Integration
    '12' => [
        'trapezoidal_method' => 1,
        'simpson_rule' => 2,
        'gaussian_quadrature' => 3,
        'convolution_of_two_gaussian_functions' => 4
    ],
    // Menu 13: Ordinary Differential Equation-2
    '13' => [
        'rk2_1st_order' => 1,
        'rk2_2nd_order' => 2,
        'rk4_1st_order' => 3,
        'rk4_2nd_order' => 4,
        'scipy_odeint' => 5
    ],
    // Menu 14: Curve Fitting
    '14' => [
        'numpy_curve_fitting' => 1,
        'scipy_optimize_curve_fitting' => 2
    ],
    // Menu 15: Special Functions
    '15' => [
        'bessel_function' => 1,
        'legendre_polynomial' => 2,
        'hermite_polynomial' => 3
    ],
    // Menu 16: Fourier Series
    '16' => [
        'square_wave' => 1,
        'triangular_wave' => 2,
        'sawtooth_wave' => 3
    ],
    // Menu 17: Partial Differential Equations
    '17' => [
        'heat_diffusion' => 1,
        'laplace_equation' => 2,
        'wave_equation' => 3,
        'Schrodinger_equation' => 4
    ],
    // Menu 18: Shooting Method for Boundary Value Problem
    '18' => [
        'example_1' => 1,
        'example_2' => 2
    ],
    // Menu 19: TISE Solution
    '19' => [
        'shooting_method_to_solve_TISE' => 1,
        'particle_in_1d_potential_box' => 2,
        'finite_square_well' => 3,
        'harmonic_oscillator' => 4,
        'h_like_atom' => 5,
		'particle_1d_potential_well_transcendental_equation' => 6,
		'TISE_finite_difference_method' => 7
    ],
    // Menu 20: TDSE Solution
    '20' => [
        'explicit_method' => 1,
        'implicit_method' => 2,
        'Crank-Nicolson_Methods' => 3,
        'wavepacket_time_evolution' => 4,
        'barrier_tunneling' => 5
    ],
    // Menu 21: Application in Statistical Mechanics
    '21' => [
        'random_numbers' => 1,
        'different_variate_from_uniform_variate' => 2,
        'coin_toss_simulation' => 3,
        'nuclear_decay_simulation' => 4,
        '1d_random_walk' => 5,
        '2d_random_walk' => 6,
		'monte_carlo_method' => 7,
        'specific_heat_einstein' => 8,
        'specific_heat_debye' => 9,
        'distribution_plots' => 10
    ]
];

// Redirection logic based on menu and submenu selections
if (isset($_GET['menu_id']) && isset($_GET['submenu_id'])) {
    $menu_id = $_GET['menu_id'];
    $submenu_id = $_GET['submenu_id'];

    switch ($menu_id) {
        case '1': // Introduction to Python
        case '2': // Iterable Data Type
        case '3': // Basic Problems
        case '4': // Matrix Operation
        case '5': // Plotting in Python
        case '6': // Ordinary Differential Equation-1
        case '7': // Introduction to Numpy
        case '8': // Introduction to Pandas for Physics
        case '9': // Solve Linear Equations
        case '10': // Introduction to Scipy
        case '11': // Interpolation
            break;
        default:
            break;
    }
}
?>
