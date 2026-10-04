/**
 * Plano chart language: flat-topped bars, 1.5px strokes, a dashed grid and
 * mono axes. Two series at most read as blue and ink; red only marks what
 * missed its target. Series colours never follow laboratory branding, so a
 * colour always means the same thing across screens.
 */
export const chartPalette = ['#0757b5', '#061f46', '#8595a9', '#176b43', '#8a5200', '#b42332', '#70b5ff', '#b4c2d2']

export const chartStatus = {
  positive: '#176b43',
  caution: '#8a5200',
  negative: '#b42332',
  neutral: '#8595a9',
  primary: '#0757b5',
  structure: '#061f46',
}

export const chartFontFamily = "'Geist Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace"

export function chartTokens(dark = false) {
  return {
    text: dark ? '#edf2f8' : '#061f46',
    muted: dark ? '#5c6e88' : '#8595a9',
    grid: dark ? '#1a2a43' : '#d9e2ec',
    surface: dark ? '#070f1c' : '#ffffff',
  }
}

/**
 * Global ApexCharts defaults: every chart inherits the typeface, palette,
 * dashed grid and square legend markers unless it sets its own.
 */
export function applyChartDefaults() {
  if (typeof window === 'undefined') {
    return
  }

  const dark = document.documentElement.classList.contains('dark')
  const tokens = chartTokens(dark)

  window.Apex = {
    ...(window.Apex ?? {}),
    chart: {
      fontFamily: chartFontFamily,
      foreColor: tokens.muted,
      toolbar: { show: false },
      zoom: { enabled: false },
      dropShadow: { enabled: false },
      animations: { enabled: true, speed: 420, animateGradually: { enabled: false }, dynamicAnimation: { enabled: true, speed: 240 } },
    },
    colors: dark ? ['#70b5ff', '#edf2f8', ...chartPalette.slice(2)] : chartPalette,
    fill: { type: 'solid', opacity: 1 },
    grid: { borderColor: tokens.grid, strokeDashArray: 4, padding: { left: 8, right: 8 } },
    stroke: { width: 1.5, curve: 'straight', lineCap: 'square' },
    markers: { size: 3, shape: 'square', strokeWidth: 1.5 },
    dataLabels: { enabled: false, style: { fontSize: '10px', fontWeight: 500 } },
    legend: { fontSize: '11px', fontWeight: 500, markers: { size: 4, shape: 'square' }, itemMargin: { horizontal: 10, vertical: 4 } },
    tooltip: { style: { fontSize: '11px' }, marker: { show: true } },
    xaxis: { axisBorder: { show: true, color: tokens.grid }, axisTicks: { show: false }, labels: { style: { fontSize: '10px' } } },
    yaxis: { labels: { style: { fontSize: '10px' } } },
    plotOptions: { bar: { borderRadius: 0 }, pie: { donut: { size: '72%', labels: { name: { fontSize: '11px' }, value: { fontSize: '24px', fontWeight: 700 } } } } },
    states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
  }
}
