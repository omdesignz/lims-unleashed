import assert from 'node:assert/strict'
import { readFileSync, readdirSync, statSync } from 'node:fs'
import { dirname, join, normalize, relative } from 'node:path'
import test from 'node:test'
import { fileURLToPath } from 'node:url'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'

const root = fileURLToPath(new URL('../../resources/js/', import.meta.url))
const read = (path) => readFileSync(join(root, path), 'utf8')

function vueFiles(directory) {
  return readdirSync(join(root, directory)).flatMap((entry) => {
    const path = join(directory, entry)
    if (statSync(join(root, path)).isDirectory()) return vueFiles(path)
    return entry.endsWith('.vue') ? [path] : []
  })
}

const sources = Object.fromEntries([...vueFiles('Pages'), ...vueFiles('Components')].map((path) => [path, read(path)]))

// A file carries the Plano page header when it renders it, or renders a component that does.
const headerTags = /<(PageHeader|ModuleHero|NotificationAdminHeader)\b/
const bearing = new Set(Object.keys(sources).filter((path) => headerTags.test(sources[path])))
const imports = (path) => [...sources[path].matchAll(/from\s+['"]([^'"]+\.vue)['"]/g)].map(([, target]) => (
  target.startsWith('@/') ? target.slice(2) : normalize(join(dirname(path), target))
))
for (let changed = true; changed;) {
  changed = false
  for (const path of Object.keys(sources)) {
    if (!bearing.has(path) && imports(path).some((target) => bearing.has(target))) {
      bearing.add(path)
      changed = true
    }
  }
}

// Screens that draw their whole canvas themselves (the Today band) or that are
// superseded prototypes kept only for their routes; they are not part of the contract.
const exempt = new Set([
  'Pages/LaboratoryWorkbench.vue',
  'Pages/Boards/Show.vue',
  'Pages/Files/Index.vue',
  'Pages/Folders/Index.vue',
  'Pages/Folders/Show.vue',
  'Pages/ModernFolders/Index.vue',
  'Pages/ModernFolders/Show.vue',
  'Pages/Media/Index.vue',
  'Pages/Media/Create.vue',
  'Pages/Formulas/IndexMB.vue',
  'Pages/InventoryOrders/Create.vue',
  'Pages/InventoryOrders/Edit.vue',
  'Pages/InventoryOrders/Show.vue',
  'Pages/Proposals/Show.vue',
  'Pages/User/Security.vue',
  'Pages/VAPProposalTemplates/Create.vue',
  'Pages/VAPProposalTemplates/Edit.vue',
])

const controllers = fileURLToPath(new URL('../../app/', import.meta.url))
function phpFiles(directory) {
  return readdirSync(directory).flatMap((entry) => {
    const path = join(directory, entry)
    if (statSync(path).isDirectory()) return phpFiles(path)
    return entry.endsWith('.php') ? [path] : []
  })
}
const rendered = new Set(phpFiles(controllers).flatMap((path) => (
  [...readFileSync(path, 'utf8').matchAll(/(?:Inertia::render|inertia)\(\s*'([^']+)'/g)].map(([, name]) => `Pages/${name}.vue`)
)))

test('every staff screen a controller renders opens with the Plano page header', () => {
  const staffPages = Object.keys(sources).filter((path) => (
    path.startsWith('Pages/')
    && rendered.has(path)
    && !/^Pages\/(Auth|PortalAuth|ClientPortal|Public)\//.test(path)
    && !['Pages/Error.vue', 'Pages/RateForm.vue'].includes(path)
    && !exempt.has(path)
  ))

  assert.ok(staffPages.length > 200, `expected the staff screens, found ${staffPages.length}`)
  assert.deepEqual(staffPages.filter((path) => !bearing.has(path)), [])
})

test('the page header claims the canvas and the shell steps back', () => {
  const header = read('Components/plano/PageHeader.vue')
  const layout = read('Shared/Layouts/Layout.vue')

  assert.match(header, /const shell = inject\(planoShellKey, null\)/)
  assert.match(header, /shell\?\.claim\(\)/)
  assert.match(header, /onBeforeUnmount\(\(\) => shell\?\.release\(\)\)/)
  assert.match(layout, /provide\(planoShellKey, \{/)
  assert.match(layout, /<nav v-if="!ownsCanvas" class="pl-crumbs pl-page-crumbs"/)
  assert.match(layout, /'lims-backoffice-content': !ownsCanvas/)

  // Claim and release balance, so the shell returns to older screens after a visit.
  const body = layout.match(/const pageHeaders = ref\(0\)[\s\S]*?\n\}\)\n/)[0]
  const shell = new Function('ref', 'computed', 'provide', 'planoShellKey', 'planoPages', 'page', 'crumbs', `${body}; return { pageHeaders, ownsCanvas, headerTitle }`)
  let provided
  const { pageHeaders, ownsCanvas, headerTitle } = shell((value) => ({ value }), (getter) => ({ get value() { return getter() } }), (_, api) => { provided = api }, Symbol('shell'), [], { component: 'Countries/Index' }, { value: [] })
  assert.equal(ownsCanvas.value, false)
  provided.claim()
  assert.equal(ownsCanvas.value, true)

  // The header names the page; the name leaves with the header.
  provided.setTitle('Países')
  assert.equal(headerTitle.value, 'Países')
  provided.release()
  provided.release()
  assert.equal(pageHeaders.value, 0)
  assert.equal(ownsCanvas.value, false)
  assert.equal(headerTitle.value, '')
})

test('the header path keeps the shell area and never repeats it', () => {
  const header = read('Components/plano/PageHeader.vue')
  const body = header.match(/const path = computed\(\(\) => \{([\s\S]*?)\n\}\)/)[1]
  const path = (props, shellCrumbs) => new Function('props', 'shell', body)(props, { crumbs: { value: shellCrumbs } })
  const shellCrumbs = [{ title: 'Comercial', url: '/dashboard' }, { title: 'Área comercial', current: true }]

  assert.deepEqual(path({ crumbs: [], trail: [] }, shellCrumbs), shellCrumbs)
  assert.deepEqual(path({ crumbs: [{ title: 'Inventário' }], trail: [] }, shellCrumbs), [{ title: 'Inventário' }])
  assert.deepEqual(
    path({ crumbs: [], trail: [{ title: 'Clientes', url: '/customers' }, { title: 'Novo' }] }, shellCrumbs).map((crumb) => crumb.title),
    ['Comercial', 'Clientes', 'Novo'],
  )
  assert.deepEqual(
    path({ crumbs: [], trail: [{ title: 'Comercial' }, { title: 'Facturas' }] }, shellCrumbs).map((crumb) => crumb.title),
    ['Comercial', 'Facturas'],
  )
})

test('registers are a Plano queue: filter row, one panel, a selection bar', () => {
  const table = read('Components/records-table.vue')
  const selection = read('Components/select-action.vue')
  const catalogue = read('Components/catalogs/ReferenceCatalogManager.vue')

  assert.match(table, /<form class="pl-filter" role="search"/)
  assert.match(table, /class="pl-filter-input"/)
  assert.match(table, /<section class="pl-panel"/)
  assert.match(table, /<DataTable v-if="record\.data\.length" class="pl-stack-table">/)
  assert.match(table, /:data-label="\$t\(field\.name\)"/)
  assert.doesNotMatch(table, /md:hidden|hidden md:block/)
  assert.match(selection, /class="pl-selection"/)
  assert.match(catalogue, /<div class="pl-page" data-template="queue">/)
  assert.match(catalogue, /<PageHeader :trail="\[\{ title: kicker \}, \{ title \}\]"/)
  assert.match(catalogue, /<SlideOver v-if="isPanelOpen" size="narrow"/)
  assert.doesNotMatch(catalogue, /const metrics = computed/)
})

test('an empty query string does not read as an active filter', () => {
  const table = read('Components/records-table.vue')
  const initial = table.match(/const initialQuery = (.*);/)[1]
  const active = table.match(/const hasActiveQuery = computed\(\(\) => \{([\s\S]*?)\n\}\);/)[1]
  const state = (query) => {
    const initialQuery = new Function('props', `return ${initial}`)({ query })
    const reactiveQuery = { search: initialQuery.search ?? '', filter: initialQuery.filter ?? null, date: initialQuery.date ?? null }

    return new Function('query', active)(reactiveQuery)
  }

  // Laravel serialises an empty query as [], whose `filter` is Array.prototype.filter.
  assert.equal(state([]), false)
  assert.equal(state(undefined), false)
  assert.equal(state({ date: { start: null, end: null } }), false)
  assert.equal(state({ search: 'água' }), true)
  assert.equal(state({ filter: 'trashed' }), true)
  assert.equal(state({ date: { start: '2026-10-01', end: null } }), true)
})

test('bulk actions offer only what the selection allows and ignore repeat clicks while pending', () => {
  const selection = read('Components/select-action.vue')
  const { descriptor } = parse(selection)
  const script = compileScript(descriptor, { id: 'selection-bar' }).content
  assert.match(script, /if \(action\.id === 'restore'\) return hasArchived\.value/)
  assert.match(script, /if \(action\.id === 'delete'\) return hasLive\.value/)

  const body = selection.match(/function executeAction\(actionId\) \{([\s\S]*?)\n\}\n/)[1]
  const emitted = []
  const execute = (props, actionId) => new Function('props', 'emit', 'actionId', body)(props, (...args) => emitted.push(args), actionId)
  execute({ processing: true, recordIds: [1] }, 'delete')
  execute({ processing: false, recordIds: [] }, 'delete')
  assert.deepEqual(emitted, [])
  execute({ processing: false, recordIds: [1] }, 'delete')
  assert.deepEqual(emitted, [['execute', 'delete']])
})

test('forms keep a single submit, in the next-step bar', () => {
  for (const path of ['Pages/Customers/Create.vue', 'Pages/Matrixes/Create.vue', 'Pages/Matrixes/Edit.vue', 'Pages/Parameters/Create.vue', 'Pages/Profiles/Edit.vue', 'Pages/Standards/Create.vue', 'Pages/CustomerRequests/Create.vue']) {
    const source = sources[path]
    assert.match(source, /<form class="pl-page/, path)
    assert.match(source, /<NextStepBar>/, path)
    assert.equal((source.match(/type="submit"/g) || []).length, 1, path)
  }
})

test('billing dossiers follow the dossier template and keep their handlers', () => {
  for (const [path, handlers] of [
    ['Pages/Invoices/Show.vue', ['downloadPDF', 'sendEmail', 'duplicateInvoice', 'editInvoice', 'recordPayment']],
    ['Pages/Quotes/Show.vue', ['downloadPDF', 'sendEmail', 'duplicateQuote', 'convertToInvoice', 'viewInvoice']],
  ]) {
    const source = sources[path]
    assert.match(source, /<div class="pl-page" data-template="dossier">/, path)
    assert.match(source, /class="pl-dossier-grid"/, path)
    assert.match(source, /<NextStepBar>/, path)
    assert.doesNotMatch(source, /bg-gradient|rounded-xl|text-gray-|\$router/, path)
    for (const handler of handlers) assert.match(source, new RegExp(`@click="${handler}"`), `${path} ${handler}`)
  }
})

test('every screen and shared part touched by the Plano pass compiles', () => {
  for (const [path, source] of Object.entries(sources)) {
    if (!bearing.has(path) && !/Components\/(records-table|select-action|select-filter|slide-over|vap-table\/table)\.vue$/.test(path)) continue
    const { descriptor, errors } = parse(source, { filename: path })
    assert.deepEqual(errors, [], path)
    const script = descriptor.script || descriptor.scriptSetup ? compileScript(descriptor, { id: path }) : null
    const template = compileTemplate({ id: path, filename: path, source: descriptor.template.content, compilerOptions: { bindingMetadata: script?.bindings } })
    assert.deepEqual(template.errors, [], path)
  }
})
