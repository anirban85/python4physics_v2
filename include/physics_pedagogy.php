<?php
/**
 * Python4Physics - Academic Pedagogical Knowledge Engine
 * Generates comprehensive theoretical, mathematical, and algorithmic physics documentation
 * to ensure deep educational value, academic rigor, and adherence to Google Quality Guidelines.
 * 
 * Authored by Dr. Alorika Chatterjee & Dr. Anirban Shaw
 */

if (!function_exists('get_physics_pedagogical_guide')) {
    function get_physics_pedagogical_guide($menu_id, $submenu_id, $chapter_title, $subtopic_title) {
        $menu_id = intval($menu_id);
        $submenu_id = intval($submenu_id);
        $safe_chapter = htmlspecialchars($chapter_title, ENT_QUOTES, 'UTF-8');
        $safe_subtopic = htmlspecialchars($subtopic_title, ENT_QUOTES, 'UTF-8');

        // Chapter Pedagogical Guides
        // NOTE: All strings containing mathematical LaTeX ($...) MUST be enclosed in single quotes ('...') 
        // to prevent PHP from evaluating LaTeX math symbols ($c, $x, $p, $k_B, etc.) as PHP variables.
        $guides = [
            // Chapter 1: Introduction to Python
            1 => [
                'title' => 'Computational Foundations: Python in Scientific Physics',
                'overview' => 'Scientific computing in modern physics relies on high-level interpreted environments like Python to bridge abstract analytical equations and experimental data interpretation. This module introduces fundamental syntax, memory management, and numerical evaluation pipelines essential for computational physics laboratories.',
                'formulation' => 'In classical and quantum mechanics, physical equations balance dimensional physical constants (Planck\'s constant $\\hbar \\approx 1.054 \\times 10^{-34}\\text{ J}\\cdot\\text{s}$, speed of light $c \\approx 2.998 \\times 10^8\\text{ m/s}$, Boltzmann constant $k_B \\approx 1.380 \\times 10^{-23}\\text{ J/K}$) and dynamic variables (position $x$, linear momentum $p$, potential $V(x)$). Computations evaluate dimensionless numbers such as the Reynolds number $\\text{Re} = \\frac{\\rho v L}{\\mu}$ or the fine-structure constant $\\alpha = \\frac{e^2}{4\\pi\\varepsilon_0 \\hbar c} \\approx \\frac{1}{137.036}$. Floating-point operations adhere to IEEE 754 standard double precision with machine epsilon $\\epsilon_{\\text{mach}} \\approx 2.22 \\times 10^{-16}$.',
                'algorithm' => '1. <strong>Deterministic Initialisation:</strong> Define physical constants and initial state vectors.<br>2. <strong>Iteration Control:</strong> Use bounded <code>for</code> loops for fixed time-step evolutions and adaptive <code>while</code> loops for numerical convergence thresholds (e.g., $|x_{k+1} - x_k| < 10^{-7}$).<br>3. <strong>Modular Encapsulation:</strong> Structure acceleration functions $\\vec{a}(t, \\vec{r}, \\vec{v})$ as modular callables for plug-and-play integration with numerical ODE solvers.',
                'exercises' => [
                    'Evaluate the relativistic kinetic energy $K = (\\gamma - 1)mc^2$ where $\\gamma = 1/\\sqrt{1 - v^2/c^2}$ for velocities $v \\in [0.1c, 0.99c]$ and verify convergence to Newtonian $\\frac{1}{2}mv^2$ as $v \\to 0$.',
                    'Compute the blackbody radiation spectral radiance using Planck\'s law $u(\\lambda, T) = \\frac{8\\pi h c}{\\lambda^5 (e^{hc/\\lambda k_B T} - 1)}$ across optical wavelengths for $T = 3000\\text{ K}, 5800\\text{ K}, 8000\\text{ K}$.',
                    'Implement a convergence test evaluating the Leibniz series $\\pi = 4 \\sum_{n=0}^{\\infty} \\frac{(-1)^n}{2n+1}$ and calculate the asymptotic truncation error after $N = 10^4$ steps.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).',
                    'Hans Petter Langtangen, "A Primer on Scientific Programming with Python", Springer (2016).',
                    'Rubin H. Landau, Manuel J. Páez, and Cristian C. Bordeianu, "Computational Physics: Problem Solving with Python", Wiley-VCH (2015).'
                ]
            ],

            // Chapter 2: Operators & Expressions
            2 => [
                'title' => 'Arithmetic & Logical Operators in Mathematical Physics Modeling',
                'overview' => 'Mathematical operators represent physical operations: differential operators represent momentum and kinetic energy, while algebraic relations model conservation laws. Understanding operator precedence, bitwise logic, and IEEE floating-point arithmetic is fundamental to preventing catastrophic cancellation errors in numerical physics.',
                'formulation' => 'In computational modeling, arithmetic operations approximate continuum mechanics. When subtracting nearly equal floating-point numbers (e.g., $f(x+h) - f(x)$ in finite-difference differentiation), severe cancellation occurs: $\\Delta y = f(x+h) - f(x) \\sim \\mathcal{O}(\\epsilon_{\\text{mach}})$. Logical operators underpin piecewise physical potentials (such as finite square wells $V(x) = 0$ for $|x| < a$ and $V_0$ elsewhere) and event detection in molecular dynamics simulations.',
                'algorithm' => '1. <strong>Precision Preservation:</strong> Reformulate ill-conditioned mathematical expressions to avoid subtraction of nearly identical magnitudes (e.g., use trigonometric identities $\\sin^2(x/2) = \\frac{1-\\cos x}{2}$).<br>2. <strong>Vectorized Operators:</strong> Apply element-wise array operators rather than interpreted loops to maximize SIMD throughput.<br>3. <strong>Boolean Masking:</strong> Use condition masks to apply boundary constraints and spatial barrier potentials efficiently.',
                'exercises' => [
                    'Demonstrate floating-point cancellation by computing $f(x) = \\frac{1 - \\cos(x)}{x^2}$ for very small $x = 10^{-8}$, and compare against the Taylor expansion $f(x) \\approx \\frac{1}{2} - \\frac{x^2}{24}$.',
                    'Implement a conditional piecewise potential for a double square well barrier and evaluate particle reflection conditions.',
                    'Construct a binary bitwise algorithm to model Ising spin lattice configurations ($s_i = \\pm 1$) and compute total lattice energy.'
                ],
                'references' => [
                    'David Goldberg, "What Every Computer Scientist Should Know About Floating-Point Arithmetic", ACM Computing Surveys (1991).',
                    'Paul L. DeVries and Javier E. Hasbun, "A First Course in Computational Physics", Jones & Bartlett (2011).'
                ]
            ],

            // Chapter 3: Conditional Statements & Branching
            3 => [
                'title' => 'Branching Logic & Phase Space Boundary Conditions',
                'overview' => 'Physical systems frequently transition across discrete boundaries—such as elastic collisions with walls, phase changes at critical temperatures, or discontinuous potential barriers. Conditional branching allows numerical algorithms to handle segmented physical laws dynamically.',
                'formulation' => 'Piecewise physical equations are standard in electrostatics and mechanics: for example, the electric field of a uniformly charged solid sphere of radius $R$ and total charge $Q$ is given by $E(r) = \\frac{Q r}{4\\pi\\varepsilon_0 R^3}$ for $r < R$, and $E(r) = \\frac{Q}{4\\pi\\varepsilon_0 r^2}$ for $r \\ge R$. Accurate branching preserves flux continuity across $r = R$.',
                'algorithm' => '1. <strong>Boundary Checking:</strong> Evaluate coordinate bounds at every step $\\Delta t$.<br>2. <strong>State Transition:</strong> Update particle velocity upon collision (e.g., $v_x \\leftarrow -e v_x$ where $e$ is the coefficient of restitution).<br>3. <strong>Event Root-Finding:</strong> Interpolate between time steps $(t_n, t_{n+1})$ to identify exact collision contact times.',
                'exercises' => [
                    'Simulate a bouncing ball with gravity $g = 9.8\\text{ m/s}^2$ and coefficient of restitution $e = 0.85$, terminating execution when bounce height falls below $10^{-4}\\text{ m}$.',
                    'Calculate the radial electric field and electrostatic potential for concentric conducting spherical shells with conditional zone evaluation.',
                    'Implement a threshold-based thermostat algorithm (Berendsen velocity rescaling) in a simulated gas ensemble.'
                ],
                'references' => [
                    'Harvey Gould, Jan Tobochnik, and Wolfgang Christian, "An Introduction to Computer Simulation Methods: Applications to Physical Systems", Addison-Wesley (2006).'
                ]
            ],

            // Chapter 4: Loops & Iterative Algorithms
            4 => [
                'title' => 'Iterative Loops & Time-Evolution in Dynamical Systems',
                'overview' => 'Almost all numerical physics problems boil down to iterative loops: stepping forward in time $\\Delta t$ for dynamical equations, or converging toward equilibrium across relaxation sweeps. Efficient iteration design directly determines computational speed and numerical stability.',
                'formulation' => 'In iterative relaxation of Laplace\'s equation $\\nabla^2 \\Phi = 0$ on a grid, the potential at node $(i, j)$ satisfies the mean value theorem: $\\Phi_{i, j}^{(k+1)} = \\frac{1}{4}\\left(\\Phi_{i+1, j}^{(k)} + \\Phi_{i-1, j}^{(k)} + \\Phi_{i, j+1}^{(k)} + \\Phi_{i, j-1}^{(k)}\\right)$. Iterations continue until the residual norm satisfies $\\max |\\Phi_{i, j}^{(k+1)} - \\Phi_{i, j}^{(k)}| < \\delta$.',
                'algorithm' => '1. <strong>Loop Invariants:</strong> Precompute constant coefficients outside loop blocks.<br>2. <strong>Convergence Monitoring:</strong> Track relative error $\\epsilon = \\frac{|x_{k+1} - x_k|}{|x_{k+1}|}$ and set safe maximum iteration guards.<br>3. <strong>State History Logging:</strong> Buffer coordinates into contiguous arrays for scientific plotting.',
                'exercises' => [
                    'Implement the Jacobi iterative relaxation method to solve the electric potential in a square cavity with one wall held at $V = 100\\text{ V}$ and others grounded.',
                    'Plot the bifurcation diagram for the logistic map $x_{n+1} = r x_n (1 - x_n)$ for $r \\in [2.5, 4.0]$ demonstrating period-doubling route to chaos.',
                    'Compute the trajectory of a projectile with aerodynamic air drag proportional to $v^2$ using an iterative Euler integration loop.'
                ],
                'references' => [
                    'William H. Press, Saul A. Teukolsky, William T. Vetterling, and Brian P. Flannery, "Numerical Recipes: The Art of Scientific Computing", Cambridge University Press (3rd ed., 2007).'
                ]
            ],

            // Chapter 5: Functions & Modular Scientific Programming
            5 => [
                'title' => 'Functional Modularization & Differential Operator Abstraction',
                'overview' => 'Modular functional programming separates physical laws (force fields, potentials) from numerical integration routines (Euler, Verlet, Runge-Kutta). This allows identical numerical solvers to be tested across diverse physical scenarios without code duplication.',
                'formulation' => 'A physical differential system is abstracted as an autonomous dynamical map $\\frac{d\\vec{y}}{dt} = \\vec{f}(t, \\vec{y})$. By treating $\\vec{f}$ as a first-class function, high-order solvers evaluate vector derivatives $\\vec{k}_i$ agnostic of whether the system describes Keplerian orbits, Duffing oscillators, or chemical kinetics.',
                'algorithm' => '1. <strong>Pure Functions:</strong> Ensure derivative functions have no side effects.<br>2. <strong>Higher-Order Integrator:</strong> Write generalized integrators <code>rk4(f, y0, t)</code> accepting function handles.<br>3. <strong>Vectorized Returns:</strong> Return NumPy arrays representing phase-space velocity and acceleration.',
                'exercises' => [
                    'Write a generic RK4 solver function and pass both a simple harmonic oscillator force law and a Van der Pol nonlinear damping function into it.',
                    'Create a modular gravitational $N$-body force calculator and simulate the Sun-Earth-Moon three-body system.',
                    'Implement a modular Python decorator to profile execution time and call counts of physical gradient calculations.'
                ],
                'references' => [
                    'Claudio Canuto and Anita Tabacco, "Mathematical Analysis II", Springer (2015).'
                ]
            ],

            // Chapter 6: Ordinary Differential Equations - 1
            6 => [
                'title' => 'Ordinary Differential Equations & Runge-Kutta Integration Schemes',
                'overview' => 'Ordinary differential equations (ODEs) govern virtually all fundamental laws of classical physics, including Newton\'s second law $\\vec{F} = m\\frac{d^2\\vec{r}}{dt^2}$, radioactive decay $\\frac{dN}{dt} = -\\lambda N$, and RLC circuit transients $L\\frac{d^2q}{dt^2} + R\\frac{dq}{dt} + \\frac{q}{C} = 0$. Because closed-form analytical solutions exist only for idealized linear potentials, numerical integrators are indispensable for investigating realistic physical phenomena.',
                'formulation' => 'Any $n$-th order ODE converts into a coupled system of $n$ first-order equations $\\frac{d\\vec{y}}{dt} = \\vec{f}(t, \\vec{y})$. For the classic 4th-order Runge-Kutta (RK4) integrator with step size $h = \\Delta t$:\n$$\\vec{k}_1 = \\vec{f}(t_n, \\vec{y}_n)$$\n$$\\vec{k}_2 = \\vec{f}\\left(t_n + \\frac{h}{2}, \\vec{y}_n + \\frac{h}{2}\\vec{k}_1\\right)$$\n$$\\vec{k}_3 = \\vec{f}\\left(t_n + \\frac{h}{2}, \\vec{y}_n + \\frac{h}{2}\\vec{k}_2\\right)$$\n$$\\vec{k}_4 = \\vec{f}(t_n + h, \\vec{y}_n + h\\vec{k}_3)$$\n$$\\vec{y}_{n+1} = \\vec{y}_n + \\frac{h}{6}\\left(\\vec{k}_1 + 2\\vec{k}_2 + 2\\vec{k}_3 + \\vec{k}_4\\right)$$\nThe local truncation error is $\\mathcal{O}(h^5)$, yielding a global error of $\\mathcal{O}(h^4)$, providing superior energy conservation compared to first-order Euler $\\mathcal{O}(h)$.',
                'algorithm' => '1. <strong>State Vector Setup:</strong> Define $\\vec{y} = [x, v]^T$ with initial conditions $x(0) = x_0$ and $v(0) = v_0$.<br>2. <strong>Derivative Operator:</strong> Define $\\vec{f}(t, \\vec{y}) = [v, a(t, x, v)]^T$ combining conservative and dissipative forces.<br>3. <strong>Stepping Pipeline:</strong> Advance time in uniform increments $\\Delta t$, updating position and momentum simultaneously.<br>4. <strong>Energy Diagnostics:</strong> Continuously monitor total mechanical energy $E = \\frac{1}{2}mv^2 + V(x)$ to track numerical drift.',
                'exercises' => [
                    'Compare energy drift in a simple harmonic oscillator $\\ddot{x} + \\omega^2 x = 0$ simulated with standard Euler vs. Euler-Cromer vs. RK4 over $100$ complete oscillation periods.',
                    'Introduce a velocity-squared damping term $F_{\\text{drag}} = -b v |v|$ and investigate the phase space trajectory $(x, \\dot{x})$ as it spirals toward a stable attractor.',
                    'Simulate the nonlinear pendulum $\\ddot{\\theta} + \\frac{g}{L}\\sin\\theta = 0$ for large initial release angles $\\theta_0 = 170^\\circ$ and measure the period elongation relative to the small-angle approximation $T_0 = 2\\pi\\sqrt{L/g}$.'
                ],
                'references' => [
                    'John R. Taylor, "Classical Mechanics", University Science Books (2005).',
                    'William H. Press et al., "Numerical Recipes: The Art of Scientific Computing", Cambridge University Press (2007).',
                    'David Morin, "Introduction to Classical Mechanics", Cambridge University Press (2008).'
                ]
            ],

            // Chapter 7: Introduction to NumPy
            7 => [
                'title' => 'Vectorized Array Computing & Discrete Physics Fields via NumPy',
                'overview' => 'NumPy forms the high-performance numerical computing bedrock of computational physics. Its multi-dimensional <code>ndarray</code> architecture executes compiled C/Fortran SIMD vector instructions, eliminating interpreted loop overhead and enabling large-scale field discretization.',
                'formulation' => 'In multi-particle and field simulations, physical variables represent spatial lattices: electric potential grids $\\Phi(x, y)$, electromagnetic tensors, or $N$-body state vectors. Vectorized broadcasting computes pairwise distances $\\vec{r}_{ij} = \\vec{r}_j - \\vec{r}_i$ and spatial finite-difference Laplacian stencils $\\nabla^2 \\Phi \\approx \\frac{\\Phi_{i+1, j} + \\Phi_{i-1, j} + \\Phi_{i, j+1} + \\Phi_{i, j-1} - 4\\Phi_{i, j}}{(\\Delta x)^2}$ across entire lattices in a single operation.',
                'algorithm' => '1. <strong>Memory Contiguity:</strong> Allocate C-contiguous NumPy arrays for optimal CPU L1/L2 cache locality.<br>2. <strong>Vectorized Broadcasting:</strong> Replace nested loops with vectorized broadcasting formulas like <code>r = np.sqrt(x**2 + y**2)</code>.<br>3. <strong>Linear Algebra Subroutines:</strong> Use <code>np.linalg.eig</code>, <code>np.linalg.inv</code>, and <code>np.linalg.solve</code> for coupled oscillator normal modes and matrix mechanics.',
                'exercises' => [
                    'Construct a 2D meshgrid representing a point electric dipole with charges $+q$ and $-q$ separated by distance $d$, and compute electric field vectors $\\vec{E} = -\\vec{\\nabla}\\Phi$ using <code>np.gradient</code>.',
                    'Generate a Monte Carlo random walk of $N = 10^4$ steps in three dimensions and calculate the root-mean-square displacement $\\sqrt{\\langle r^2 \\rangle}$ to confirm the diffusion law $\\langle r^2 \\rangle = 6 D t$.',
                    'Demonstrate array broadcasting efficiency by benchmarking the distance matrix calculation between $N = 1000$ particles using nested Python loops vs. vectorized NumPy broadcasting.'
                ],
                'references' => [
                    'Travis E. Oliphant, "Guide to NumPy", Continuum Press (2006).',
                    'Charles R. Harris et al., "Array programming with NumPy", Nature 585, 357–362 (2020).'
                ]
            ],

            // Chapter 12: Numerical Integration
            12 => [
                'title' => 'Numerical Quadrature & Integral Formulations in Physics',
                'overview' => 'Definite integrals appear throughout physics when calculating classical work $W = \\int_{x_1}^{x_2} F(x) dx$, center of mass, gravitational potentials, and quantum mechanical wave function normalization $\\int_{-\\infty}^{\\infty} |\\psi(x)|^2 dx = 1$. When integrands are non-integrable analytically, numerical quadrature provides accurate solutions.',
                'formulation' => 'Standard numerical quadrature partitions the integration domain $[a, b]$ into $N$ intervals of step width $h = (b - a)/N$:\n$$\\text{Trapezoidal Rule: } I_{\\text{trap}} = \\frac{h}{2} \\left[ f(a) + 2\\sum_{i=1}^{N-1} f(x_i) + f(b) \\right] - \\frac{(b-a)h^2}{12} f\'\'(\\xi)$$\n$$\\text{Simpson\'s 1/3 Rule: } I_{\\text{simp}} = \\frac{h}{3} \\left[ f(a) + 4\\sum_{i=1, 3, \\dots}^{N-1} f(x_i) + 2\\sum_{i=2, 4, \\dots}^{N-2} f(x_i) + f(b) \\right] - \\frac{(b-a)h^4}{180} f^{(4)}(\\xi)$$\nTrapezoidal integration has global error $\\mathcal{O}(h^2)$, while Simpson\'s rule achieves $\\mathcal{O}(h^4)$ accuracy by approximating segments with parabolas.',
                'algorithm' => '1. <strong>Domain Discretization:</strong> Partition the integration range into an even number of intervals $N$ for Simpson\'s rule.<br>2. <strong>Vectorized Weights:</strong> Formulate weight arrays ($1, 4, 2, 4, \\dots, 1$) and compute the dot product with function evaluations.<br>3. <strong>Richardson Extrapolation:</strong> Combine step evaluations $I(h)$ and $I(h/2)$ to cancel leading-order error terms systematically.',
                'exercises' => [
                    'Compute the total electrostatic potential $\\Phi(z)$ on the axis of a uniformly charged circular ring of radius $R$ by numerical integration, and verify agreement with $\\Phi(z) = \\frac{Q}{4\\pi\\varepsilon_0 \\sqrt{R^2 + z^2}}$.',
                    'Plot the relative error $|I_{\\text{num}} - I_{\\text{exact}}|/I_{\\text{exact}}$ on a log-log scale against step size $h$ for both Trapezoidal and Simpson\'s methods to demonstrate $\\mathcal{O}(h^2)$ and $\\mathcal{O}(h^4)$ slopes.',
                    'Apply Gaussian quadrature to evaluate the period of a large-amplitude pendulum $T = 4\\sqrt{\\frac{L}{g}} \\int_0^{\\pi/2} \\frac{d\\phi}{\\sqrt{1 - k^2 \\sin^2\\phi}}$ where $k = \\sin(\\theta_0/2)$.'
                ],
                'references' => [
                    'Philip J. Davis and Philip Rabinowitz, "Methods of Numerical Integration", Dover Publications (2007).',
                    'Richard L. Burden and J. Douglas Faires, "Numerical Analysis", Cengage Learning (2015).'
                ]
            ],

            // Chapter 19: Time-Independent Schrödinger Equation (TISE)
            19 => [
                'title' => 'Time-Independent Schrödinger Equation & Quantum Bound States',
                'overview' => 'The 1D Time-Independent Schrödinger Equation (TISE) is the cornerstone of non-relativistic quantum mechanics, determining stationary state wave functions $\\psi_n(x)$ and discrete bound-state energy eigenvalues $E_n$. Numerical solvers determine energy levels and wavefunctions for arbitrary potential wells.',
                'formulation' => 'The 1D TISE takes the eigenvalue form:\n$$-\\frac{\\hbar^2}{2m} \\frac{d^2\\psi(x)}{dx^2} + V(x)\\psi(x) = E\\psi(x)$$\nDiscretizing space on a uniform grid $x_i = x_0 + i\\Delta x$ with finite difference $\\frac{d^2\\psi}{dx^2} \\approx \\frac{\\psi_{i+1} - 2\\psi_i + \\psi_{i-1}}{(\\Delta x)^2}$ transforms the continuous differential operator into a tridiagonal Hamiltonian matrix $\\hat{H}\\vec{\\psi} = E\\vec{\\psi}$:\n$$H_{i, i} = \\frac{\\hbar^2}{m(\\Delta x)^2} + V(x_i), \\quad H_{i, i+1} = H_{i+1, i} = -\\frac{\\hbar^2}{2m(\\Delta x)^2}$$\nDiagonalizing $\\hat{H}$ via <code>scipy.linalg.eigh</code> yields the entire discrete bound spectrum $E_0, E_1, \\dots$ simultaneously.',
                'algorithm' => '1. <strong>Grid Boundaries:</strong> Choose spatial boundaries $[-L, L]$ wide enough so bound states decay exponentially toward zero.<br>2. <strong>Tridiagonal Assembly:</strong> Construct the kinetic difference matrix $\\hat{T}$ and diagonal potential matrix $\\hat{V}$.<br>3. <strong>Hermitian Diagonalization:</strong> Solve $\\hat{H}\\psi_n = E_n\\psi_n$ using optimized symmetric/Hermitian eigenvalue solvers.<br>4. <strong>Wavefunction Normalization:</strong> Enforce $\\int |\\psi_n(x)|^2 dx = 1$ via numerical trapezoidal quadrature.',
                'exercises' => [
                    'Solve for the first four eigen-energies of the quantum harmonic oscillator $V(x) = \\frac{1}{2}m\\omega^2 x^2$ and verify that $E_n = \\left(n + \\frac{1}{2}\\right)\\hbar\\omega$ with equidistant spacing $\\Delta E = \\hbar\\omega$.',
                    'Simulate quantum tunneling through a rectangular potential barrier $V(x) = V_0$ for $0 \\le x \\le a$ with $E < V_0$, and compare numerical transmission coefficients $T$ with the analytical barrier penetration formula.',
                    'Model a double potential well $V(x) = a x^4 - b x^2$ and demonstrate quantum energy level splitting between symmetric and antisymmetric parity wavefunctions.'
                ],
                'references' => [
                    'David J. Griffiths and Darrell F. Schroeter, "Introduction to Quantum Mechanics", Cambridge University Press (3rd ed., 2018).',
                    'Nouredine Zettili, "Quantum Mechanics: Concepts and Applications", Wiley (2009).'
                ]
            ],

            // Chapter 20: Time-Dependent Schrödinger Equation (TDSE)
            20 => [
                'title' => 'Time-Dependent Schrödinger Equation & Unitary Wave Packet Dynamics',
                'overview' => 'The Time-Dependent Schrödinger Equation (TDSE) describes dynamic spatio-temporal evolution of quantum states $\\Psi(x, t)$, modeling wave packet dispersion, barrier tunneling, and coherent quantum dynamics in real time.',
                'formulation' => 'The 1D TDSE is given by:\n$$i\\hbar \\frac{\\partial\\Psi(x, t)}{\\partial t} = -\\frac{\\hbar^2}{2m} \\frac{\\partial^2\\Psi(x, t)}{\\partial x^2} + V(x)\\Psi(x, t)$$\nStandard explicit Euler time-stepping is unconditionally unstable for Schrödinger evolution. The <strong>Crank-Nicolson scheme</strong> provides an unconditionally stable, unitary, and norm-conserving implicit integrator:\n$$\\left( \\hat{I} + \\frac{i\\Delta t}{2\\hbar}\\hat{H} \\right) \\Psi(x, t + \\Delta t) = \\left( \\hat{I} - \\frac{i\\Delta t}{2\\hbar}\\hat{H} \\right) \\Psi(x, t)$$\nBecause the Cayley evolution operator $\\hat{U}(\\Delta t) = \\frac{\\hat{I} - i\\Delta t\\hat{H}/2}{\\hat{I} + i\\Delta t\\hat{H}/2}$ is strictly unitary ($|\\hat{U}| = 1$), the total probability $\\int |\\Psi(x, t)|^2 dx = 1$ is conserved exactly across all time increments.',
                'algorithm' => '1. <strong>Wave Packet Preparation:</strong> Initialize a normalized Gaussian packet $\\Psi(x, 0) = \\frac{1}{(2\\pi\\sigma^2)^{1/4}} e^{-\\frac{(x-x_0)^2}{4\\sigma^2}} e^{i k_0 x}$ with group velocity $v_g = \\hbar k_0 / m$.<br>2. <strong>Banded Matrix Setup:</strong> Assemble tridiagonal Crank-Nicolson matrices $\\mathbf{A}$ and $\\mathbf{B}$.<br>3. <strong>Thomas Algorithm:</strong> Invert tridiagonal equations at each time increment $\\Delta t$.<br>4. <strong>Unitarity Verification:</strong> Continuously monitor $\\sum |\\Psi_i|^2 \\Delta x = 1.000000$ to confirm numerical stability.',
                'exercises' => [
                    'Simulate wave packet spreading in free space ($V(x) = 0$) and plot packet width $\\sigma(t)$ vs. time to verify theoretical dispersion $\\sigma(t) = \\sigma_0 \\sqrt{1 + \\left(\\frac{\\hbar t}{2m\\sigma_0^2}\\right)^2}$.',
                    'Scatter a Gaussian wave packet against a step potential $V(x) = V_0 \\Theta(x)$ and compute the reflected and transmitted probability densities by integrating $|\\Psi(x, t)|^2$ on each side of the boundary.',
                    'Animate a wave packet trapped in a harmonic oscillator potential and observe coherent oscillations at frequency $\\omega$ without change in wave packet shape.'
                ],
                'references' => [
                    'Claude Cohen-Tannoudji, Bernard Diu, and Franck Laloë, "Quantum Mechanics", Wiley-VCH (2019).',
                    'P. L. DeVries and J. E. Hasbun, "A First Course in Computational Physics", Jones & Bartlett (2011).'
                ]
            ]
        ];

        // Retrieve Chapter-Specific Guide or Construct High-Quality Academic Contextual Fallback
        if (isset($guides[$menu_id])) {
            $guide = $guides[$menu_id];
        } else {
            $guide = [
                'title' => $safe_chapter . ': ' . $safe_subtopic . ' - Computational Physics Analysis',
                'overview' => 'This module investigates the physical principles and numerical methods underlying <strong>' . $safe_subtopic . '</strong> within the curriculum of <strong>' . $safe_chapter . '</strong>. In university computational physics, these algorithms bridge analytical theory and computer-aided simulation, enabling students to explore physical phenomena beyond the constraints of idealized textbook approximations.',
                'formulation' => 'The mathematical formulation for <strong>' . $safe_subtopic . '</strong> involves discretizing continuous physical equations onto finite computational grids or phase-space state vectors. Key numerical metrics include controlling truncation errors $\\mathcal{O}(\\Delta t^p, \\Delta x^q)$, evaluating stability criteria (such as the Courant-Friedrichs-Lewy condition $\\Delta t \\le \\Delta x / v_{\\text{max}}$ for wave dynamics), and preserving fundamental conservation invariants (total mechanical energy $E$, linear momentum $\\vec{P}$, or probability norm $\\int |\\psi|^2 dx = 1$).',
                'algorithm' => '1. <strong>Physical Model Specification:</strong> Define physical parameters, dimensioned constants, and initial/boundary state vectors.<br>2. <strong>Vectorized Computation:</strong> Execute numerical equations using NumPy/SciPy linear algebra and array broadcasting to ensure high performance.<br>3. <strong>Convergence & Conservation Verification:</strong> Implement automated checks to monitor energy conservation and residual error thresholds.<br>4. <strong>Scientific Visualization:</strong> Render publication-quality figures with labeled axes, physical units, and analytical reference curves.',
                'exercises' => [
                    'Modify the initial parameters or boundary conditions for ' . $safe_subtopic . ' and evaluate the response of the physical system to perturbations.',
                    'Perform a step-size refinement study by halving $\\Delta t$ (or spatial grid spacing $\\Delta x$) and confirm the empirical order of numerical convergence.',
                    'Compare the computed simulation trajectory with an analytical asymptotic solution or physical limiting case.'
                ],
                'references' => [
                    'Mark Newman, "Computational Physics", University of Michigan (2013).',
                    'William H. Press et al., "Numerical Recipes: The Art of Scientific Computing", Cambridge University Press (3rd ed., 2007).',
                    'David Halliday, Robert Resnick, and Jearl Walker, "Fundamentals of Physics", Wiley (11th ed., 2018).'
                ]
            ];
        }

        // Guarantee all expected keys are set
        $guide['title'] = $guide['title'] ?? ($safe_chapter . ': ' . $safe_subtopic);
        $guide['overview'] = $guide['overview'] ?? '';
        $guide['formulation'] = $guide['formulation'] ?? '';
        $guide['algorithm'] = $guide['algorithm'] ?? '';
        $guide['exercises'] = $guide['exercises'] ?? [];
        $guide['references'] = $guide['references'] ?? [
            'Mark Newman, "Computational Physics", University of Michigan (2013).',
            'William H. Press et al., "Numerical Recipes: The Art of Scientific Computing", Cambridge University Press (2007).'
        ];

        return $guide;
    }
}
