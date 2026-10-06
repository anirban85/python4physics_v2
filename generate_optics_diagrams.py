"""
Python4Physics - Generator for Module 5 Optics Visualization Diagrams
Generates publication-quality scientific ray diagrams and theory figures:
1. newtons_rings_experimental_setup.png : Complete laboratory bench setup (Sodium lamp, 45-deg plate, lens, flat, microscope)
2. newtons_rings_ray_geometry.png       : High-magnification thin-film ray tracing (Reflected vs Transmitted path differences)
3. newtons_rings_fringe_analysis.png     : Reflected vs Transmitted comparison, D_m^2 vs m plot, and lambda / mu determination
"""
import os
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyArrowPatch, Rectangle, Circle, Arc, Polygon, FancyBboxPatch, Wedge
from matplotlib.colors import LinearSegmentedColormap

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), 'assets', 'images', 'visualization', 'optics')
os.makedirs(OUTPUT_DIR, exist_ok=True)

# Theme Palette matching the dark laboratory aesthetic
BG_DARK = '#0b1120'       # Deep space background
CARD_BG = '#1e293b'       # Dark card background
BORDER_COL = '#334155'    # Subtle slate border
TEXT_LIGHT = '#f8fafc'    # Bright white text
TEXT_MUTED = '#94a3b8'    # Slate gray text
ACCENT_CYAN = '#38bdf8'   # Cyan
ACCENT_EMERALD = '#10b981'# Emerald
ACCENT_AMBER = '#f59e0b'  # Amber / Sodium Yellow
ACCENT_ROSE = '#f43f5e'   # Rose
ACCENT_PURPLE = '#a855f7' # Purple
ACCENT_BLUE = '#3b82f6'   # Blue

plt.rcParams.update({
    'font.family': 'sans-serif',
    'font.sans-serif': ['DejaVu Sans', 'Arial', 'Helvetica'],
    'text.color': TEXT_LIGHT,
    'axes.labelcolor': TEXT_LIGHT,
    'xtick.color': TEXT_MUTED,
    'ytick.color': TEXT_MUTED,
    'axes.edgecolor': BORDER_COL,
    'figure.facecolor': BG_DARK,
    'axes.facecolor': CARD_BG,
    'savefig.facecolor': BG_DARK,
    'savefig.edgecolor': 'none'
})

print(f"Generating Optics Newton's Rings diagrams into {OUTPUT_DIR}...")

# -----------------------------------------------------------------------------
# DIAGRAM 1: Complete Experimental Laboratory Setup (Clean Layout)
# -----------------------------------------------------------------------------
def make_experimental_setup_diagram():
    fig, ax = plt.subplots(figsize=(11.8, 7.5), dpi=160)
    ax.set_facecolor(CARD_BG)
    ax.set_xlim(0, 14.5)
    ax.set_ylim(0, 11.2)
    ax.axis('off')

    # Card border
    card_rect = FancyBboxPatch((0.3, 0.3), 13.9, 10.6, boxstyle="round,pad=0.2",
                               fc=CARD_BG, ec=BORDER_COL, lw=1.5)
    ax.add_patch(card_rect)

    # Title
    ax.text(7.25, 10.4, "Newton's Rings: Complete Optical Laboratory Setup & Ray Paths",
            fontsize=13.5, fontweight='bold', ha='center', color=TEXT_LIGHT)
    ax.text(7.25, 9.95, "Division of Amplitude via Partial Reflection at Thin Air / Liquid Film",
            fontsize=9.5, ha='center', color=ACCENT_CYAN)

    # 1. Monochromatic Light Source S (Sodium Lamp)
    src_box = FancyBboxPatch((0.7, 5.2), 1.2, 1.4, boxstyle="round,pad=0.1",
                             fc='#78350f', ec=ACCENT_AMBER, lw=1.8)
    ax.add_patch(src_box)
    ax.text(1.3, 5.9, "S", fontsize=13, fontweight='bold', ha='center', va='center', color=ACCENT_AMBER)
    ax.text(1.3, 4.6, "Monochromatic\nSodium Lamp\n(λ = 589.3 nm)", fontsize=8, ha='center', color=TEXT_MUTED)

    # Rays from Source
    ax.annotate("", xy=(2.4, 5.9), xytext=(1.9, 5.9),
                arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=2))
    ax.annotate("", xy=(2.4, 6.4), xytext=(1.9, 6.1),
                arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=1.5))
    ax.annotate("", xy=(2.4, 5.4), xytext=(1.9, 5.7),
                arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=1.5))

    # 2. Condensing Lens L1
    lens1_body = Circle((2.6, 5.9), 0.65, fc='#0369a1', ec=ACCENT_CYAN, lw=1.5, alpha=0.5)
    ax.add_patch(lens1_body)
    ax.text(2.6, 7.0, "Condensing Lens $L_1$\n(Collimates Parallel Beam)", fontsize=8.5, ha='center', color=ACCENT_CYAN)

    # Parallel Horizontal Beam from L1 to 45-deg Glass Plate G
    for y_beam in [5.5, 5.9, 6.3]:
        ax.plot([3.3, 6.5], [y_beam, y_beam], color=ACCENT_AMBER, lw=2, alpha=0.9)
        ax.annotate("", xy=(5.0, y_beam), xytext=(4.6, y_beam),
                    arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=2))

    # 3. Glass Plate G inclined at 45 degrees
    plate_x = [6.0, 7.6]
    plate_y = [6.7, 5.1]
    ax.plot(plate_x, plate_y, color='#38bdf8', lw=5, alpha=0.85, solid_capstyle='round')
    ax.text(7.8, 6.9, "Glass Plate $G$ (at 45°)", fontsize=9.5, fontweight='bold', color=ACCENT_CYAN)
    ax.text(7.8, 6.3, "• Reflects light downward\n• Transmits reflected rays to scope", fontsize=8, color=TEXT_MUTED)

    # Arc showing 45 degree angle
    ax.plot([6.8, 7.7], [5.9, 5.9], color=TEXT_MUTED, ls='--', lw=1)
    ax.text(7.2, 5.6, "45°", fontsize=8.5, color=ACCENT_AMBER)

    # 4. Light reflected normally downward towards Plano-Convex Lens
    for x_down in [6.5, 6.8, 7.1]:
        ax.plot([x_down, x_down], [5.9, 3.2], color=ACCENT_AMBER, lw=2, alpha=0.9)
        ax.annotate("", xy=(x_down, 4.3), xytext=(x_down, 4.8),
                    arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=2))

    # 5. Plano-Convex Lens L resting on Plane Glass Plate P
    lens_x = np.linspace(4.8, 8.8, 200)
    lens_top = np.full_like(lens_x, 3.1)
    lens_bot = 2.4 + (lens_x - 6.8)**2 / (2 * 4.5)
    ax.fill_between(lens_x, lens_bot, lens_top, color='#0284c7', alpha=0.35, ec=ACCENT_CYAN, lw=1.5)
    ax.text(9.2, 3.0, "Plano-Convex Lens $L$\n(Curvature Radius $R$)", fontsize=9, fontweight='bold', color=ACCENT_CYAN)

    # Glass Plate P: flat optical flat at bottom
    plate_p = FancyBboxPatch((4.5, 1.7), 4.6, 0.65, boxstyle="round,pad=0.04",
                             fc='#1e293b', ec='#64748b', lw=1.5)
    ax.add_patch(plate_p)
    ax.text(9.2, 1.9, "Plane Glass Plate $P$\n(Optically Flat Surface)", fontsize=9, fontweight='bold', color='#94a3b8')

    # Air / Liquid Film callout at contact point
    ax.plot([6.8], [2.4], 'o', color=ACCENT_EMERALD, ms=7)
    ax.annotate("Point of Contact $O$ ($t = 0$)\nThin Film $\\mu$",
                xy=(6.8, 2.4), xytext=(4.3, 2.5),
                arrowprops=dict(arrowstyle="->", color=ACCENT_EMERALD, lw=1.5),
                fontsize=8.5, fontweight='bold', color=ACCENT_EMERALD, ha='right')

    # 6. Reflected Rays Traveling Vertically UPWARD into Microscope
    for x_up in [6.6, 7.0]:
        ax.plot([x_up, x_up], [3.1, 8.2], color='#fbbf24', lw=2.2, alpha=0.95, ls='-')
        ax.annotate("", xy=(x_up, 7.6), xytext=(x_up, 7.1),
                    arrowprops=dict(arrowstyle="->", color='#fbbf24', lw=2.2))

    # 7. Traveling Microscope at the Top
    micro_box = FancyBboxPatch((5.7, 8.2), 2.2, 1.35, boxstyle="round,pad=0.08",
                               fc='#1e1b4b', ec=ACCENT_PURPLE, lw=1.8)
    ax.add_patch(micro_box)
    ax.text(6.8, 9.2, "Traveling Microscope", fontsize=9.5, fontweight='bold', ha='center', color=ACCENT_PURPLE)
    ax.text(6.8, 8.85, "Crosswire Eyepiece", fontsize=8, ha='center', color='#cbd5e1')

    # Circular reticle inside microscope icon
    reticle = Circle((6.8, 8.5), 0.22, fc='black', ec=ACCENT_PURPLE, lw=1)
    ax.add_patch(reticle)
    ax.plot([6.58, 7.02], [8.5, 8.5], color=ACCENT_CYAN, lw=0.8, ls=':')
    ax.plot([6.8, 6.8], [8.28, 8.72], color=ACCENT_CYAN, lw=0.8, ls=':')

    # 8. Transmitted Rays traveling downward through plate P
    for x_trans in [6.6, 7.0]:
        ax.plot([x_trans, x_trans], [1.7, 0.7], color='#38bdf8', lw=1.8, ls='--')
        ax.annotate("", xy=(x_trans, 0.9), xytext=(x_trans, 1.2),
                    arrowprops=dict(arrowstyle="->", color='#38bdf8', lw=1.8))
    ax.text(6.8, 0.45, "Transmitted Rays (Bright Center)", fontsize=8.5, ha='center', color=ACCENT_CYAN)

    # 9. Key Laboratory Insights Box on the right side
    info_box = FancyBboxPatch((10.4, 4.3), 3.4, 5.0, boxstyle="round,pad=0.15",
                              fc='#0f172a', ec=BORDER_COL, lw=1.3)
    ax.add_patch(info_box)
    ax.text(12.1, 9.0, "Key Physics Principles", fontsize=10, fontweight='bold', ha='center', color=ACCENT_EMERALD)
    
    insights = [
        "• Extended Source: Ensures light enters",
        "  across broad angles, filling the eye.",
        "• 45° Plate G: Produces normal incidence",
        "  (i = 0, cos r = 1), so optical path diff",
        "  is purely 2μt without angular skew.",
        "• Fizeau Equal-Thickness Fringes:",
        "  Loci of constant film thickness t are",
        "  perfect concentric circles.",
        "• Reflected System:",
        "  Phase jump π at glass plate reflection",
        "  => Central Spot is DARK (m = 0).",
        "• Transmitted System:",
        "  Two internal reflections (no net π jump)",
        "  => Central Spot is BRIGHT (m = 0)."
    ]
    ax.text(10.55, 8.6, "\n".join(insights), fontsize=7.8, va='top', color=TEXT_LIGHT, linespacing=1.35)

    out_path = os.path.join(OUTPUT_DIR, 'newtons_rings_experimental_setup.png')
    plt.savefig(out_path, dpi=160, bbox_inches='tight')
    plt.close()
    print(f"Generated clean setup: {out_path}")

# -----------------------------------------------------------------------------
# DIAGRAM 2: High-Magnification Thin-Film Ray Geometry (Clean Layout)
# -----------------------------------------------------------------------------
def make_ray_geometry_diagram():
    fig = plt.figure(figsize=(12.4, 6.8), dpi=160)
    fig.patch.set_facecolor(BG_DARK)

    # Left: Geometric Film Thickness Derivation
    ax1 = fig.add_subplot(1, 2, 1)
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-0.5, 6.5)
    ax1.set_ylim(-0.5, 6.5)
    ax1.axis('off')

    rect1 = FancyBboxPatch((-0.3, -0.3), 6.6, 6.6, boxstyle="round,pad=0.15",
                           fc=CARD_BG, ec=BORDER_COL, lw=1.5)
    ax1.add_patch(rect1)

    ax1.text(3.0, 6.0, "1. Geometry of Spherical Air Film", fontsize=11, fontweight='bold', ha='center', color=ACCENT_CYAN)

    C = (3.0, 4.5)
    O = (3.0, 0.8)
    R = 3.7

    # Flat plate
    ax1.plot([0.5, 5.5], [0.8, 0.8], color='#64748b', lw=3)
    ax1.text(5.6, 0.8, "Glass Plate", fontsize=8, va='center', color=TEXT_MUTED)

    # Lens curved bottom
    th = np.linspace(-0.65, 0.65, 100)
    arc_x = 3.0 + R * np.sin(th)
    arc_y = 4.5 - R * np.cos(th)
    ax1.plot(arc_x, arc_y, color=ACCENT_CYAN, lw=2.2)
    ax1.text(5.5, 1.8, "Lens Surface\n(Radius $R$)", fontsize=8, color=ACCENT_CYAN)

    # Radius vector C -> P
    P_th = 0.42
    Px = 3.0 + R * np.sin(P_th)
    Py = 4.5 - R * np.cos(P_th)
    ax1.plot([C[0], Px], [C[1], Py], color=ACCENT_AMBER, lw=1.6, ls='--')
    ax1.plot([C[0], C[0]], [C[1], O[1]], color='#94a3b8', lw=1.2, ls=':')

    # Points C, O, P
    ax1.plot(C[0], C[1], 'o', color=ACCENT_AMBER, ms=5)
    ax1.text(C[0]-0.25, C[1], "$C$", fontsize=10, fontweight='bold', color=ACCENT_AMBER)

    ax1.plot(O[0], O[1], 'o', color=ACCENT_EMERALD, ms=5)
    ax1.text(O[0], O[1]-0.3, "$O$ (Contact)", fontsize=9, fontweight='bold', ha='center', color=ACCENT_EMERALD)

    ax1.plot(Px, Py, 'o', color=ACCENT_CYAN, ms=5)
    ax1.text(Px+0.15, Py, "$P$", fontsize=9, fontweight='bold', color=ACCENT_CYAN)

    # Vertical line from P down to plate
    ax1.plot([Px, Px], [0.8, Py], color=ACCENT_ROSE, lw=2)
    ax1.text(Px+0.15, (0.8+Py)/2, "$t$", fontsize=10, fontweight='bold', color=ACCENT_ROSE)

    # Radial distance r from center
    ax1.annotate("", xy=(Px, 0.5), xytext=(O[0], 0.5),
                 arrowprops=dict(arrowstyle="<->", color=ACCENT_EMERALD, lw=1.5))
    ax1.text((O[0]+Px)/2, 0.25, "Radius $r$", fontsize=9, ha='center', color=ACCENT_EMERALD)

    # Derivation text
    geom_text = (
        "By right triangle chord relations:\n"
        "$R^2 = r^2 + (R - t)^2$\n"
        "$R^2 = r^2 + R^2 - 2Rt + t^2$\n"
        "Since $t \\ll R$, neglect $t^2$:\n"
        "$t(r) \\approx \\frac{r^2}{2R}$\n\n"
        "With film medium $\\mu$ and gap $t_0$:\n"
        "$t(r) = t_0 + \\frac{r^2}{2R}$"
    )
    ax1.text(0.2, 3.8, geom_text, fontsize=8.2, color=TEXT_LIGHT,
             bbox=dict(boxstyle="round,pad=0.4", fc='#0f172a', ec=BORDER_COL, lw=1))

    # Right: Thin-Film Ray Tracing (Reflected vs Transmitted)
    ax2 = fig.add_subplot(1, 2, 2)
    ax2.set_facecolor(CARD_BG)
    ax2.set_xlim(-0.5, 6.5)
    ax2.set_ylim(-0.5, 6.5)
    ax2.axis('off')

    rect2 = FancyBboxPatch((-0.3, -0.3), 6.6, 6.6, boxstyle="round,pad=0.15",
                           fc=CARD_BG, ec=BORDER_COL, lw=1.5)
    ax2.add_patch(rect2)

    ax2.text(3.0, 6.1, "2. Reflected vs. Transmitted Ray Splitting", fontsize=11, fontweight='bold', ha='center', color=ACCENT_EMERALD)

    # Boundaries: Lens glass (top), Film (middle), Plate glass (bottom)
    # Lens block
    ax2.fill_between([0.2, 5.8], [4.6, 4.6], [5.8, 5.8], color='#0284c7', alpha=0.3, ec=ACCENT_CYAN, lw=1)
    ax2.text(5.6, 5.5, "Plano-Convex Lens ($n_g \\approx 1.5$)", fontsize=7.5, ha='right', color=ACCENT_CYAN)

    # Film region
    ax2.fill_between([0.2, 5.8], [2.1, 2.1], [4.6, 4.6], color='#f59e0b', alpha=0.15, ec=BORDER_COL, lw=1, ls='--')
    ax2.text(0.4, 3.3, "Thin Film Medium (Air $\\mu=1$ or Liquid $\\mu$)", fontsize=7.8, color=ACCENT_AMBER)
    ax2.text(5.6, 3.3, "Thickness $t$", fontsize=8, ha='right', color=ACCENT_ROSE)

    # Plate block
    ax2.fill_between([0.2, 5.8], [0.9, 0.9], [2.1, 2.1], color='#1e293b', alpha=0.6, ec='#64748b', lw=1)
    ax2.text(5.6, 1.5, "Glass Plate ($n_g \\approx 1.5$)", fontsize=7.5, ha='right', color='#94a3b8')

    # Ray Tracing:
    # Incident ray enters from top left down to (1.6, 4.6)
    ax2.annotate("", xy=(1.6, 4.6), xytext=(1.2, 5.8),
                 arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=2.2))
    ax2.text(1.0, 5.5, "Incident", fontsize=7.5, color=ACCENT_AMBER, ha='right')

    # 1. Reflected Ray 1: from glass -> film (denser -> rarer) => NO PHASE CHANGE!
    ax2.annotate("", xy=(0.8, 5.8), xytext=(1.6, 4.6),
                 arrowprops=dict(arrowstyle="->", color='#38bdf8', lw=2.2))
    ax2.text(0.7, 5.0, "Ray 1 (Refl.)\n$\\Delta\\phi = 0$", fontsize=7.2, color='#38bdf8', ha='right')

    # 2. Refracted into film, then reflects off plate at (2.1, 2.1) (rarer -> denser) => PHASE CHANGE PI!
    ax2.annotate("", xy=(2.1, 2.1), xytext=(1.6, 4.6),
                 arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=1.8))
    ax2.annotate("", xy=(2.6, 4.6), xytext=(2.1, 2.1),
                 arrowprops=dict(arrowstyle="->", color='#f59e0b', lw=1.8))
    # Emerges as Ray 2
    ax2.annotate("", xy=(2.2, 5.8), xytext=(2.6, 4.6),
                 arrowprops=dict(arrowstyle="->", color='#ec4899', lw=2.2))
    ax2.text(2.6, 5.3, "Ray 2 (Refl.)\n$\\Delta\\phi = \\pi$", fontsize=7.2, color='#ec4899')

    # Stokes' Phase Jump Callout
    ax2.text(2.2, 2.35, "★ Stokes Phase Jump $\\pi$", fontsize=7.5, fontweight='bold', color='#fbbf24')

    # Reflected path difference box
    ref_box = (
        "Reflected Light System:\n"
        "Optical Path Difference:\n"
        "$\\Delta_{\\mathrm{ref}} = 2\\mu t + \\frac{\\lambda}{2} = \\frac{\\mu r^2}{R} + \\frac{\\lambda}{2}$\n"
        "• Dark Rings: $\\Delta = (2m+1)\\frac{\\lambda}{2} \\rightarrow r_m = \\sqrt{\\frac{m\\lambda R}{\\mu}}$\n"
        "• Bright Rings: $\\Delta = m\\lambda \\rightarrow r_m = \\sqrt{\\frac{(2m-1)\\lambda R}{2\\mu}}$\n"
        "• Center ($r=0, t=0$): $\\Delta = \\frac{\\lambda}{2} \\rightarrow$ DARK CENTER!"
    )
    ax2.text(3.1, 4.4, ref_box, fontsize=7.2, color=TEXT_LIGHT, va='top',
             bbox=dict(boxstyle="round,pad=0.3", fc='#0f172a', ec=ACCENT_CYAN, lw=1))

    # Transmitted Ray Tracing:
    # Ray 1' transmits directly at (2.1, 2.1) down
    ax2.annotate("", xy=(2.1, 0.9), xytext=(2.1, 2.1),
                 arrowprops=dict(arrowstyle="->", color='#10b981', lw=2))
    ax2.text(1.6, 1.3, "Ray 1'", fontsize=7.2, color='#10b981')

    # Ray 2' undergoes 2 internal reflections inside film, then transmits down at (3.1, 0.9)
    ax2.annotate("", xy=(3.1, 0.9), xytext=(3.1, 2.1),
                 arrowprops=dict(arrowstyle="->", color='#a855f7', lw=1.8))
    ax2.text(3.3, 1.3, "Ray 2'", fontsize=7.2, color='#a855f7')

    # Transmitted path difference box
    trans_box = (
        "Transmitted Light System:\n"
        "Two internal reflections: No net phase jump!\n"
        "$\\Delta_{\\mathrm{trans}} = 2\\mu t = \\frac{\\mu r^2}{R}$\n"
        "• Bright Rings: $\\Delta = m\\lambda \\rightarrow r_m = \\sqrt{\\frac{m\\lambda R}{\\mu}}$\n"
        "• Dark Rings: $\\Delta = (m+1/2)\\lambda \\rightarrow r_m = \\sqrt{\\frac{(2m+1)\\lambda R}{2\\mu}}$\n"
        "• Center ($r=0, t=0$): $\\Delta = 0 \\rightarrow$ BRIGHT CENTER!"
    )
    ax2.text(0.3, 0.75, trans_box, fontsize=7.2, color=TEXT_LIGHT, va='top',
             bbox=dict(boxstyle="round,pad=0.3", fc='#0f172a', ec=ACCENT_EMERALD, lw=1))

    out_path = os.path.join(OUTPUT_DIR, 'newtons_rings_ray_geometry.png')
    plt.savefig(out_path, dpi=160, bbox_inches='tight')
    plt.close()
    print(f"Generated clean ray geometry: {out_path}")

# -----------------------------------------------------------------------------
# DIAGRAM 3: Reflected vs Transmitted Comparison & D_m^2 vs m Analysis
# -----------------------------------------------------------------------------
def make_fringe_analysis_diagram():
    fig = plt.figure(figsize=(12.0, 6.2), dpi=160)
    fig.patch.set_facecolor(BG_DARK)

    # Subplot 1: Reflected vs Transmitted Fringe Visual Comparison
    ax1 = fig.add_subplot(1, 2, 1)
    ax1.set_facecolor(CARD_BG)
    ax1.set_xlim(-3.5, 3.5)
    ax1.set_ylim(-3.5, 3.5)
    ax1.set_aspect('equal')
    ax1.axis('off')

    rect1 = FancyBboxPatch((-3.3, -3.3), 6.6, 6.6, boxstyle="round,pad=0.15",
                           fc=CARD_BG, ec=BORDER_COL, lw=1.5)
    ax1.add_patch(rect1)

    ax1.text(0, 3.0, "Reflected vs. Transmitted Rings\n(Strict Complementarity)",
             fontsize=10.5, fontweight='bold', ha='center', color=ACCENT_CYAN)

    lam, R, mu = 589.3e-9, 1.2, 1.0
    radii = [np.sqrt(m * lam * R / mu) * 1e3 for m in range(1, 8)]

    # Left half: Reflected (Dark center)
    wedge_bg_ref = Wedge((0, 0), 2.7, 90, 270, fc='#ca8a04', ec=BORDER_COL, lw=1)
    ax1.add_patch(wedge_bg_ref)
    spot_dark = Wedge((0, 0), radii[0]*0.7, 90, 270, fc='#000000')
    ax1.add_patch(spot_dark)
    for r in radii:
        if r < 2.6:
            ring = Wedge((0, 0), r, 90, 270, width=0.08, fc='#000000')
            ax1.add_patch(ring)

    # Right half: Transmitted (Bright center)
    wedge_bg_trans = Wedge((0, 0), 2.7, 270, 90, fc='#000000', ec=BORDER_COL, lw=1)
    ax1.add_patch(wedge_bg_trans)
    spot_bright = Wedge((0, 0), radii[0]*0.7, 270, 90, fc='#fde047')
    ax1.add_patch(spot_bright)
    for r in radii:
        if r < 2.6:
            ring_t = Wedge((0, 0), r, 270, 90, width=0.08, fc='#fde047')
            ax1.add_patch(ring_t)

    # Divider line and labels
    ax1.plot([0, 0], [-2.7, 2.7], color=TEXT_LIGHT, lw=2, ls='--')
    ax1.text(-1.4, -2.9, "REFLECTED\n• Dark Center\n• $D_m \\propto \\sqrt{m}$ (Dark)",
             fontsize=8, fontweight='bold', ha='center', color=ACCENT_AMBER)
    ax1.text(1.4, -2.9, "TRANSMITTED\n• Bright Center\n• $D_m \\propto \\sqrt{m}$ (Bright)",
             fontsize=8, fontweight='bold', ha='center', color=ACCENT_CYAN)

    # Subplot 2: D_m^2 vs m Graph (Wavelength & Index Determination)
    ax2 = fig.add_subplot(1, 2, 2)
    ax2.set_facecolor(CARD_BG)

    m_orders = np.arange(1, 13)
    D2_air = 4 * m_orders * lam * R / 1.0 * 1e6  # mm^2
    D2_water = 4 * m_orders * lam * R / 1.333 * 1e6  # mm^2

    ax2.plot(m_orders, D2_air, 'o-', color=ACCENT_AMBER, lw=2.2, ms=6, label='Air Film ($\\mu = 1.00$)')
    ax2.plot(m_orders, D2_water, 's--', color=ACCENT_CYAN, lw=2.0, ms=6, label='Water Film ($\\mu = 1.33$)')

    ax2.set_xlabel('Fringe Order Number $m$', fontsize=10, fontweight='bold')
    ax2.set_ylabel('Diameter Squared $D_m^2$ (mm$^2$)', fontsize=10, fontweight='bold')
    ax2.set_title('Linear Calibration: $D_m^2$ vs. Ring Order $m$', fontsize=11, fontweight='bold', pad=10)
    ax2.grid(True, linestyle=':', alpha=0.4, color=BORDER_COL)
    ax2.legend(loc='upper left', fontsize=8.5, framealpha=0.85)

    ax2.annotate("Slope = $4\\lambda R / \\mu$\nLinear Relation!", xy=(7, D2_air[6]), xytext=(4, D2_air[8]),
                 arrowprops=dict(arrowstyle="->", color=ACCENT_AMBER, lw=1.5),
                 fontsize=8.5, color=ACCENT_AMBER, fontweight='bold',
                 bbox=dict(boxstyle="round,pad=0.3", fc='#0f172a', ec=BORDER_COL, lw=1))

    formula_card = (
        "Core Laboratory Formulas:\n"
        "• Unknown Wavelength $\\lambda$:\n"
        "  $\\lambda = \\frac{D_{m+p}^2 - D_m^2}{4pR}$\n"
        "• Liquid Refractive Index $\\mu$:\n"
        "  $\\mu = \\frac{(D_{m+p}^2 - D_m^2)_{\\mathrm{air}}}{(D_{m+p}^2 - D_m^2)_{\\mathrm{liquid}}}$"
    )
    ax2.text(0.52, 0.08, formula_card, transform=ax2.transAxes, fontsize=8, color=TEXT_LIGHT,
             bbox=dict(boxstyle="round,pad=0.4", fc='#0f172a', ec=ACCENT_EMERALD, lw=1.2))

    out_path = os.path.join(OUTPUT_DIR, 'newtons_rings_fringe_analysis.png')
    plt.savefig(out_path, dpi=160, bbox_inches='tight')
    plt.close()
    print(f"Generated: {out_path}")

if __name__ == '__main__':
    make_experimental_setup_diagram()
    make_ray_geometry_diagram()
    make_fringe_analysis_diagram()
    print("All Optics diagrams regenerated successfully!")
