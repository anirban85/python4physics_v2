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

        // Core Chapter Pedagogical Database
        $guides = [
            // Chapter 1: Introduction to Python
            1 => [
                'overview' => "Scientific computing in modern physics relies heavily on high-level interpreted languages like Python to bridge abstract analytical equations and experimental data interpretation. This module introduces fundamental syntax, memory management, and numerical evaluation pipelines necessary for scientific programming.",
                'physics_context' => "In classical and quantum mechanics, physical quantities are expressed in terms of scalar constants (e.g., Planck's constant $\\hbar$, speed of light $c$, Boltzmann constant $k_B$) and dynamic variables (position $x$, momentum $p$, potential $V(x)$). Mastering Python variables, floating-point precision (IEEE 754 standard), and control flow structures allows physicists to simulate physical models deterministically.",
                'math_formulation' => "Computations often evaluate dimensionless physical parameters such as the Reynolds number $Re = \\frac{\\rho v L}{\\mu}$ in fluid dynamics or the fine-structure constant $\\alpha = \\frac{e^2}{4\\pi\\varepsilon_0 \\hbar c} \\approx \\frac{1}{137.036}$. Floating-point operations require awareness of round-off errors and machine epsilon $\\epsilon_{\\text{mach}} \\approx 2.22 \\times 10^{-16}$ for standard 64-bit IEEE floats.",
                'algorithm_notes' => "1. Initialization: Declare physical constants and experimental state variables.\n2. Iterative Loops: Use deterministic <code>for</code> loops for fixed time-step evolutions and <code>while</code> loops for threshold convergence criteria (e.g., error $< 10^{-6}$).\n3. Function Modularization: Encapsulate force laws and differential operators as pure functions for reusability across numerical integrators.",
                'exercises' => [
                    "Evaluate the relativistic kinetic energy $K = (\\gamma - 1)mc^2$ where $\\gamma = 1/\\sqrt{1 - v^2/c^2}$ for speeds from $0.1c$ to $0.99c$, and verify convergence to Newtonian $\\frac{1}{2}mv^2$ as $v \\to 0$.",
                    "Compute the blackbody radiation spectral radiance using Planck's law $u(\\lambda, T) = \\frac{8\\pi h c}{\\lambda^5 (e^{hc/\\lambda k_B T} - 1)}$ across optical wavelengths.",
                    "Implement a convergence test evaluating the infinite series for $\\pi = 4 \\sum_{n=0}^{\\infty} \\frac{(-1)^n}{2n+1}$ (Leibniz formula) and calculate the truncation error after $N=10^4$ iterations."
                ]
            ],

            // Chapter 6: Ordinary Differential Equations - 1
            6 => [
                'overview' => "Ordinary differential equations (ODEs) govern virtually all fundamental laws of classical physics, including Newton's second law $\\vec{F} = m\\frac{d^2\\vec{r}}{dt^2}$, radioactive decay $\\frac{dN}{dt} = -\\lambda N$, and RLC circuit transients $L\\frac{d^2q}{dt^2} + R\\frac{dq}{dt} + \\frac{q}{C} = 0$.",
                'physics_context' => "Because analytical closed-form solutions exist only for idealized linear potentials, numerical integration techniques like Euler's method, the Euler-Cromer algorithm, and Runge-Kutta schemes (RK2 and RK4) are essential to simulate realistic nonlinear physical phenomena such as aerodynamic drag, chaotic pendula, and gravitational planetary orbits.",
                'math_formulation' => "Any $n$-th order ODE can be converted into a system of $n$ coupled first-order equations $\\frac{d\\vec{y}}{dt} = \\vec{f}(t, \\vec{y})$. For the classic 4th-order Runge-Kutta (RK4) integrator with step size $h = \\Delta t$:\n$$\\vec{k}_1 = \\vec{f}(t_n, \\vec{y}_n)$$\n$$\\vec{k}_2 = \\vec{f}\\left(t_n + \\frac{h}{2}, \\vec{y}_n + \\frac{h}{2}\\vec{k}_1\\right)$$\n$$\\vec{k}_3 = \\vec{f}\\left(t_n + \\frac{h}{2}, \\vec{y}_n + \\frac{h}{2}\\vec{k}_2\\right)$$\n$$\\vec{k}_4 = \\vec{f}(t_n + h, \\vec{y}_n + h\\vec{k}_3)$$\n$$\\vec{y}_{n+1} = \\vec{y}_n + \\frac{h}{6}\\left(\\vec{k}_1 + 2\\vec{k}_2 + 2\\vec{k}_3 + \\vec{k}_4\\right)$$\nThe local truncation error is $\\mathcal{O}(h^5)$, yielding a global error of $\\mathcal{O}(h^4)$, providing superior energy conservation compared to first-order Euler $\\mathcal{O}(h)$.",
                'algorithm_notes' => "1. State Vector Setup: Define the state $\\vec{y} = [x, v]^T$ with initial conditions $x(0) = x_0$ and $v(0) = v_0$.\n2. Derivative Function: Implement $\\vec{f}(t, \\vec{y}) = [v, a(t, x, v)]^T$ modeling conservative and dissipative forces.\n3. Stepping Pipeline: Advance time in uniform increments $\\Delta t$, updating position and momentum simultaneously.\n4. Conservation Diagnostics: Continuously evaluate total mechanical energy $E = \\frac{1}{2}mv^2 + V(x)$ to monitor numerical drift.",
                'exercises' => [
                    "Compare energy drift in a simple harmonic oscillator $\\ddot{x} + \\omega^2 x = 0$ simulated with standard Euler vs. Euler-Cromer vs. RK4 over $100$ complete oscillation periods.",
                    "Introduce a velocity-squared damping term $F_{\\text{drag}} = -b v |v|$ and investigate the phase space trajectory $(x, \\dot{x})$ as it spirals toward a stable attractor.",
                    "Simulate the nonlinear pendulum $\\ddot{\\theta} + \\frac{g}{L}\\sin\\theta = 0$ for large initial release angles $\\theta_0 = 170^\\circ$ and measure the period elongation relative to the small-angle approximation $T_0 = 2\\pi\\sqrt{L/g}$."
                ]
            ],

            // Chapter 7: Introduction to NumPy
            7 => [
                'overview' => "NumPy forms the high-performance numerical computing bedrock of computational physics. Its multi-dimensional <code>ndarray</code> architecture executes compiled C/Fortran SIMD vector instructions, eliminating Python interpreter overhead.",
                'physics_context' => "In experimental physics and multi-particle simulations, datasets represent discretized fields (e.g., electric potential grid $\\Phi(x, y)$), spectral timeseries, or $N$-body position vectors. Vectorized array broadcasting allows evaluating mathematical expressions simultaneously over millions of lattice coordinates.",
                'math_formulation' => "Vectorized array calculations implement linear algebraic coordinate mappings $\\vec{r}_i = [x_i, y_i, z_i]^T$, discrete Fourier transforms $X_k = \\sum_{n=0}^{N-1} x_n e^{-i 2\\pi k n / N}$, and spatial finite-difference stencils $\\nabla^2 \\Phi \\approx \\frac{\\Phi_{i+1, j} + \\Phi_{i-1, j} + \\Phi_{i, j+1} + \\Phi_{i, j-1} - 4\\Phi_{i, j}}{(\\Delta x)^2}$.",
                'algorithm_notes' => "1. Memory Contiguity: NumPy arrays allocate continuous blocks of C-contiguous memory for optimal CPU cache utilization.\n2. Vectorized Broadcasting: Replace slow nested Python loops with array arithmetic like <code>r = np.sqrt(x**2 + y**2)</code>.\n3. Linear Algebra Submodule: Utilize <code>np.linalg.eig</code>, <code>np.linalg.inv</code>, and <code>np.linalg.solve</code> for coupled oscillator and quantum Hamiltonian diagonalization.",
                'exercises' => [
                    "Construct a 2D meshgrid representing a point electric dipole with charges $+q$ and $-q$ separated by distance $d$, and compute the electric field vectors $\\vec{E} = -\\vec{\\nabla}\\Phi$ using <code>np.gradient</code>.",
                    "Generate a Monte Carlo random walk of $N=10^4$ steps in three dimensions and calculate the root-mean-square displacement $\\sqrt{\\langle r^2 \\rangle}$ to confirm the diffusion law $\\langle r^2 \\rangle = 6 D t$.",
                    "Demonstrate array broadcasting efficiency by benchmarking the distance matrix calculation between $N=1000$ particles using nested Python loops vs. vectorized NumPy broadcasting."
                ]
            ],

            // Chapter 12: Numerical Integration
            12 => [
                'overview' => "Definite integrals appear throughout physics when calculating classical work $W = \\int_{x_1}^{x_2} F(x) dx$, center of mass coordinates, electric dipole moments, and quantum mechanical wave function normalization $\\int_{-\\infty}^{\\infty} |\\psi(x)|^2 dx = 1$.",
                'physics_context' => "When the integrand $f(x)$ is non-integrable analytically (such as the error function $\\text{erf}(x)$, elliptic integrals in nonlinear pendula, or Planck blackbody energy density integrals), numerical quadrature algorithms approximate the true area with high precision.",
                'math_formulation' => "Common numerical integration rules partition the domain $[a, b]$ into $N$ intervals of width $h = (b - a)/N$:\n* **Trapezoidal Rule:** Approximates $f(x)$ as linear segments, with global error $\\mathcal{O}(h^2)$:\n$$I_{\\text{trap}} = \\frac{h}{2} \\left[ f(a) + 2\\sum_{i=1}^{N-1} f(x_i) + f(b) \\right] - \\frac{(b-a)h^2}{12} f''(\\xi)$$\n* **Simpson's 1/3 Rule:** Approximates $f(x)$ as quadratic parabolas over pairs of intervals, with global error $\\mathcal{O}(h^4)$:\n$$I_{\\text{simp}} = \\frac{h}{3} \\left[ f(a) + 4\\sum_{i=1, 3, \\dots}^{N-1} f(x_i) + 2\\sum_{i=2, 4, \\dots}^{N-2} f(x_i) + f(b) \\right] - \\frac{(b-a)h^4}{180} f^{(4)}(\\xi)$$",
                'algorithm_notes' => "1. Interval Discretization: Partition domain into an even number of subintervals $N$ for Simpson's rule.\n2. Weight Vector: Apply appropriate quadrature weights ($1, 4, 2, 4, \\dots, 1$).\n3. Richardson Extrapolation / Romberg: Combine estimates from step sizes $h$ and $h/2$ to cancel leading error terms systematically.",
                'exercises' => [
                    "Compute the total electrostatic potential $\\Phi(z)$ on the axis of a uniformly charged circular ring of radius $R$ by numerical integration, and verify agreement with the exact formula $\\Phi(z) = \\frac{Q}{4\\pi\\varepsilon_0 \\sqrt{R^2 + z^2}}$.",
                    "Plot the relative error $|I_{\\text{num}} - I_{\\text{exact}}|/I_{\\text{exact}}$ on a log-log scale against step size $h$ for both Trapezoidal and Simpson's methods to demonstrate $\\mathcal{O}(h^2)$ and $\\mathcal{O}(h^4)$ slopes.",
                    "Apply Gaussian quadrature to evaluate the period of a large-amplitude pendulum $T = 4\\sqrt{\\frac{L}{g}} \\int_0^{\\pi/2} \\frac{d\\phi}{\\sqrt{1 - k^2 \\sin^2\\phi}}$ where $k = \\sin(\\theta_0/2)$."
                ]
            ],

            // Chapter 19: TISE (Time-Independent Schrödinger Equation)
            19 => [
                'overview' => "The 1D Time-Independent Schrödinger Equation (TISE) is the cornerstone of non-relativistic quantum mechanics, determining the stationary state wave functions $\\psi_n(x)$ and discrete bound-state energy eigenvalues $E_n$.",
                'physics_context' => "Unlike classical point particles that occupy definite trajectories, quantum particles are described by probability density amplitudes $|\\psi(x)|^2$. Numerical solvers enable physicists to determine energy levels and quantum tunneling probabilities for realistic potentials like the finite square well, Morse potential (molecular bonds), and double-well potentials (ammonia inversion).",
                'math_formulation' => "The 1D TISE takes the eigenvalue form:\n$$-\\frac{\\hbar^2}{2m} \\frac{d^2\\psi(x)}{dx^2} + V(x)\\psi(x) = E\\psi(x)$$\nDiscretizing space on a uniform grid $x_i = x_0 + i\\Delta x$ with finite difference $\\frac{d^2\\psi}{dx^2} \\approx \\frac{\\psi_{i+1} - 2\\psi_i + \\psi_{i-1}}{(\\Delta x)^2}$ transforms the continuous differential operator into a tridiagonal Hamiltonian matrix $\\hat{H}\\vec{\\psi} = E\\vec{\\psi}$:\n$$H_{i, i} = \\frac{\\hbar^2}{m(\\Delta x)^2} + V(x_i), \\quad H_{i, i+1} = H_{i+1, i} = -\\frac{\\hbar^2}{2m(\\Delta x)^2}$$\nDiagonalizing $\\hat{H}$ via <code>scipy.linalg.eigh</code> yields the entire discrete bound spectrum $E_0, E_1, \\dots$ simultaneously.",
                'algorithm_notes' => "1. Grid Specification: Choose spatial boundaries $[-L, L]$ wide enough so bound states decay exponentially toward zero.\n2. Tridiagonal Matrix Assembly: Construct the sparse or banded kinetic operator matrix $\\hat{T}$ and diagonal potential matrix $\\hat{V}$.\n3. Eigenvalue Extraction: Solve $\\hat{H}\\psi_n = E_n\\psi_n$ using Hermitian solver routines.\n4. Wavefunction Normalization: Ensure $\\int |\\psi_n(x)|^2 dx = 1$ via numerical trapezoidal quadrature.",
                'exercises' => [
                    "Solve for the first four eigen-energies of the quantum harmonic oscillator $V(x) = \\frac{1}{2}m\\omega^2 x^2$ and verify that $E_n = \\left(n + \\frac{1}{2}\\right)\\hbar\\omega$ with equidistant spacing $\\Delta E = \\hbar\\omega$.",
                    "Simulate quantum tunneling through a rectangular potential barrier $V(x) = V_0$ for $0 \\le x \\le a$ with $E < V_0$, and compare numerical transmission coefficients $T$ with the analytical barrier penetration formula.",
                    "Model a double potential well $V(x) = a x^4 - b x^2$ and demonstrate quantum energy level splitting between symmetric and antisymmetric parity wavefunctions."
                ]
            ],

            // Chapter 20: TDSE (Time-Dependent Schrödinger Equation)
            20 => [
                'overview' => "The Time-Dependent Schrödinger Equation (TDSE) describes the dynamic spatial-temporal evolution of quantum states $\\Psi(x, t)$, modeling wave packet dispersion, barrier tunneling, and coherent quantum dynamics.",
                'physics_context' => "Real-time quantum wave packet simulations visualize how localized initial wave packets $\\Psi(x, 0) = \\frac{1}{(2\\pi\\sigma^2)^{1/4}} e^{-\\frac{(x-x_0)^2}{4\\sigma^2}} e^{i k_0 x}$ spread over time in free space or scatter upon interacting with potential steps, demonstrating quantum reflection and transmission dynamically.",
                'math_formulation' => "The 1D TDSE is given by:\n$$i\\hbar \\frac{\\partial\\Psi(x, t)}{\\partial t} = -\\frac{\\hbar^2}{2m} \\frac{\\partial^2\\Psi(x, t)}{\\partial x^2} + V(x)\\Psi(x, t)$$\nStandard explicit Euler time-stepping is unconditionally unstable for Schrödinger evolution. The **Crank-Nicolson scheme** provides an unconditionally stable, unitary, and norm-conserving implicit integrator:\n$$\\left( \\hat{I} + \\frac{i\\Delta t}{2\\hbar}\\hat{H} \\right) \\Psi(x, t + \\Delta t) = \\left( \\hat{I} - \\frac{i\\Delta t}{2\\hbar}\\hat{H} \\right) \\Psi(x, t)$$\nBecause the Cayley evolution operator $\\hat{U}(\\Delta t) = \\frac{\\hat{I} - i\\Delta t\\hat{H}/2}{\\hat{I} + i\\Delta t\\hat{H}/2}$ is strictly unitary ($|\\hat{U}| = 1$), total probability $\\int |\\Psi(x, t)|^2 dx = 1$ is conserved exactly for all time steps.",
                'algorithm_notes' => "1. Initial Gaussian Packet: Prepare normalized Gaussian packet centered at $x_0$ with group velocity $v_g = \\hbar k_0 / m$.\n2. Tridiagonal System: Form the banded Crank-Nicolson matrices $A$ and $B$.\n3. Thomas Algorithm: Solve the tridiagonal linear equation system at each time increment $\\Delta t$.\n4. Norm Diagnostics: Verify $\\sum |\\Psi_i|^2 \\Delta x = 1.000000$ to ensure numerical stability.",
                'exercises' => [
                    "Simulate wave packet spreading in free space ($V(x) = 0$) and plot packet width $\\sigma(t)$ vs. time to verify theoretical dispersion $\\sigma(t) = \\sigma_0 \\sqrt{1 + \\left(\\frac{\\hbar t}{2m\\sigma_0^2}\\right)^2}$.",
                    "Scatter a Gaussian wave packet against a step potential $V(x) = V_0 \\Theta(x)$ and compute the reflected and transmitted probability densities by integrating $|\\Psi(x, t)|^2$ on each side of the boundary.",
                    "Animate a wave packet trapped in a harmonic oscillator potential and observe coherent oscillations at frequency $\\omega$ without change in wave packet shape."
                ]
            ]
        ];

        // Retrieve Chapter Specific Guide or Assemble Contextual Topic Guide
        $guide = $guides[$menu_id] ?? null;

        if (!$guide) {
            $guide = [
                'overview' => "This curriculum module covers <strong>{$subtopic_title}</strong> as part of <strong>{$chapter_title}</strong> in the Python4Physics computational physics curriculum.",
                'physics_context' => "In university-level computational physics, <strong>{$subtopic_title}</strong> serves as a fundamental building block for modeling physical phenomena, processing experimental datasets, and solving governing algebraic or differential equations. Computational physicists use these algorithms to simulate dynamical systems, analyze statistical ensembles, and construct accurate physical predictions.",
                'math_formulation' => "The mathematical framework underpinning <strong>{$subtopic_title}</strong> involves systematic discretization of physical variables over finite computational domains. Numerical convergence relies on controlling truncation errors $\\mathcal{O}(\\Delta x^p)$ and maintaining stability conditions (e.g., Courant-Friedrichs-Lewy conditions $\\Delta t \\le \\frac{\\Delta x}{v_{\\text{max}}}$ in hyperbolic PDEs).",
                'algorithm_notes' => "1. Domain & Variable Setup: Define physical constants, step sizes, and initial/boundary constraints.\n2. Iterative Matrix/Vector Execution: Perform vectorized mathematical operations using optimized numerical libraries.\n3. Convergence & Error Control: Monitor stopping criteria and preserve conservation laws (energy, momentum, probability norm).\n4. Data Visualization: Transform numerical arrays into clear scientific figures with dimensional units.",
                'exercises' => [
                    "Modify the initial parameters or boundary constraints for {$subtopic_title} and analyze how the physical solution responds to perturbation.",
                    "Perform a step-size refinement study by halving $\\Delta t$ (or $\\Delta x$) and verify the theoretical order of convergence.",
                    "Compare the numerical simulation results with an analytical asymptotic solution or physical limiting case."
                ]
            ];
        }

        return $guide;
    }
}
