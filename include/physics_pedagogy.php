<?php
/**
 * Python4Physics - Academic Pedagogical Knowledge Engine
 * Generates topic-accurate theoretical, mathematical, and algorithmic documentation
 * ensuring exact alignment between the curriculum topic and pedagogical explanations.
 * 
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */

if (!function_exists('get_physics_pedagogical_guide')) {
    function get_physics_pedagogical_guide($menu_id, $submenu_id, $chapter_title, $subtopic_title) {
        $menu_id = intval($menu_id);
        $submenu_id = intval($submenu_id);
        $safe_chapter = htmlspecialchars($chapter_title, ENT_QUOTES, 'UTF-8');
        $safe_subtopic = htmlspecialchars($subtopic_title, ENT_QUOTES, 'UTF-8');
        $key = "{$menu_id}-{$submenu_id}";

        // Subtopic-Specific Pedagogical Knowledge Database
        // NOTE: All strings with mathematical notation MUST use single quotes ('...') 
        // to prevent PHP from evaluating LaTeX math symbols ($x, $y, $t, etc.) as PHP variables.
        $subtopic_guides = [
            // Chapter 1: Introduction to Python
            '1-1' => [
                'title' => 'Print Function: Standard Console Output & Numerical Data Formatting',
                'overview' => 'In scientific programming and computational physics, the <code>print()</code> function serves as the primary stream interface for displaying calculation results, physical diagnostics, and simulation parameters to standard output (<code>stdout</code>).',
                'formulation' => 'Scientific output requires precise numerical formatting to represent experimental error and significant figures. Python f-strings allow explicit precision control: <code>f"{value:.4e}"</code> formats in scientific notation (e.g., $1.6022 \\times 10^{-19}$), while <code>f"{value:.3f}"</code> specifies fixed-point decimal precision. Tabular alignment using format specifiers ensures clear presentation of multi-column experimental datasets.',
                'algorithm' => '1. <strong>Output Stream:</strong> Direct data to <code>sys.stdout</code> using standard <code>print(*objects, sep=\' \', end=\'\\n\')</code>.<br>2. <strong>Scientific Specifiers:</strong> Format floating-point quantities using <code>:.e</code> for exponential notation and <code>:.f</code> for fixed-width display.<br>3. <strong>Data Logging:</strong> Display multi-column tables with headers for time, position, and velocity during simulation runs.',
                'exercises' => [
                    'Write a script to print the values of physical constants ($c, \\hbar, G, k_B$) formatted neatly in scientific notation with four significant figures.',
                    'Construct a formatted ASCII table displaying angles from $0^\\circ$ to $90^\\circ$ in $15^\\circ$ increments alongside their sine and cosine values.',
                    'Format an experimental measurement report displaying value, absolute uncertainty, and percentage relative uncertainty.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],
            '1-2' => [
                'title' => 'Variables & Data Types: Dynamic Typing & Numerical Representation',
                'overview' => 'Variables in computational physics act as symbolic representations of physical observables and system state parameters. Python provides dynamic typing with native scalar types: integers (arbitrary precision), floating-point numbers (IEEE 754 standard), complex numbers, and booleans.',
                'formulation' => 'Physical simulations rely on 64-bit double precision floating-point numbers (IEEE 754), providing 53 bits of significand precision (~15 to 17 significant decimal digits) with dynamic range approximately $10^{-308}$ to $10^{308}$. For quantum mechanical wavefunctions, Python provides built-in complex numbers $z = x + iy$ using the <code>j</code> suffix (e.g., <code>3.0 + 4.0j</code>).',
                'algorithm' => '1. <strong>Variable Allocation:</strong> Bind identifier names to numerical values and physical quantities.<br>2. <strong>Type Conversion:</strong> Apply explicit typecasting (<code>int()</code>, <code>float()</code>, <code>complex()</code>) to guarantee proper operator behavior.<br>3. <strong>Precision Awareness:</strong> Guard against numerical overflow ($> 1.8 \\times 10^{308}$) and underflow ($< 2.2 \\times 10^{-308}$) during iterative physical calculations.',
                'exercises' => [
                    'Verify the machine epsilon $\\epsilon_{\\text{mach}}$ for standard 64-bit floating point numbers by dividing $1.0$ by $2$ until $1.0 + \\epsilon == 1.0$.',
                    'Represent a quantum state amplitude as a complex number $z = \\frac{1}{\\sqrt{2}} + \\frac{i}{\\sqrt{2}}$ and verify that its probability density $|z|^2 = z \\cdot z^* = 1.0$.',
                    'Investigate the dynamic range difference between Python arbitrary-precision integers and standard 64-bit floats by computing large factorials $N!$.'
                ],
                'references' => [
                    'David Goldberg, "What Every Computer Scientist Should Know About Floating-Point Arithmetic", ACM Computing Surveys (1991).',
                    'Paul L. DeVries and Javier E. Hasbun, "A First Course in Computational Physics", Jones & Bartlett (2011).'
                ]
            ],
            '1-3' => [
                'title' => 'Mathematical Operations: Arithmetic Expressions & Operator Precedence',
                'overview' => 'Arithmetic operators implement fundamental mathematical equations governing physical laws, from kinetic energy formulas to Coulomb and gravitational force evaluations.',
                'formulation' => 'Python evaluates mathematical operations using standard algebraic precedence: exponentiation (<code>**</code>), unary operations, multiplication and division (<code>*</code>, <code>/</code>, <code>//</code>, <code>%</code>), and addition/subtraction (<code>+</code>, <code>-</code>). True division <code>/</code> returns a floating-point number, floor division <code>//</code> truncates to the nearest integer, and modulo <code>%</code> yields the remainder.',
                'algorithm' => '1. <strong>Precedence Grouping:</strong> Use explicit parentheses to enforce intended physical calculation order.<br>2. <strong>Power Evaluation:</strong> Use the <code>**</code> operator for exponents and power laws (e.g., $r^{-2}$ for inverse-square fields).<br>3. <strong>Periodic Boundary Wrapping:</strong> Apply modulo arithmetic <code>x % L</code> to simulate periodic boundary conditions in particle physics.',
                'exercises' => [
                    'Calculate the gravitational force $F = G \\frac{m_1 m_2}{r^2}$ between the Earth and Moon using exact astronomical constants.',
                    'Evaluate the period of a simple pendulum $T = 2\\pi \\sqrt{L/g}$ for varying lengths $L \\in [0.1, 2.0]\\text{ m}$.',
                    'Demonstrate integer division vs true division when evaluating fractions in numerical formulas.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],
            '1-4' => [
                'title' => 'Conditionals (if, elif, else): Logical Branching & Piecewise Physical Laws',
                'overview' => 'In computational physics, conditional statements model physical phenomena that depend on state thresholds—such as piecewise potential energy functions, boundary collision reflections, or regime transitions (e.g., laminar vs. turbulent flow).',
                'formulation' => 'Logical branching evaluates boolean conditions composed of relational operators ($==, \\neq, <, >, \\le, \\ge$) and logical operators (<code>and</code>, <code>or</code>, <code>not</code>). A classic physics application is the piecewise potential energy function: for a finite square well, $V(x) = 0$ for $|x| < a$ and $V(x) = V_0$ for $|x| \\ge a$. Another example is the Heaviside step function $H(x) = 1$ if $x \\ge 0$, and $0$ otherwise.',
                'algorithm' => '1. <strong>Condition Evaluation:</strong> Check physical boundary criteria using boolean expressions.<br>2. <strong>Branch Execution:</strong> Route control flow to the corresponding physical equation depending on the current regime.<br>3. <strong>Boundary Reflection:</strong> Reverse velocity components upon boundary crossing ($v \\leftarrow -v$) to simulate elastic wall collisions.',
                'exercises' => [
                    'Implement a script that evaluates the electrostatic potential $V(r)$ of a uniformly charged sphere of radius $R$: $V(r) = \\frac{Q}{8\\pi\\varepsilon_0 R}\\left(3 - \\frac{r^2}{R^2}\\right)$ inside, and $V(r) = \\frac{Q}{4\\pi\\varepsilon_0 r}$ outside.',
                    'Write a program to classify the roots of a quadratic equation $ax^2 + bx + c = 0$ using the discriminant $\\Delta = b^2 - 4ac$ (distinct real, repeated real, or complex conjugate roots).',
                    'Simulate a 1D elastic particle bouncing between two rigid walls at $x = 0$ and $x = L$, updating velocity with an <code>if</code> statement whenever a wall is reached.'
                ],
                'references' => [
                    'David Halliday, Robert Resnick, and Jearl Walker, "Fundamentals of Physics", Wiley (11th ed., 2018).',
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],
            '1-5' => [
                'title' => 'For Loop: Deterministic Iteration & Finite Series Summations',
                'overview' => 'The <code>for</code> loop enables deterministic iteration over known sequence lengths, making it ideal for evaluating finite mathematical summations, stepping through discretized time grids, and traversing coordinate arrays.',
                'formulation' => 'Many fundamental physical quantities are defined as finite or convergent series: $\\Phi = \\sum_{i=1}^N \\frac{q_i}{4\\pi\\varepsilon_0 r_i}$ for discrete multipole charge systems, or $S_N = \\sum_{n=1}^N \\frac{1}{n^2}$ converging toward $\\frac{\\pi^2}{6}$. Loop iterators generated by <code>range(start, stop, step)</code> advance in discrete integer increments.',
                'algorithm' => '1. <strong>Loop Setup:</strong> Instantiate a loop counter using <code>range(N)</code> for $N$ discrete steps.<br>2. <strong>Accumulation:</strong> Maintain a running accumulator variable (e.g., <code>total_energy += dE</code>).<br>3. <strong>Discretization:</strong> Map loop index $i$ to continuous physical time $t_i = t_0 + i \\cdot \\Delta t$.',
                'exercises' => [
                    'Compute the total electric potential at the origin due to $N = 100$ point charges arranged along the $x$-axis.',
                    'Evaluate the Taylor series expansion for $\\sin(x) = \\sum_{n=0}^N \\frac{(-1)^n x^{2n+1}}{(2n+1)!}$ and compare the error against <code>math.sin(x)</code> as $N$ increases.',
                    'Simulate the trajectory of a free-falling body under gravity over $100$ discrete time steps of $\\Delta t = 0.05\\text{ s}$.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],
            '1-6' => [
                'title' => 'While Loop: Indefinite Iteration & Convergence Thresholds',
                'overview' => 'The <code>while</code> loop executes as long as a specified physical or numerical condition remains true. It is the natural control structure for convergence algorithms, root-finding bisection, and simulation stopping conditions.',
                'formulation' => 'In iterative relaxation and root-finding, the number of required steps is not known a priori. Instead, execution continues until the relative error between consecutive iterations satisfies $|x_{k+1} - x_k| < \\epsilon_{\\text{tol}}$, where $\\epsilon_{\\text{tol}}$ is a designated numerical tolerance (e.g., $10^{-7}$).',
                'algorithm' => '1. <strong>Condition Definition:</strong> Initialize the state variable and error metric.<br>2. <strong>Iterative Update:</strong> Compute the next numerical approximation $x_{k+1} = g(x_k)$.<br>3. <strong>Safety Guard:</strong> Include a maximum iteration limit (<code>max_iter</code>) to prevent infinite loops in non-convergent regimes.',
                'exercises' => [
                    'Implement the Babylonian method to find the square root of a positive number $S$ using $x_{n+1} = \\frac{1}{2}\\left(x_n + \\frac{S}{x_n}\\right)$ until $|x_{n+1} - x_n| < 10^{-8}$.',
                    'Simulate radioactive decay $N(t + \\Delta t) = N(t) - \\lambda N(t) \\Delta t$, continuing the loop until the remaining nuclei count drops below $1\\%$ of $N_0$.',
                    'Compute the trajectory of a projectile launched at an angle, terminating the loop with <code>while y >= 0:</code> when it impacts the ground.'
                ],
                'references' => [
                    'William H. Press et al., "Numerical Recipes: The Art of Scientific Computing", Cambridge University Press (2007).'
                ]
            ],
            '1-7' => [
                'title' => 'User Defined Functions: Modular Programming & Force Law Abstraction',
                'overview' => 'User-defined functions encapsulate reusable physical laws and numerical procedures. Decoupling mathematical force definitions from time-integration algorithms enables modular, extensible computational physics modeling.',
                'formulation' => 'A mathematical function represents a mapping $f: \\mathbb{R}^n \\to \\mathbb{R}^m$, such as computing a force vector $\\vec{F}(\\vec{r}, \\vec{v}, t)$ from spatial coordinates and velocities. Defining modular functions with parameters and explicit return values enforces functional isolation and prevents unintended global state mutation.',
                'algorithm' => '1. <strong>Function Definition:</strong> Declare functions with descriptive physical arguments (<code>def gravitational_force(m1, m2, r):</code>).<br>2. <strong>Pure Functions:</strong> Ensure functions depend strictly on their arguments and return computed physical results.<br>3. <strong>Higher-Order Integration:</strong> Pass function handles directly into general numerical integration routines.',
                'exercises' => [
                    'Write a function that calculates the Lennard-Jones potential $V(r) = 4\\epsilon \\left[\\left(\\frac{\\sigma}{r}\\right)^{12} - \\left(\\frac{\\sigma}{r}\\right)^6\\right]$ and another function for the interatomic force $F(r) = -\\frac{dV}{dr}$.',
                    'Create a modular function computing the velocity and position of a damped harmonic oscillator at time $t$ for underdamped, critically damped, and overdamped cases.',
                    'Implement a generic numerical derivative function <code>deriv(f, x, h=1e-5)</code> using the central difference stencil $\\frac{f(x+h) - f(x-h)}{2h}$.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],
            '1-8' => [
                'title' => 'Modules math and cmath: Standard Mathematical & Complex Libraries',
                'overview' => 'Python\'s standard library provides <code>math</code> for real-valued floating-point functions and <code>cmath</code> for complex arithmetic. These modules provide optimized C-level implementations of transcendental, trigonometric, and hyperbolic functions.',
                'formulation' => 'In wave mechanics, optics, and AC circuit theory, physical quantities are expressed in terms of complex numbers $z = x + iy = r e^{i\\theta}$. The <code>cmath</code> module evaluates Euler\'s formula $e^{i\\theta} = \\cos\\theta + i\\sin\\theta$, complex phase $\\theta = \\text{atan2}(y, x)$, and square roots of negative numbers without throwing domain errors.',
                'algorithm' => '1. <strong>Module Import:</strong> Import <code>math</code> for real calculations and <code>cmath</code> for complex phasors and quantum states.<br>2. <strong>Coordinate Conversion:</strong> Convert Cartesian coordinates to polar representation using <code>cmath.polar(z)</code>.<br>3. <strong>Domain Verification:</strong> Guard input arguments to prevent mathematical domain errors (e.g., negative square root arguments in <code>math.sqrt</code>).',
                'exercises' => [
                    'Calculate the complex impedance $Z = R + i\\left(\\omega L - \\frac{1}{\\omega C}\\right)$ of an RLC circuit and determine the resonance frequency where $\\text{Im}(Z) = 0$.',
                    'Compute the phase shift and complex transmission coefficient for a light wave traversing a dielectric thin film.',
                    'Verify Euler\'s identity $e^{i\\pi} + 1 = 0$ numerically to machine precision using <code>cmath.exp(1j * cmath.pi)</code>.'
                ],
                'references' => [
                    'David J. Griffiths, "Introduction to Electrodynamics", Cambridge University Press (4th ed., 2017).'
                ]
            ],
            '1-9' => [
                'title' => 'File I/O Operations: Experimental Data Ingestion & Trajectory Logging',
                'overview' => 'Scientific workflows require reading experimental measurements from external instruments and writing simulation trajectory coordinates to persistent disk files for subsequent analysis and plotting.',
                'formulation' => 'Experimental data typically arrives in delimited plain text formats (CSV, TSV, space-separated ASCII). Robust file handling employs context managers (<code>with open(...) as f:</code>) to guarantee proper stream closure even if runtime exceptions occur.',
                'algorithm' => '1. <strong>Context Management:</strong> Open files safely using the <code>with open(\'filename\', \'mode\') as file:</code> construct.<br>2. <strong>Parsing Data:</strong> Strip whitespace, split lines by delimiters, and cast text tokens to numerical <code>float</code> values.<br>3. <strong>Formatted Export:</strong> Write multi-column simulation logs with aligned formatting and scientific headers.',
                'exercises' => [
                    'Write a script that exports simulated planetary orbital coordinates $(t, x, y)$ into a CSV data file.',
                    'Read an experimental sensor dataset from a text file and calculate the arithmetic mean and sample standard deviation.',
                    'Construct a data filtering script that reads raw lab measurements and filters out corrupted or out-of-range sensor readings.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],

            // Chapter 2: Iterable Data Types
            '2-1' => [
                'title' => 'Python Lists: Dynamic Sequences & Spatial Trajectory Storage',
                'overview' => 'Lists in Python are mutable, dynamic arrays capable of storing ordered sequences of physical data—such as time histories, coordinate trajectories, and experimental measurements.',
                'formulation' => 'A physical trajectory is represented as a discrete sequence $\\vec{r} = [x_0, x_1, x_2, \\dots, x_N]$. Python lists support dynamic memory resizing, indexing, slicing (<code>list[start:stop:step]</code>), and expressive list comprehensions that allow concise functional transformations of physical arrays.',
                'algorithm' => '1. <strong>List Initialization:</strong> Initialize empty accumulator lists (<code>t_list = []</code>, <code>x_list = []</code>).<br>2. <strong>Iterative Appending:</strong> Append state variables at each time step using <code>.append()</code>.<br>3. <strong>List Comprehensions:</strong> Transform coordinate data compactly (e.g., <code>[0.5 * m * v**2 for v in velocities]</code>).',
                'exercises' => [
                    'Simulate a projectile trajectory and record time, $x$, and $y$ coordinates in separate lists for Matplotlib plotting.',
                    'Use list comprehension to calculate the gravitational potential energy $U = mgh$ for a list of height measurements.',
                    'Implement a moving average filter over a noisy experimental list of 50 data points.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],
            '2-2' => [
                'title' => 'Python Tuples: Immutable Sequences & Physical State Vectors',
                'overview' => 'Tuples are immutable ordered sequences in Python. Their immutability ensures data integrity when storing fixed physical constants, spatial coordinate pairs $(x, y, z)$, or passing immutable state vectors across simulation functions.',
                'formulation' => 'A phase-space coordinate is represented as an immutable tuple $(\\vec{r}, \\vec{p}) = (x, y, z, p_x, p_y, p_z)$. Tuple packing and unpacking enables clean multi-variable return statements from physical integration functions: <code>pos, vel = update_state(pos, vel, dt)</code>.',
                'algorithm' => '1. <strong>Immutable Packing:</strong> Package multiple physical observables into a single tuple return value.<br>2. <strong>Tuple Unpacking:</strong> Destructure multi-variable coordinates cleanly in receiving scopes.<br>3. <strong>Constant Lookup:</strong> Use tuples as dictionary keys for grid lookups where mutable lists are disallowed.',
                'exercises' => [
                    'Write a function that accepts time $t$ and returns a tuple $(x, y, v_x, v_y)$ of 2D projectile state observables.',
                    'Create an immutable registry of fundamental physical constants stored as named coordinate tuples.',
                    'Demonstrate tuple unpacking to swap phase space variables without requiring temporary variables.'
                ],
                'references' => [
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).'
                ]
            ],
            '2-3' => [
                'title' => 'Python Strings: Experimental Metadata Parsing & Scientific Formatting',
                'overview' => 'String handling is essential for parsing experimental data headers, extracting scientific notation from raw lab instrument outputs, and generating dynamic plot labels with mathematical notations.',
                'formulation' => 'Instrument outputs often combine textual prefixes with numerical quantities (e.g., <code>"VOLTAGE: 12.45 V"</code>). String slicing, regex matching, and string methods (<code>.split()</code>, <code>.strip()</code>, <code>.replace()</code>) enable extraction of numerical values from unstructured experimental telemetry.',
                'algorithm' => '1. <strong>String Sanitization:</strong> Strip non-numeric tokens and formatting characters from raw sensor feeds.<br>2. <strong>Token Splitting:</strong> Split multi-column whitespace-separated ASCII lines into array tokens.<br>3. <strong>Plot Label Generation:</strong> Construct LaTeX-formatted strings for Matplotlib graph axes and legends.',
                'exercises' => [
                    'Parse a raw sensor record string and extract floating-point temperature and pressure readings.',
                    'Construct dynamically formatted plot legends incorporating variable physical parameters: <code>f"$\\omega = {omega:.2f}\\text{{ rad/s}}$"</code>.',
                    'Write a function that converts numerical values into standard SI prefix notation (e.g., $1.5 \\times 10^{-6}\\text{ m} \\to 1.5\\,\\mu\\text{m}$).'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).'
                ]
            ],

            // Chapter 6: Ordinary Differential Equations - 1
            '6-1' => [
                'title' => 'Euler Algorithm: First-Order Discretization for Differential Equations',
                'overview' => 'Euler\'s method is the simplest numerical algorithm for solving initial value problems for ordinary differential equations. It approximates the derivative with a forward finite difference.',
                'formulation' => 'For an initial value problem $\\frac{dy}{dt} = f(t, y)$ with $y(t_0) = y_0$, the first-order Taylor expansion yields:\n$$y_{n+1} = y_n + h f(t_n, y_n)$$\nwhere $h = \\Delta t$ is the uniform time step. The local truncation error is $\\mathcal{O}(h^2)$, while the global cumulative error over a finite interval is $\\mathcal{O}(h)$. Because of its first-order nature, standard Euler integration exhibits artificial energy growth in oscillatory physical systems.',
                'algorithm' => '1. <strong>Initial Conditions:</strong> Set $t_0, y_0$, and step size $h$.<br>2. <strong>Derivative Computation:</strong> Evaluate $f(t_n, y_n)$ at the current state.<br>3. <strong>State Update:</strong> Advance $y_{n+1} = y_n + h \\cdot f(t_n, y_n)$ and $t_{n+1} = t_n + h$.<br>4. <strong>Stability Check:</strong> Monitor numerical divergence for stiff differential equations.',
                'exercises' => [
                    'Solve the radioactive decay equation $\\frac{dN}{dt} = -\\lambda N$ using Euler\'s method and compare the numerical result with the exact analytical solution $N(t) = N_0 e^{-\\lambda t}$.',
                    'Demonstrate numerical instability in a simple harmonic oscillator simulated with standard Euler, observing how total mechanical energy artificially increases over time.',
                    'Investigate the effect of halving step size $h$ on the global numerical error at time $t = 1.0\\text{ s}$.'
                ],
                'references' => [
                    'John R. Taylor, "Classical Mechanics", University Science Books (2005).',
                    'William H. Press et al., "Numerical Recipes", Cambridge University Press (2007).'
                ]
            ],
            '6-2' => [
                'title' => 'Simple Harmonic Oscillator: Conservative Dynamics & Phase Space',
                'overview' => 'The simple harmonic oscillator models systems with linear restoring forces—from mechanical springs to molecular vibrations and electromagnetic cavity modes.',
                'formulation' => 'The equation of motion is $\\ddot{x} + \\omega^2 x = 0$, where $\\omega = \\sqrt{k/m}$ is the natural angular frequency. In state-space representation, it becomes two coupled first-order ODEs:\n$$\\frac{dx}{dt} = v, \\quad \\frac{dv}{dt} = -\\omega^2 x$$\nTotal mechanical energy $E = \\frac{1}{2}m v^2 + \\frac{1}{2}k x^2$ is an invariant of the exact physical motion. Symplectic algorithms like the Euler-Cromer method ($v_{n+1} = v_n - \\omega^2 x_n h$, $x_{n+1} = x_n + v_{n+1} h$) preserve phase-space area and bound energy fluctuations indefinitely.',
                'algorithm' => '1. <strong>State Initialization:</strong> Define initial displacement $x_0$, initial velocity $v_0$, and frequency $\\omega$.<br>2. <strong>Coupled Updating:</strong> Advance velocity and position sequentially.<br>3. <strong>Energy Monitoring:</strong> Compute total energy $E_n = \\frac{1}{2}m v_n^2 + \\frac{1}{2}k x_n^2$ at each step to verify conservation.<br>4. <strong>Phase Portrait:</strong> Plot position $x$ vs velocity $v$ to visualize closed phase-space orbits.',
                'exercises' => [
                    'Simulate a harmonic oscillator using both Euler and Euler-Cromer schemes, comparing energy conservation over 50 complete oscillation periods.',
                    'Introduce viscous damping $F_d = -b v$ and simulate underdamped, critically damped, and overdamped motion.',
                    'Plot the phase space trajectory $(x, v)$ showing the transition from closed elliptical orbits to spiral attractors under damping.'
                ],
                'references' => [
                    'David Morin, "Introduction to Classical Mechanics", Cambridge University Press (2008).',
                    'John R. Taylor, "Classical Mechanics", University Science Books (2005).'
                ]
            ],
            '6-3' => [
                'title' => 'RC Circuit Transients: Capacitor Charging & Discharging Dynamics',
                'overview' => 'Resistor-Capacitor (RC) circuits represent fundamental first-order dynamical systems modeling exponential relaxation, signal filtering, and dielectric relaxation processes.',
                'formulation' => 'Applying Kirchhoff\'s loop rule to a series RC circuit driven by an electromotive force $\\mathcal{E}$ yields $R \\frac{dq}{dt} + \\frac{q}{C} = \\mathcal{E}$. In terms of capacitor voltage $V_C(t) = q/C$:\n$$R C \\frac{dV_C}{dt} + V_C = \\mathcal{E}$$\nThe characteristic relaxation time is $\\tau = RC$. During charging from $V_C(0) = 0$, the voltage follows $V_C(t) = \\mathcal{E}(1 - e^{-t/\\tau})$. During discharging with $\\mathcal{E} = 0$, it relaxes as $V_C(t) = V_0 e^{-t/\\tau}$.',
                'algorithm' => '1. <strong>Parameter Setup:</strong> Define resistance $R$, capacitance $C$, EMF $\\mathcal{E}$, and compute $\\tau = RC$.<br>2. <strong>Time Discretization:</strong> Select step size $h \\ll \\tau$ for numerical stability.<br>3. <strong>Transient Stepping:</strong> Update $V_{n+1} = V_n + \\frac{h}{RC}(\\mathcal{E} - V_n)$ across charging and discharging phases.',
                'exercises' => [
                    'Simulate capacitor charging from $t = 0$ to $5\\tau$ and verify that $V_C(\\tau) \\approx 0.632\\mathcal{E}$.',
                    'Simulate the response of an RC circuit to a square wave input voltage alternating between $0\\text{ V}$ and $5\\text{ V}$.',
                    'Compute the instantaneous power dissipated across the resistor and confirm total energy conservation.'
                ],
                'references' => [
                    'David J. Griffiths, "Introduction to Electrodynamics", Cambridge University Press (4th ed., 2017).'
                ]
            ],
            '6-4' => [
                'title' => 'Half-Wave Rectifier with Capacitor Filter: Nonlinear AC Circuit Simulation',
                'overview' => 'A half-wave rectifier with a capacitive filter converts alternating current (AC) into direct current (DC) by exploiting diode unidirectional conduction and capacitive charge retention.',
                'formulation' => 'The circuit consists of an AC source $V_{\\text{in}}(t) = V_m \\sin(\\omega t)$, an ideal or forward-biased diode, and a parallel RC load. When $V_{\\text{in}}(t) > V_C(t) + V_D$, the diode conducts, charging the capacitor to the peak voltage. When $V_{\\text{in}}(t)$ drops below $V_C(t)$, the diode turns off and the capacitor discharges through load resistance $R_L$ with time constant $\\tau = R_L C$. The peak-to-peak ripple voltage is approximately $V_r \\approx \\frac{V_m}{f R_L C}$.',
                'algorithm' => '1. <strong>Input Signal:</strong> Generate sinusoidal input $V_{\\text{in}}(t_n) = V_m \\sin(\\omega t_n)$.<br>2. <strong>Diode State Evaluation:</strong> Check conduction condition with an <code>if</code> statement: if $V_{\\text{in}} > V_C + V_D$, set $V_C = V_{\\text{in}} - V_D$.<br>3. <strong>Discharge Phase:</strong> When diode is reverse-biased, update $V_{C, n+1} = V_{C, n} \\left(1 - \\frac{\\Delta t}{R_L C}\\right)$.<br>4. <strong>Ripple Measurement:</strong> Compute ripple factor and DC average output voltage.',
                'exercises' => [
                    'Simulate the rectifier output for different load resistances and verify that ripple voltage decreases as $R_L C$ increases.',
                    'Compare the ripple factor of the half-wave rectifier with a full-wave bridge rectifier topology.',
                    'Evaluate the diode reverse recovery and peak inverse voltage (PIV).'
                ],
                'references' => [
                    'Paul Horowitz and Winfield Hill, "The Art of Electronics", Cambridge University Press (3rd ed., 2015).'
                ]
            ]
        ];

        // Retrieve matched subtopic guide if defined
        if (isset($subtopic_guides[$key])) {
            return $subtopic_guides[$key];
        }

        // Intelligently assembled pedagogical guide tailored directly to $subtopic_title and $chapter_title
        return [
            'title' => $safe_chapter . ': ' . $safe_subtopic . ' - Computational Modeling',
            'overview' => 'This curriculum module explores the computational implementation of <strong>' . $safe_subtopic . '</strong> within the study of <strong>' . $safe_chapter . '</strong>. In university computational physics, these algorithms translate analytical equations into numerical algorithms, enabling students to simulate physical systems and analyze experimental data systematically.',
            'formulation' => 'The mathematical formulation for <strong>' . $safe_subtopic . '</strong> structures physical relationships into algorithmic procedures. Depending on the physical context, computations involve discretizing spatial or temporal coordinates ($x_i = x_0 + i\\Delta x$, $t_n = t_0 + n\\Delta t$), evaluating physical boundary conditions, and controlling truncation errors to ensure convergence to analytical limiting solutions.',
            'algorithm' => '1. <strong>Initialization:</strong> Define physical constants, parameter bounds, and initial conditions for ' . $safe_subtopic . '.<br>2. <strong>Numerical Execution:</strong> Implement the algorithmic steps using Python data structures and optimized numerical libraries.<br>3. <strong>Error & Convergence Control:</strong> Monitor numerical stability and verify that simulation results remain physically meaningful.<br>4. <strong>Result Visualization:</strong> Output computed values and render scientific figures with labeled physical units.',
            'exercises' => [
                'Modify the parameters for ' . $safe_subtopic . ' and analyze how the simulation response changes under varying physical conditions.',
                'Perform a convergence study by refining the numerical discretization step size and observe the reduction in approximation error.',
                'Compare the numerical solution with an analytical benchmark or limiting case to validate the computational model.'
            ],
            'references' => [
                'Mark Newman, "Computational Physics", University of Michigan (2013).',
                'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).',
                'William H. Press et al., "Numerical Recipes: The Art of Scientific Computing", Cambridge University Press (3rd ed., 2007).'
            ]
        ];
    }
}
