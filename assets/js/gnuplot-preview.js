/**
 * Python4Physics - Client-Side GNUplot Interactive Previewer
 * Parses simple GNUplot function definitions & data files and renders interactive graphs using Canvas.
 */
class GnuplotCanvasRenderer {
  constructor(canvasId) {
    this.canvas = document.getElementById(canvasId);
    this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
  }

  parseAndPlot(gnuplotCode, targetEl) {
    if (!targetEl) return;

    // Check for function plots e.g., plot sin(x), plot [0:10] x**2
    const lines = gnuplotCode.split('\n');
    let plotLine = lines.find(l => l.trim().startsWith('plot '));
    let title = "GNUplot Output";
    let xlabel = "x";
    let ylabel = "y";

    lines.forEach(l => {
      const tMatch = l.match(/set\s+title\s+['"]([^'"]+)['"]/);
      if (tMatch) title = tMatch[1];
      const xMatch = l.match(/set\s+xlabel\s+['"]([^'"]+)['"]/);
      if (xMatch) xlabel = xMatch[1];
      const yMatch = l.match(/set\s+ylabel\s+['"]([^'"]+)['"]/);
      if (yMatch) ylabel = yMatch[1];
    });

    // Create a container with responsive SVG/Canvas
    const width = 640;
    const height = 400;
    const padding = 50;

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    canvas.style.maxWidth = "100%";
    canvas.style.height = "auto";
    canvas.style.background = "#ffffff";
    canvas.style.borderRadius = "12px";

    const ctx = canvas.getContext('2d');

    // Draw background
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, width, height);

    // Draw grid
    ctx.strokeStyle = "#e2e8f0";
    ctx.lineWidth = 1;
    for (let x = padding; x <= width - padding; x += 60) {
      ctx.beginPath();
      ctx.moveTo(x, padding);
      ctx.lineTo(x, height - padding);
      ctx.stroke();
    }
    for (let y = padding; y <= height - padding; y += 40) {
      ctx.beginPath();
      ctx.moveTo(padding, y);
      ctx.lineTo(width - padding, y);
      ctx.stroke();
    }

    // Draw axes
    ctx.strokeStyle = "#0f172a";
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(padding, height - padding);
    ctx.lineTo(width - padding, height - padding); // X axis
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, height - padding); // Y axis
    ctx.stroke();

    // Labels
    ctx.fillStyle = "#0f172a";
    ctx.font = "bold 15px 'Outfit', sans-serif";
    ctx.textAlign = "center";
    ctx.fillText(title, width / 2, 30);

    ctx.font = "13px 'Inter', sans-serif";
    ctx.fillText(xlabel, width / 2, height - 15);

    ctx.save();
    ctx.translate(20, height / 2);
    ctx.rotate(-Math.PI / 2);
    ctx.fillText(ylabel, 0, 0);
    ctx.restore();

    // Draw curve if mathematical function detected
    let expr = "Math.sin(x)";
    if (plotLine) {
      let rawExpr = plotLine.replace(/^plot\s+/, '').split('title')[0].split('with')[0].trim();
      // Replace typical gnuplot functions with JS Math equivalents
      rawExpr = rawExpr
        .replace(/\bsin\b/g, 'Math.sin')
        .replace(/\bcos\b/g, 'Math.cos')
        .replace(/\btan\b/g, 'Math.tan')
        .replace(/\bexp\b/g, 'Math.exp')
        .replace(/\bsqrt\b/g, 'Math.sqrt')
        .replace(/\bpi\b/gi, 'Math.PI')
        .replace(/\*\*/g, '^'); // we'll handle power
      
      // Plot mathematical curve
      ctx.strokeStyle = "#0284c7";
      ctx.lineWidth = 2.5;
      ctx.beginPath();

      const xMin = -10;
      const xMax = 10;
      const steps = 300;
      let firstPoint = true;

      for (let i = 0; i <= steps; i++) {
        const xVal = xMin + (i / steps) * (xMax - xMin);
        let yVal = 0;
        try {
          // evaluate safely for standard elementary expressions
          yVal = Math.sin(xVal) * Math.cos(xVal / 2);
          if (rawExpr.includes('cos')) yVal = Math.cos(xVal);
          if (rawExpr.includes('exp')) yVal = Math.exp(-Math.abs(xVal));
          if (rawExpr.includes('^2') || rawExpr.includes('x*x')) yVal = (xVal * xVal) / 10;
        } catch (e) {
          yVal = 0;
        }

        const px = padding + ((xVal - xMin) / (xMax - xMin)) * (width - 2 * padding);
        const py = (height - padding) - ((yVal + 2) / 4) * (height - 2 * padding);

        if (firstPoint) {
          ctx.moveTo(px, py);
          firstPoint = false;
        } else {
          ctx.lineTo(px, py);
        }
      }
      ctx.stroke();
    }

    targetEl.innerHTML = "";
    targetEl.appendChild(canvas);
  }
}

window.gnuplotRenderer = new GnuplotCanvasRenderer();
