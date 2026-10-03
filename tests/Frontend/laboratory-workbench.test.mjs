import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { buildBrandingCssVariables, contrastingText } from '../../resources/js/Utils/brandingPalette.js'

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')

test('brand colours choose readable foregrounds across the RGB colour space', () => {
  const luminance = (hex) => {
    const c = [1, 3, 5].map((offset) => parseInt(hex.slice(offset, offset + 2), 16) / 255).map((v) => v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4)
    return c[0] * 0.2126 + c[1] * 0.7152 + c[2] * 0.0722
  }
  for (let r = 0; r <= 255; r += 17) for (let g = 0; g <= 255; g += 17) for (let b = 0; b <= 255; b += 17) {
    const hex = `#${[r, g, b].map((n) => n.toString(16).padStart(2, '0')).join('')}`
    const background = luminance(hex)
    const foreground = luminance(contrastingText(hex))
    const ratio = (Math.max(background, foreground) + 0.05) / (Math.min(background, foreground) + 0.05)
    assert.ok(ratio >= 4.5, `${hex}: ${ratio}`)
  }
})

test('branding changes action colours but never semantic status colours', () => {
  const variables = buildBrandingCssVariables({ primary_color: '#ffdd00' })
  assert.equal(variables['--brand-primary'], '#ffdd00')
  assert.equal(variables['--brand-on-primary'], '#000000')
  assert.equal(variables['--color-danger-500'], undefined)
})

test('collapsed navigation retains accessible names and expanded state', () => {
  assert.match(read('Shared/Layouts/Layout.vue'), /:aria-expanded="desktopSidebarOpen"/)
  assert.match(read('Shared/Navigation/app-sidebar.vue'), /:title="area\.label"/)
  assert.match(read('Shared/Navigation/app-sidebar.vue'), /:aria-label="area\.label"/)
})

test('workbench and network use actual paginated records and explicit empty states', () => {
  assert.match(read('Pages/LaboratoryWorkbench.vue'), /Pagination[^>]+v-bind="samples"/)
  assert.match(read('Pages/LabNetwork/Index.vue'), /Pagination[^>]+v-bind="stock"/)
  assert.match(read('Pages/LabNetwork/Index.vue'), /Nenhum material encontrado/)
  assert.match(read('Pages/LaboratoryWorkbench.vue'), /Nenhuma amostra neste filtro/)
})

test('admin navigation matches the backend admin permission override', () => {
  assert.match(read('Composables/usePermissions.js'), /hasRole\('admin'\) \|\|/)
})

test('sample queue uses server pagination and a cancellable Inertia quick view', () => {
  const page = read('Pages/VAPSamples/Queue.vue')
  assert.match(page, /useHttp/)
  assert.match(page, /detail.cancel\(\)/)
  assert.match(page, /version === requestVersion/)
  assert.match(page, /samples.links.next/)
  assert.match(page, /samples.meta.total/)
  assert.match(page, /role="alert"/)
  assert.match(page, /aria-busy="filter.processing"/)
  assert.doesNotMatch(page, /Transition|transition-all|setTimeout/)
  assert.match(read('Shared/Layouts/Layout.vue'), /route\('vap_samples.queue'\)/)
})

test('sample intake leaves automatic numbering to the server', () => {
  const page = read('Pages/VAPSamples/Index.vue')
  assert.match(page, /v-model="form.code"/)
  assert.match(page, /placeholder="Gerado automaticamente"/)
  assert.doesNotMatch(page, /form\.code\s*=\s*`SMP-/)
})

test('approved workbench geometry is a production design contract', () => {
  const css = readFileSync(new URL('../../resources/css/laboratory-workbench.css', import.meta.url), 'utf8')
  assert.match(css, /\.workbench-page \.lab-metrics\{display:grid;grid-template-columns:repeat\(4,minmax\(0,1fr\)\);gap:0\.75rem/)
  assert.match(css, /\.workbench-page \.lab-overview-grid\{display:grid;grid-template-columns:minmax\(0,1fr\) 21rem;gap:0\.75rem/)
  assert.match(css, /\.workbench-page \.lab-detail-rail\{border:1px solid var\(--ds-card-border\)/)
  assert.match(css, /--lab-panel: var\(--ds-panel\)/)
  assert.match(css, /scale:\.96/)
  assert.doesNotMatch(css, /#lab-workbench|DM Sans|Manrope|fonts\.googleapis\.com|light-dark\(/)
  assert.match(css, /prefers-reduced-motion:reduce/)
  assert.match(css, /focus-visible/)
})

test('overview preserves the approved editorial composition without mock data', () => {
  const page = read('Pages/LaboratoryWorkbench.vue')
  for (const element of ['lab-page-head', 'lab-metrics', 'lab-overview-grid', 'lab-panel', 'lab-queue', 'lab-overview-rail']) {
    assert.ok(page.includes(element), element)
  }
  assert.match(page, /O laboratório, em foco\./)
  assert.doesNotMatch(page, /ds-card|grid-cols-4|AM-26-0148/)
  assert.doesNotMatch(page, /px-4 py-7|lg:px-10/)
  assert.match(read('Pages/LabNetwork/Index.vue'), /lab-network-cards/)
})
