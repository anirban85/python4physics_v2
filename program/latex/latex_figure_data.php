<?php
/**
 * Python4Physics - LaTeX Chapter 8 (Figures) Data Module
 * Provides structured educational LaTeX documents for all figure topics in physics.
 * Used for database seeding and live runtime fallback.
 */

function get_latex_figure_data() {
    return [
        // ==========================================
        // 8.1 Inserting Figures
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 1,
            'program_id' => 1,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Newtonian Force on an Accelerating Body}

According to Newton\'s Second Law of Motion, the net force $\mathbf{F}$ acting on a body of constant mass $m$ equals the time derivative of its linear momentum $\mathbf{p}$:
\begin{equation}
    \mathbf{F} = \frac{d\mathbf{p}}{dt} = m \mathbf{a}
\end{equation}
where $\mathbf{a} = \ddot{\mathbf{r}}$ is the resulting acceleration vector. In one dimension:
\begin{equation}
    a = \frac{F}{m}
\end{equation}

\begin{figure}[htbp]
    \centering
    \includegraphics[width=0.55\textwidth]{fig1.png}
    \caption{Free-body diagram of an object subjected to an applied unidirectional force.}
    \label{fig:force_schematic}
\end{figure}

Integrating the equations of motion for constant acceleration yields the kinematic trajectory:
\begin{equation}
    x(t) = x_0 + v_0 t + \frac{1}{2} a t^2
\end{equation}

\end{document}',
            'algo' => '<ul>
    <li>Include graphic package: <code>\usepackage{graphicx}</code></li>
    <li>Declare float container: <code>\begin{figure}[htbp] ... \end{figure}</code></li>
    <li>Center image horizontally: <code>\centering</code></li>
    <li>Specify image scale and path: <code>\includegraphics[width=0.55\textwidth]{fig1.png}</code></li>
    <li>Add caption and reference label: <code>\caption{...}</code> and <code>\label{fig:...}</code></li>
</ul>',
            'explanation' => '<p>The <code>graphicx</code> package provides the standard <code>\includegraphics</code> command for inserting bitmap (PNG, JPEG) and vector (PDF, EPS) figures into LaTeX documents. The optional positioning parameter <code>[htbp]</code> instructs the LaTeX float positioning engine to prioritize placement: <strong>h</strong>ere, at the <strong>t</strong>op of the page, at the <strong>b</strong>ottom of the page, or on a separate float <strong>p</strong>age.</p>'
        ],
        [
            'menu_id' => 8,
            'submenu_id' => 1,
            'program_id' => 2,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Newton\'s Rings: Optical Interference Geometry}

When a plano-convex lens with a large radius of curvature $R$ is placed on an optical flat glass plate, a thin wedge-shaped air film of variable thickness $t$ is enclosed between the surfaces. 

Under near-normal incidence of monochromatic light of wavelength $\lambda$, the optical path difference $\Delta$ between rays reflected from the upper and lower surfaces of the air film is:
\begin{equation}
    \Delta = 2t + \frac{\lambda}{2}
\end{equation}
The additional path difference of $\lambda/2$ arises from the phase reversal ($\pi$ shift) upon reflection at the rarer-to-denser interface.

\begin{figure}[htbp]
    \centering
    \includegraphics[width=0.65\textwidth]{optics_rings.png}
    \caption{Ray geometry for the formation of circular interference fringes in Newton\'s rings.}
    \label{fig:newtons_rings_ray}
\end{figure}

The condition for destructive interference gives the radii of the circular dark rings:
\begin{equation}
    r_n = \sqrt{n R \lambda}, \quad n \in \{0, 1, 2, \dots\}
\end{equation}

\end{document}',
            'algo' => '<ul>
    <li>Include optics ray diagram using <code>\includegraphics[width=0.65\textwidth]{optics_rings.png}</code></li>
    <li>Relate physical geometry $t \approx r^2 / (2R)$ to path difference $\Delta$</li>
    <li>Derive dark fringe condition: $2t + \lambda/2 = (2n+1)\lambda/2 \implies r_n^2 = n R \lambda$</li>
</ul>',
            'explanation' => '<p>In physics publications, diagrams are sized relative to the page geometry using fractional dimensions like <code>width=0.65\textwidth</code>. This ensures consistent scaling across different document classes and margins.</p>'
        ],

        // ==========================================
        // 8.2 Figure Alignment
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 2,
            'program_id' => 1,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Simple Harmonic Motion: Centered Placement}

An undamped simple harmonic oscillator consisting of a mass $m$ attached to a linear spring of stiffness constant $k$ is described by the equation of motion:
\begin{equation}
    m \frac{d^2 x}{dt^2} + k x = 0 \implies \ddot{x} + \omega_0^2 x = 0
\end{equation}
where $\omega_0 = \sqrt{k/m}$ is the natural angular frequency.

\begin{figure}[h]
    \centering
    \includegraphics[width=0.5\textwidth]{fig2.png}
    \caption{Centered harmonic displacement waveform as a function of dimensionless time.}
    \label{fig:centered_sho}
\end{figure}

The total mechanical energy $E$ remains constant throughout the motion:
\begin{equation}
    E = T + V = \frac{1}{2}m\dot{x}^2 + \frac{1}{2}kx^2 = \frac{1}{2}kA^2
\end{equation}
where $A$ is the peak oscillation amplitude.

\end{document}',
            'algo' => '<ul>
    <li>Apply horizontal centering inside float: <code>\centering</code></li>
    <li>Avoid <code>\begin{center}</code> inside floats to eliminate extra vertical gaps</li>
    <li>Use <code>[h]</code> (here) specifier for immediate context placement</li>
</ul>',
            'explanation' => '<p>In LaTeX, using <code>\centering</code> inside a <code>figure</code> environment is preferred over <code>\begin{center}...\end{center}</code> because <code>\centering</code> does not introduce unwanted additional vertical whitespace before and after the image.</p>'
        ],
        [
            'menu_id' => 8,
            'submenu_id' => 2,
            'program_id' => 2,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Nonlinear Pendulum: Flush Left Alignment}

For a simple pendulum of length $l$ undergoing arbitrary angular excursions $\theta(t)$, the governing equation of motion is nonlinear:
\begin{equation}
    \ddot{\theta} + \frac{g}{l}\sin\theta = 0
\end{equation}

When documenting figures alongside marginal notes or side annotations, left-aligned placement is achieved using \texttt{\textbackslash raggedright}:

\begin{figure}[htbp]
    \raggedright
    \includegraphics[width=0.55\textwidth]{pendulum.png}
    \caption{Flush-left trajectory diagram depicting the coordinate configuration.}
    \label{fig:left_pendulum}
\end{figure}

For small oscillation amplitudes ($\theta \ll 1$), $\sin\theta \approx \theta$, giving the period $T_0 = 2\pi\sqrt{l/g}$. For large amplitudes $\theta_0$, the exact elliptic period is:
\begin{equation}
    T = 4\sqrt{\frac{l}{g}} \int_0^{\pi/2} \frac{d\phi}{\sqrt{1 - \sin^2(\theta_0/2)\sin^2\phi}}
\end{equation}

\end{document}',
            'algo' => '<ul>
    <li>Align figure flush left: <code>\raggedright</code></li>
    <li>Align figure flush right: <code>\raggedleft</code></li>
    <li>Formulate large-angle nonlinear pendulum equation and complete elliptic integral of the first kind</li>
</ul>',
            'explanation' => '<p>By declaring <code>\raggedright</code> or <code>\raggedleft</code> inside the <code>figure</code> block, authors can override default alignment behavior and position graphics flush with the left or right margin as required by journal layout templates.</p>'
        ],

        // ==========================================
        // 8.3 Figure Captions
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 3,
            'program_id' => 1,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Double Pendulum: Detailed Captions and Labels}

Consider a planar double pendulum consisting of two point masses $m_1$ and $m_2$ suspended by rigid massless links of lengths $l_1$ and $l_2$. The generalized coordinates are the deflection angles $\theta_1$ and $\theta_2$ measured relative to the downward vertical.

\begin{figure}[htbp]
    \centering
    \includegraphics[width=0.6\textwidth]{pendulum.png}
    \caption[Double Pendulum Geometry]{Kinematic configuration of a planar double pendulum showing generalized coordinates $(\theta_1, \theta_2)$, link lengths $(l_1, l_2)$, and positions of point masses $(m_1, m_2)$.}
    \label{fig:double_pendulum_schematic}
\end{figure}

As shown in Figure~\ref{fig:double_pendulum_schematic}, the Cartesian coordinates of the second bob are given by:
\begin{align}
    x_2 &= l_1 \sin\theta_1 + l_2 \sin\theta_2 \\
    y_2 &= -l_1 \cos\theta_1 - l_2 \cos\theta_2
\end{align}
The kinetic energy contains nonlinear inertial coupling $\dot{\theta}_1\dot{\theta}_2\cos(\theta_1 - \theta_2)$, generating deterministic chaos for high energy initial states.

\end{document}',
            'algo' => '<ul>
    <li>Provide short caption for List of Figures: <code>\caption[Short Title]{Long Description}</code></li>
    <li>Place <code>\label{...}</code> directly AFTER <code>\caption{...}</code></li>
    <li>Reference figure within text using non-breaking space: <code>Figure~\ref{...}</code></li>
</ul>',
            'explanation' => '<p>The optional square bracket in <code>\caption[short]{full}</code> supplies a concise title for the automated <code>\listoffigures</code>, while rendering the comprehensive description beneath the illustration. The label <code>\label{fig:key}</code> MUST always follow the caption to bind to the correct figure counter.</p>'
        ],
        [
            'menu_id' => 8,
            'submenu_id' => 3,
            'program_id' => 2,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}
\usepackage[font=small,labelfont=bf,labelsep=period]{caption}

\begin{document}

\section{Electromagnetic Field Tensor: Styled Captions}

In covariant relativistic electrodynamics, the electric field $\mathbf{E}$ and magnetic flux density $\mathbf{B}$ form the components of the rank-2 antisymmetric Faraday tensor $F^{\mu\nu}$:
\begin{equation}
    F^{\mu\nu} = \partial^\mu A^\nu - \partial^\nu A^\mu
\end{equation}
where $A^\mu = (\phi/c, \mathbf{A})$ represents the electromagnetic 4-potential.

\begin{figure}[htbp]
    \centering
    \includegraphics[width=0.6\textwidth]{em_tensor.png}
    \caption{Antisymmetric matrix structure of the $4\times 4$ electromagnetic field tensor $F^{\mu\nu}$ and its dual tensor $\tilde{F}^{\mu\nu}$.}
    \label{fig:em_tensor_layout}
\end{figure}

The source-free Maxwell equations take the compact 4-divergence form:
\begin{equation}
    \partial_\mu F^{\mu\nu} = \mu_0 J^\nu, \quad \partial_\mu \tilde{F}^{\mu\nu} = 0
\end{equation}

\end{document}',
            'algo' => '<ul>
    <li>Customize caption package: <code>\usepackage[font=small,labelfont=bf,labelsep=period]{caption}</code></li>
    <li>Boldface figure labels (e.g., <strong>Figure 1.</strong>)</li>
    <li>Reduce font size for scientific captions to differentiate from body text</li>
</ul>',
            'explanation' => '<p>The <code>caption</code> package provides complete typographic control over caption presentation. Options like <code>font=small</code> and <code>labelfont=bf</code> match the rigorous styling requirements of physical society journals.</p>'
        ],

        // ==========================================
        // 8.4 Side-by-Side Subfigures
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 4,
            'program_id' => 1,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}
\usepackage{subcaption}

\begin{document}

\section{Maxwell\'s Vector Fields: Flux and Circulation}

Maxwell\'s equations in vacuum illustrate the dual nature of vector fields through differential divergence and curl operations:

\begin{figure}[htbp]
    \centering
    \begin{subfigure}[b]{0.48\textwidth}
        \centering
        \includegraphics[width=\linewidth]{gauss_flux.png}
        \caption{Outward flux: $\nabla \cdot \mathbf{E} = \frac{\rho}{\varepsilon_0}$}
        \label{subfig:flux}
    \end{subfigure}
    \hfill
    \begin{subfigure}[b]{0.48\textwidth}
        \centering
        \includegraphics[width=\linewidth]{curl_field.png}
        \caption{Vortical circulation: $\nabla \times \mathbf{B} = \mu_0 \mathbf{J}$}
        \label{subfig:curl}
    \end{subfigure}
    \caption{Topological comparison of fundamental vector fields in electrodynamics.}
    \label{fig:maxwell_duality}
\end{figure}

As shown in Figure~\ref{subfig:flux}, the divergence represents net flux exiting an infinitesimal volume, while Figure~\ref{subfig:curl} shows line integrals circulating around closed loops following Stokes\' theorem.

\end{document}',
            'algo' => '<ul>
    <li>Include subcaption package: <code>\usepackage{subcaption}</code></li>
    <li>Nest <code>\begin{subfigure}[b]{0.48\textwidth} ... \end{subfigure}</code> within <code>figure</code></li>
    <li>Insert horizontal spacing: <code>\hfill</code></li>
    <li>Label individual panels: <code>\label{subfig:flux}</code> for sub-references</li>
</ul>',
            'explanation' => '<p>The modern <code>subcaption</code> package provides the <code>subfigure</code> environment. Multiple subfigures are placed horizontally using width fractions (e.g., <code>0.48\textwidth</code>) and separated by <code>\hfill</code>. Each subfigure receives an automatic letter index (a, b) and its own caption.</p>'
        ],
        [
            'menu_id' => 8,
            'submenu_id' => 4,
            'program_id' => 2,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}
\usepackage{subcaption}

\begin{document}

\section{Coupled Oscillators: Normal Modes of Vibration}

For two identical masses $m$ coupled by three springs of stiffness $k$, the equations of motion in generalized displacement coordinates $(x_1, x_2)$ are:
\begin{align}
    m\ddot{x}_1 &= -2kx_1 + kx_2 \\
    m\ddot{x}_2 &= kx_1 - 2kx_2
\end{align}

\begin{figure}[htbp]
    \centering
    \begin{subfigure}[b]{0.45\textwidth}
        \centering
        \includegraphics[width=\linewidth]{fig2.png}
        \caption{Symmetric mode: $\omega_1 = \sqrt{k/m}$}
        \label{subfig:mode1}
    \end{subfigure}
    \hfill
    \begin{subfigure}[b]{0.45\textwidth}
        \centering
        \includegraphics[width=\linewidth]{fig3.png}
        \caption{Anti-symmetric mode: $\omega_2 = \sqrt{3k/m}$}
        \label{subfig:mode2}
    \end{subfigure}
    \caption{Normal modes of vibration for a coupled symmetric mechanical system.}
    \label{fig:normal_modes}
\end{figure}

In the symmetric mode shown in Figure~\ref{subfig:mode1}, the coupling spring is unstretched ($x_1 = x_2$). In the anti-symmetric mode (Figure~\ref{subfig:mode2}), the masses move in exact anti-phase ($x_1 = -x_2$).

\end{document}',
            'algo' => '<ul>
    <li>Decompose multi-degree-of-freedom vibrations into eigenmodes</li>
    <li>Present mode shapes in adjacent subfigures with independent labels</li>
    <li>Cross-reference specific vibration modes in technical prose</li>
</ul>',
            'explanation' => '<p>Subfigures allow clear scientific comparisons between paired states, such as normal modes, phase-portrait bifurcations, or before/after experimental measurements.</p>'
        ],

        // ==========================================
        // 8.5 Minipage Layouts
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 5,
            'program_id' => 1,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}
\usepackage{caption}

\begin{document}

\section{Lagrangian Mechanics: Diagram and Equations Side-by-Side}

Using the minipage environment, an illustration can be positioned directly adjacent to analytical derivations:

\noindent
\begin{minipage}{0.48\textwidth}
    \centering
    \includegraphics[width=0.92\linewidth]{pendulum.png}
    \captionof{figure}{Double pendulum kinematic coordinates.}
    \label{fig:mini_pendulum}
\end{minipage}
\hfill
\begin{minipage}{0.48\textwidth}
    The Lagrangian $L = T - V$ for the system shown in Figure~\ref{fig:mini_pendulum} is:
    \begin{equation*}
        L = \frac{1}{2}(m_1+m_2)l_1^2 \dot{\theta}_1^2 + \frac{1}{2}m_2 l_2^2 \dot{\theta}_2^2 + m_2 l_1 l_2 \dot{\theta}_1 \dot{\theta}_2 \cos(\theta_1-\theta_2) - V
    \end{equation*}
    Applying the Euler-Lagrange equations:
    \begin{equation*}
        \frac{d}{dt}\left(\frac{\partial L}{\partial \dot{\theta}_i}\right) - \frac{\partial L}{\partial \theta_i} = 0, \quad i \in \{1, 2\}
    \end{equation*}
    yields the coupled second-order ODEs.
\end{minipage}

\end{document}',
            'algo' => '<ul>
    <li>Suppress indentation before box: <code>\noindent</code></li>
    <li>Declare left minipage for figure: <code>\begin{minipage}{0.48\textwidth} ... \end{minipage}</code></li>
    <li>Use <code>\captionof{figure}{...}</code> inside minipage to caption without a floating figure</li>
    <li>Place mathematical derivation in right minipage: <code>\hfill \begin{minipage}{0.48\textwidth}</code></li>
</ul>',
            'explanation' => '<p>The <code>minipage</code> environment builds a self-contained miniature page box. This allows placing a diagram directly beside text or equations without allowing them to float to other pages. Using <code>\captionof{figure}{...}</code> from the <code>caption</code> package generates standard figure numbering inside minipages.</p>'
        ],
        [
            'menu_id' => 8,
            'submenu_id' => 5,
            'program_id' => 2,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Dual Independent Figures in a Single Float}

\begin{figure}[htbp]
    \centering
    \begin{minipage}[t]{0.47\textwidth}
        \centering
        \includegraphics[width=\linewidth]{fig1.png}
        \caption{Apparatus for measuring inertial mass via linear acceleration.}
        \label{fig:setup_mechanics}
    \end{minipage}
    \hfill
    \begin{minipage}[t]{0.47\textwidth}
        \centering
        \includegraphics[width=\linewidth]{optics_rings.png}
        \caption{Spectrometric apparatus for measuring wavelength with Newton\'s rings.}
        \label{fig:setup_optics}
    \end{minipage}
\end{figure}

Figure~\ref{fig:setup_mechanics} and Figure~\ref{fig:setup_optics} show two independent laboratory experiments conducted in undergraduate physics.

\end{document}',
            'algo' => '<ul>
    <li>Nest two minipages inside a single <code>figure</code> float</li>
    <li>Use top alignment option: <code>\begin{minipage}[t]{0.47\textwidth}</code></li>
    <li>Provide each minipage with its own complete <code>\caption{...}</code></li>
</ul>',
            'explanation' => '<p>Unlike subfigures which share a single parent Figure number (e.g. Figure 1a, 1b), two minipages inside a figure float can each have an independent <code>\caption{...}</code> command, creating distinct Figure 1 and Figure 2 side by side while consuming minimal vertical page height.</p>'
        ],

        // ==========================================
        // 8.6 Wrapping Text Around Figures
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 6,
            'program_id' => 1,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}
\usepackage{wrapfig}

\begin{document}

\section{Gauss\'s Law and Electric Flux Density}

\begin{wrapfigure}{r}{0.45\textwidth}
    \centering
    \includegraphics[width=0.42\textwidth]{gauss_flux.png}
    \caption{Closed Gaussian surface enclosing charge $Q_{\text{enc}}$.}
    \label{fig:wrap_gauss}
\end{wrapfigure}
Gauss\'s Law relates the distribution of electric charge to the resulting electric field. In its integral form, the total electric flux $\Phi_E$ through any hypothetical closed surface $S$ is proportional to the net electric charge $Q_{\text{enc}}$ enclosed by that surface:
\begin{equation}
    \oint_S \mathbf{E} \cdot d\mathbf{A} = \frac{Q_{\text{enc}}}{\varepsilon_0}
\end{equation}
where $\varepsilon_0$ is the permittivity of free space. By applying the Gauss divergence theorem to the left-hand side:
\begin{equation}
    \int_V (\nabla \cdot \mathbf{E})\, dV = \frac{1}{\varepsilon_0}\int_V \rho\, dV
\end{equation}
Equating the integrands for an arbitrary volume yields the first Maxwell equation: $\nabla \cdot \mathbf{E} = \frac{\rho}{\varepsilon_0}$. As depicted in Figure~\ref{fig:wrap_gauss}, flux lines diverge outward from regions of positive charge density.

\end{document}',
            'algo' => '<ul>
    <li>Include wrapfig package: <code>\usepackage{wrapfig}</code></li>
    <li>Open wrapped environment: <code>\begin{wrapfigure}{r}{0.45\textwidth}</code></li>
    <li>{r} aligns the figure to the right margin, {l} aligns to the left margin</li>
    <li>Flow explanatory physics text directly after the environment</li>
</ul>',
            'explanation' => '<p>The <code>wrapfig</code> package wraps paragraph text around figures, mimicking professional textbook formatting. The first argument specifies alignment (<code>r</code> for right, <code>l</code> for left), and the second sets the reserved column width.</p>'
        ],
        [
            'menu_id' => 8,
            'submenu_id' => 6,
            'program_id' => 2,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}
\usepackage{wrapfig}

\begin{document}

\section{Rigid Body Dynamics: Rolling on an Incline}

\begin{wrapfigure}{l}{0.42\textwidth}
    \centering
    \includegraphics[width=0.38\textwidth]{fig1.png}
    \caption{Free-body forces on an inclined plane.}
    \label{fig:wrap_incline}
\end{wrapfigure}
Consider a uniform rigid body of mass $M$ and radius $R$ rolling without slipping down an inclined plane with inclination angle $\theta$. The active forces are gravity $M\mathbf{g}$, the normal reaction force $\mathbf{N} = Mg\cos\theta\,\hat{\mathbf{j}}$, and the static frictional force $\mathbf{f}_s$ acting up the incline plane.

The linear equation of motion along the incline is:
\begin{equation}
    Mg \sin\theta - f_s = M a_{\text{cm}}
\end{equation}
Taking rotational torques about the center of mass: $\tau = f_s R = I_{\text{cm}} \alpha$. Since pure rolling enforces the kinematic constraint $a_{\text{cm}} = R\alpha$, we eliminate $f_s$ to obtain:
\begin{equation}
    a_{\text{cm}} = \frac{g \sin\theta}{1 + \frac{I_{\text{cm}}}{M R^2}}
\end{equation}

\end{document}',
            'algo' => '<ul>
    <li>Wrap figure on the left margin: <code>\begin{wrapfigure}{l}{0.42\textwidth}</code></li>
    <li>State translational and rotational Newton-Euler equations</li>
    <li>Derive center-of-mass linear acceleration for rolling without slipping</li>
</ul>',
            'explanation' => '<p>Positioning figures on the left margin via <code>\begin{wrapfigure}{l}{...}</code> provides varied editorial balance in extended scientific reports.</p>'
        ],

        // ==========================================
        // 8.7 Scaling, Rotating & Trimming
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 7,
            'program_id' => 1,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Tensor Transformations: Rotated Figure Orientation}

In Minkowski spacetime with metric $\eta_{\mu\nu} = \text{diag}(1, -1, -1, -1)$, an orthogonal rotation or coordinate boost transforms tensor field components. When embedding wide schematic diagrams, the \texttt{angle} option in \texttt{\textbackslash includegraphics} performs exact rotation:

\begin{figure}[htbp]
    \centering
    \includegraphics[angle=90, width=0.45\textwidth]{em_tensor.png}
    \caption{Electromagnetic tensor diagram rotated counter-clockwise by $90^\circ$ for landscape display.}
    \label{fig:rotated_tensor}
\end{figure}

Under a Lorentz transformation matrix $\Lambda^\mu{}_\alpha$, the field-strength tensor transforms as:
\begin{equation}
    F\'^{\mu\nu} = \Lambda^\mu{}_\alpha \Lambda^\nu{}_\beta F^{\alpha\beta}
\end{equation}
preserving the gauge invariants $I_1 = -\frac{1}{2}F_{\mu\nu}F^{\mu\nu} = \mathbf{E}^2 - c^2\mathbf{B}^2$ and $I_2 = -\frac{1}{4}F_{\mu\nu}\tilde{F}^{\mu\nu} = c\mathbf{E}\cdot\mathbf{B}$.

\end{document}',
            'algo' => '<ul>
    <li>Rotate graphics by an angle in degrees: <code>\includegraphics[angle=90, width=...]{...}</code></li>
    <li>Positive angles rotate counter-clockwise; negative angles rotate clockwise</li>
    <li>Order of parameters matters: <code>[angle=90, width=0.45\textwidth]</code> first rotates then rescales</li>
</ul>',
            'explanation' => '<p>The <code>graphicx</code> package supports geometric transformations on imported figures. Specifying <code>angle=90</code> rotates the graphic by 90 degrees counter-clockwise before scaling it to fit the specified width.</p>'
        ],
        [
            'menu_id' => 8,
            'submenu_id' => 7,
            'program_id' => 2,
            'content' => '\documentclass{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\section{Optical Interferometry: Bounding Box Cropping}

When working with laboratory images, unwanted margins can be trimmed directly in LaTeX using the \texttt{trim} and \texttt{clip} options without modifying the source file:

\begin{figure}[htbp]
    \centering
    % Syntax: trim = left bottom right top
    \includegraphics[trim=20 30 20 30, clip, width=0.6\textwidth, keepaspectratio]{optics_rings.png}
    \caption{Cropped view isolating central optical path interference geometry.}
    \label{fig:cropped_rings}
\end{figure}

The \texttt{trim} option removes the specified dimensions from each edge, while \texttt{clip} enforces strict clipping to the designated bounding box.

\end{document}',
            'algo' => '<ul>
    <li>Crop unwanted margins: <code>trim=left bottom right top</code></li>
    <li>Enforce clipping: <code>clip</code> parameter</li>
    <li>Preserve proportional dimensions: <code>keepaspectratio</code></li>
</ul>',
            'explanation' => '<p>The <code>trim</code> option specifies dimensions (in PostScript points by default or explicit units like <code>10mm</code>) to shave off from the left, bottom, right, and top edges. The companion <code>clip</code> boolean ensures that pixels outside the trimmed bounding box are masked from display.</p>'
        ],

        // ==========================================
        // 8.8 Two-Column Wide Figures
        // ==========================================
        [
            'menu_id' => 8,
            'submenu_id' => 8,
            'program_id' => 1,
            'content' => '\documentclass[twocolumn]{article}
\usepackage{graphicx}
\usepackage{amsmath}

\begin{document}

\title{Precision Thin-Film Optical Interferometry}
\author{Laboratory of Applied Optics}
\maketitle

\section{Introduction}
Two-column layouts are the standard formatting standard for physics journal articles (e.g., Physical Review, Applied Physics Letters). Normal figures are restricted to a single column width. However, complex experimental diagrams often require spanning both columns across the full page width.

\begin{figure*}[t]
    \centering
    \includegraphics[width=0.85\textwidth]{optics_rings.png}
    \caption{Wide-format apparatus schematic: Ray geometry for Newton\'s rings interferometry spanning both publication columns.}
    \label{fig:wide_apparatus}
\end{figure*}

\section{Interference Theory}
By replacing the standard figure environment with the starred float \texttt{figure*}, the figure container expands across the full text width rather than the single column width.

The central dark spot formed under reflection confirms a relative phase change of $\pi$ radians at the glass-air boundary:
\begin{equation}
    2t = n\lambda
\end{equation}
For a spherical lens surface of radius $R$, the sagitta formula gives $r^2 \approx 2Rt$, establishing the quadratic fringe distribution seen in Figure~\ref{fig:wide_apparatus}.

\end{document}',
            'algo' => '<ul>
    <li>Create two-column document: <code>\documentclass[twocolumn]{article}</code></li>
    <li>Use starred figure environment: <code>\begin{figure*}[t] ... \end{figure*}</code></li>
    <li>Spans both columns across <code>\textwidth</code></li>
    <li>Note: In LaTeX, <code>figure*</code> floats are placed at the top of a page (<code>[t]</code>) or on a dedicated float page (<code>[p]</code>)</li>
</ul>',
            'explanation' => '<p>In two-column publications, standard <code>figure</code> floats are constrained inside a single column. The starred float <code>\begin{figure*}...\end{figure*}</code> instructs LaTeX to span the graphic and its caption across both columns, producing full-width illustrations.</p>'
        ]
    ];
}
