import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { compileScript, compileTemplate, parse } from '@vue/compiler-sfc'

const paths = ['Pages/ItemCategories/Index.vue', 'Components/catalogs/ReferenceCatalogManager.vue', 'Pages/VAPInventory/Items/Create.vue', 'Pages/VAPInventory/Items/Edit.vue', 'Pages/VAPInventory/Items/Index.vue', 'Pages/VAPInventory/Items/Show.vue']
const read = path => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')

test('equipment quick navigation selects the explicit kind in route and fallback modes', () => {
  const source = read('Components/quick-menu.vue')
  const expression = source.match(/title: 'gestlab.quick_menu.boards.equipments.title', href: (safeRoute\([^\n]+?\)), icon:/)[1]
  const evaluate = new Function('safeRoute', `return ${expression}`)
  const calls = []
  evaluate((name, params, fallback) => calls.push({ name, params, fallback }))
  assert.deepEqual(calls, [{ name: 'vap-inventory.items.index', params: { inventory_type: 'equipment' }, fallback: '/vap-inventory/items?inventory_type=equipment' }])
  const { descriptor, errors } = parse(source)
  assert.deepEqual(errors, [])
  const script = compileScript(descriptor, { id: 'quick-menu' })
  assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename: 'quick-menu.vue', id: 'quick-menu', compilerOptions: { bindingMetadata: script.bindings } }).errors, [])
})

for (const path of paths) {
  test(`${path} compiles with the existing component contracts`, () => {
    const { descriptor, errors } = parse(read(path))
    assert.deepEqual(errors, [])
    compileScript(descriptor, { id: path })
    const result = compileTemplate({ source: descriptor.template.content, filename: path, id: path })
    assert.deepEqual(result.errors, [])
  })
}

test('classification has two explicit choices, a material default and a permanent lock explanation', () => {
  const source = read(paths[0])
  assert.match(source, /key: 'inventory_type', type: 'select'/)
  assert.match(source, /defaultValue: 'material'/)
  assert.match(source, /value: 'equipment'/)
  assert.match(source, /lockWhenRecordKey: 'type_locked'/)
  assert.match(source, /incluindo itens arquivados/)
})

test('select remains labelled, explains disabled state, keeps selected data and blocks editing while saving', () => {
  const source = read(paths[1])
  assert.match(source, /<select[\s\S]*?v-model="form\[field.key\]"/)
  assert.match(source, /:disabled="form.processing \|\| Boolean\(field.lockWhenRecordKey && editingRecord\?\.\[field.lockWhenRecordKey\]\)"/)
  assert.match(source, /:aria-describedby="`reference-\$\{field.key\}-help`"/)
  assert.match(source, /editingRecord.value = null/)
  assert.match(source, /editingRecord.value = data/)
  assert.match(source, /field.lockedHelp : field.help/)
})

for (const path of paths.slice(2, 4)) {
  test(`${path} shows equipment fields from explicit type, not category names or IDs`, () => {
    const source = read(path)
    assert.match(source, /return category\?\.inventory_type === 'equipment'/)
    assert.doesNotMatch(source, /includes\('equipamento'\)/)
  })
}

test('catalogue controls honor server capabilities and retain the explicit kind filter', () => {
  const source = read(paths[4])
  assert.equal((source.match(/v-if="canExport && localFilters.archive_state !== 'archived'"/g) || []).length, 1)
  assert.equal((source.match(/v-if="item.can_edit"/g) || []).length, 1)
  assert.equal((source.match(/v-if="item.can_delete"/g) || []).length, 1)
  assert.match(source, /inventory_type: props.filters.inventory_type \|\| ''/)
  assert.match(source, /<Link v-if="canCreate" :href="createItemUrl"/)
  assert.match(source, /<Link v-if="canCreate && localFilters.archive_state !== 'archived'" :href="createItemUrl"/)
  assert.match(source, /Exportar XLSX/)
})

test('detail classification and new-item links retain explicit kind, including archived categories', () => {
  assert.match(read(paths[5]), /return props.item.category\?\.inventory_type === 'equipment'/)
  assert.doesNotMatch(read(paths[5]), /includes\('equipamento'\)/)
  assert.match(read(paths[5]), /<Link v-if="canEdit" :href="route\('vap-inventory.items.edit'/)
  const index = read(paths[4])
  assert.match(index, /localFilters.inventory_type \? \{ inventory_type: localFilters.inventory_type \} : \{\}/)
  assert.equal((index.match(/:href="createItemUrl"/g) || []).length, 2)
})

test('catalogue title and path follow the navigation category or explicit kind', () => {
  const source = read(paths[4])
  const constant = name => new Function(`return (${source.match(new RegExp(`const ${name} = (\\{[^\\n]+\\})`))[1]})`)()
  const body = source.match(/const scopeTitle = computed\(\(\) => \{([\s\S]*?)\n\}\)/)[1]
  const scopeTitle = filters => new Function('props', 'navigationCategoryTitles', 'inventoryTypeTitles', body)(
    { filters, categories: [{ id: 7, name: 'Vidraria' }] }, constant('navigationCategoryTitles'), constant('inventoryTypeTitles'))
  assert.equal(scopeTitle({ category_id: '1' }), 'Equipamentos')
  assert.equal(scopeTitle({ category_id: 2 }), 'Reagentes e consumíveis')
  assert.equal(scopeTitle({ category_id: 7 }), 'Vidraria')
  assert.equal(scopeTitle({ inventory_type: 'equipment' }), 'Equipamentos')
  assert.equal(scopeTitle({}), '')
  assert.match(source, /<PageHeader :crumbs="crumbs" :title="listTitle" :lede="lede">/)
  assert.match(source, /\{ title: 'Itens', url: route\('vap-inventory\.items\.index'\) \}, \{ title: scopeTitle\.value \}/)
})
