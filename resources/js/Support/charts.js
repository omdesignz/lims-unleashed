/**
 * Chart language for the product. Series colours are fixed and never follow
 * laboratory branding, so a colour always means the same thing across screens.
 */
export const chartPalette = ['#087cf0', '#061f46', '#22a45d', '#e0902b', '#e5484d', '#7c5ce0', '#14a3a8', '#98a1ae']

export const chartStatus = {
  positive: '#22a45d',
  caution: '#e0902b',
  negative: '#e5484d',
  neutral: '#98a1ae',
  primary: '#087cf0',
  structure: '#061f46',
}

export const chartFontFamily = "'Inter', 'Segoe UI', ui-sans-serif, system-ui, sans-serif"

export function chartTokens(dark = false) {
  return {
    text: dark ? '#f3f5f7' : '#111827',
    muted: dark ? '#8b95a3' : '#6b7482',
    grid: dark ? '#262d3a' : '#eef0f3',
    surface: dark ? '#161b23' : '#ffffff',
  }
}

/**
 * Global ApexCharts defaults: every chart inherits the typeface, palette,
 * quiet grid and compact legend unless it sets its own.
 */
export function applyChartDefaults() {
  if (typeof window === 'undefined') {
    return
  }

  const tokens = chartTokens(document.documentElement.classList.contains('dark'))

  window.Apex = {
    ...(window.Apex ?? {}),
    chart: {
      fontFamily: chartFontFamily,
      foreColor: tokens.muted,
      toolbar: { show: false },
      zoom: { enabled: false },
      animations: { enabled: true, speed: 420, animateGradually: { enabled: false }, dynamicAnimation: { enabled: true, speed: 240 } },
    },
    colors: chartPalette,
    grid: { borderColor: tokens.grid, strokeDashArray: 3, padding: { left: 8, right: 8 } },
    stroke: { width: 2, curve: 'smooth', lineCap: 'round' },
    dataLabels: { enabled: false, style: { fontSize: '12px', fontWeight: 500 } },
    legend: { fontSize: '12px', fontWeight: 400, markers: { size: 5, shape: 'circle' }, itemMargin: { horizontal: 10, vertical: 4 } },
    tooltip: { style: { fontSize: '12px' }, marker: { show: true } },
    xaxis: { axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '12px' } } },
    yaxis: { labels: { style: { fontSize: '12px' } } },
    plotOptions: { bar: { borderRadius: 3, borderRadiusApplication: 'end' }, pie: { donut: { labels: { name: { fontSize: '12px' }, value: { fontSize: '20px', fontWeight: 600 } } } } },
    states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } },
  }
}
