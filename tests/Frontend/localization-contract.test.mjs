import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import path from 'node:path'
import { createRequire } from 'node:module'
import test from 'node:test'

const require = createRequire(path.join(process.cwd(), 'package.json'))
const { parse: parseSfc } = require('@vue/compiler-sfc')
const { parse: parseTemplate } = require('@vue/compiler-dom')
const babelParser = require('@babel/parser')
const root = path.resolve(import.meta.dirname, '../..')

const visibleAttributes = new Set([
  'alt', 'aria-description', 'aria-label', 'cancel', 'cancel-text', 'confirm',
  'confirm-text', 'description', 'empty-label', 'empty-message', 'empty-text',
  'help', 'hint', 'label', 'placeholder', 'subtitle', 'title', 'tooltip',
])
const visibleProperties = new Set([
  'alt', 'ariaDescription', 'ariaLabel', 'badge', 'caption', 'context', 'copy',
  'description', 'detail', 'eyebrow', 'heading', 'help', 'hint', 'kicker', 'label',
  'message', 'note', 'placeholder', 'subtitle', 'text', 'title', 'tooltip',
])
const ao90Pattern = /\b(?:ação|ações|atividade|atividades|ativo|ativa|ativos|ativas|atual|atuais|atualize|afeta|afetado|afetada|afetação|contato|contatos|correção|correto|correta|corretivo|corretiva|corretivos|corretivas|deteção|detetado|detetada|direção|diretor|direto|direta|diretos|diretas|diretamente|efetivo|efetiva|exato|exata|fatura|faturas|faturado|faturar|faturação|inativo|inativa|interação|objetivo|objetiva|objetivos|objetivas|ótimo|ótima|perspetiva|projeto|projetos|proteção|receção|rececionar|rececionado|rececionada|rececionados|rececionadas|respetivo|respetiva|retificação|seleção|selecionar|selecionado|selecionada|seletivo|seletiva|seletivos|seletivas|setor|setores|transação|transações)\b/iu
const brazilianPattern = /\b(?:baixar|cadastro|cadastrado|deletar|equipe|escopo|estoque|gerenciar|liberação|liberações|recebimento|recebimentos|senha|solicitação|solicitações|tela|usuário|usuários|você)\b/iu
const englishLeakPattern = /\b(?:dashboard|lab code|laboratory ops|logout|passkey|preview|qms|sample entry|settings|snapshot|stock|templates?)\b/iu
const missingAccentPattern = /\b(?:alteracao|alteracoes|analitica|analitico|analiticos|area|calculo|catalogo|comparacao|comparavel|composicao|comunicacao|concluido|concluida|confirmacao|conteudo|descricao|destinatario|diferenca|diferencas|disponivel|disponiveis|elegiveis|emissao|estao|evidencia|execucao|facturacao|faturacao|fisico|formula|formatacao|imutavel|impressao|informacao|ligacao|liquidacao|monetario|necessaria|notificacoes|ocorrencia|organizacao|passara|periodo|precisao|protecao|rapidos|rastreavel|rececao|reconciliacao|relacoes|reposicao|revisoes|saude|selecao|sensivel|simbolos|solicitacoes|substituida|telefonico|tendencia|unico|ultimos|utilizavel|visualizacao|visivel|visiveis)\b/iu

function filesIn(directory, extensions) {
  return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const absolute = path.join(directory, entry.name)

    if (entry.isDirectory()) {
      return filesIn(absolute, extensions)
    }

    return extensions.some((extension) => entry.name.endsWith(extension)) ? [absolute] : []
  })
}

function walk(node, callback, parent = null, parentKey = null) {
  if (!node || typeof node !== 'object') {
    return
  }

  callback(node, parent, parentKey)

  for (const [key, value] of Object.entries(node)) {
    if (['comments', 'errors', 'extra', 'loc', 'tokens'].includes(key)) {
      continue
    }

    if (Array.isArray(value)) {
      value.forEach((child) => walk(child, callback, node, key))
    } else {
      walk(value, callback, node, key)
    }
  }
}

function walkTemplate(node, callback) {
  callback(node)

  for (const child of node?.children ?? []) {
    walkTemplate(child, callback)
  }

  for (const branch of node?.branches ?? []) {
    walkTemplate(branch, callback)
  }
}

function propertyName(node) {
  return node?.type === 'Identifier' ? node.name : node?.type === 'StringLiteral' ? node.value : null
}

function expressionStrings(expression) {
  if (!expression?.content) {
    return []
  }

  let ast

  try {
    ast = babelParser.parseExpression(expression.content, { plugins: ['typescript'] })
  } catch {
    return []
  }

  const values = []

  walk(ast, (node, parent) => {
    if (node.type === 'StringLiteral' && !['BinaryExpression', 'CallExpression', 'MemberExpression', 'ObjectProperty'].includes(parent?.type)) {
      values.push(node.value)
    }

    if (node.type === 'TemplateElement') {
      values.push(node.value?.cooked ?? '')
    }
  })

  return values
}

function visibleVueCopy(file) {
  const source = readFileSync(file, 'utf8')
  const { descriptor } = parseSfc(source, { filename: file })
  const values = []

  if (descriptor.template?.content.trim()) {
    const template = parseTemplate(descriptor.template.content, { comments: true })

    walkTemplate(template, (node) => {
      if (node.type === 2) {
        values.push(node.content)
      }

      if (node.type === 5) {
        values.push(...expressionStrings(node.content))
      }

      if (node.type === 1) {
        for (const prop of node.props) {
          if (prop.type === 6 && prop.value && visibleAttributes.has(prop.name)) {
            values.push(prop.value.content)
          }

          if (prop.type === 7 && prop.exp && prop.arg?.type === 4 && visibleAttributes.has(prop.arg.content)) {
            values.push(...expressionStrings(prop.exp))
          }
        }
      }
    })
  }

  for (const block of [descriptor.script, descriptor.scriptSetup]) {
    if (!block?.content.trim()) {
      continue
    }

    const ast = babelParser.parse(block.content, {
      sourceType: 'module',
      plugins: ['dynamicImport', 'importMeta', 'jsx', 'topLevelAwait', ...(block.lang?.startsWith('ts') ? ['typescript'] : [])],
    })

    walk(ast, (node, parent, parentKey) => {
      if (node.type !== 'StringLiteral') {
        return
      }

      if (parent?.type === 'ObjectProperty' && parentKey === 'value' && visibleProperties.has(propertyName(parent.key))) {
        values.push(node.value)
      }

      if (parent?.type === 'VariableDeclarator' && /(?:label|title|description|message|text|copy|hint|note)$/i.test(parent.id?.name ?? '')) {
        values.push(node.value)
      }
    })
  }

  return values
    .map((value) => String(value).replace(/\s+/g, ' ').trim())
    .filter((value) => value && !value.startsWith('gestlab.'))
}

test('Portuguese is the application and frontend fallback language', () => {
  const appConfig = readFileSync(path.join(root, 'config/app.php'), 'utf8')
  const appBootstrap = readFileSync(path.join(root, 'resources/js/app.js'), 'utf8')

  assert.match(appConfig, /'fallback_locale'\s*=>\s*(?:env\('APP_FALLBACK_LOCALE',\s*)?'pt'\)?/)
  assert.match(appBootstrap, /fallbackLang:\s*['"]pt['"]/)
})

test('Portuguese JSON catalogues remain valid, synchronized, and Angolan', () => {
  const primary = JSON.parse(readFileSync(path.join(root, 'lang/pt.json'), 'utf8'))
  const nested = JSON.parse(readFileSync(path.join(root, 'lang/pt/pt.json'), 'utf8'))

  assert.deepEqual(nested, primary)

  for (const [key, value] of Object.entries(primary)) {
    assert.doesNotMatch(String(value), ao90Pattern, `AO90 form in translation ${key}`)
    assert.doesNotMatch(String(value), brazilianPattern, `Brazilian form in translation ${key}`)
    assert.doesNotMatch(String(value), missingAccentPattern, `Missing accent in translation ${key}`)
  }
})

test('rendered Vue copy follows the Portuguese localization contract', () => {
  const failures = []

  for (const file of filesIn(path.join(root, 'resources/js'), ['.vue'])) {
    for (const copy of visibleVueCopy(file)) {
      for (const [name, pattern] of [
        ['AO90', ao90Pattern],
        ['Brazilian', brazilianPattern],
        ['English', englishLeakPattern],
        ['accent', missingAccentPattern],
      ]) {
        if (pattern.test(copy)) {
          failures.push(`${path.relative(root, file)} [${name}] ${copy}`)
        }
      }
    }
  }

  assert.deepEqual(failures, [])
})

test('localized copy does not alter technical frontend identifiers', () => {
  const sources = filesIn(path.join(root, 'resources/js'), ['.vue', '.js', '.mjs', '.ts'])
    .map((file) => readFileSync(file, 'utf8'))
    .join('\n')

  assert.doesNotMatch(sources, /^import [^\n]*['"][^'"\n]*[À-ÿ][^'"\n]*['"]/mu)
  assert.doesNotMatch(sources, /route\(['"][^'"\n]*[À-ÿ][^'"\n]*['"]/u)
  assert.doesNotMatch(sources, /(?:\$t|trans)\(['"]gestlab\.[^'"\n]*[À-ÿ][^'"\n]*['"]/u)
  assert.doesNotMatch(sources, /class=['"][^'"\n]*[À-ÿ][^'"\n]*['"]/u)
})

test('PDF templates declare Portuguese and omit known bilingual leaks', () => {
  const pdfSources = filesIn(path.join(root, 'resources/views'), ['.blade.php'])
    .map((file) => readFileSync(file, 'utf8'))
    .join('\n')

  assert.doesNotMatch(pdfSources, /<html\s+lang=['"]en['"]/iu)
  assert.doesNotMatch(pdfSources, /\b(?:Chemical Analysis Worksheet|Results and Uncertainty|Validation Date|Sample identification|Analysis report|End of analytical results|Maintenance calendar|Maintenance tasks report)\b/iu)
  assert.doesNotMatch(pdfSources, /(?:Cliente\s*\/\s*Customer|Laboratório\s*\/\s*Laboratory|Estado\s*\/\s*Status)/iu)
  assert.doesNotMatch(pdfSources, /(?:The sampling was carried out|STATUS:\s*(?:AGUARDANDO|PENDING))/iu)
})

test('backend user feedback no longer contains known English fallbacks', () => {
  const phpSources = filesIn(path.join(root, 'app'), ['.php'])
    .map((file) => readFileSync(file, 'utf8'))
    .join('\n')

  assert.doesNotMatch(phpSources, /\b(?:Session Expired|Your session has expired|Only completed or canceled samples|Sample discard recorded successfully|This connector is not active|Stock adjusted successfully|Reagent consumed successfully)\b/u)

  const catalogue = readFileSync(path.join(root, 'lang/pt/gestlab.php'), 'utf8')
  assert.doesNotMatch(catalogue, /(?:informa|configura|identifica|importa|exporta|notifica|opera|aprova|valida|autentica|classifica)cção/iu)

  const generatedOutputSources = [
    path.join(root, 'app/Exports/ActivityLogExport.php'),
    path.join(root, 'app/Exports/MaintenanceCalendarExport.php'),
    path.join(root, 'app/Exports/MaintenanceTasksExport.php'),
    path.join(root, 'app/Models/Inventory.php'),
    path.join(root, 'app/Models/QualityCertificateRevision.php'),
    path.join(root, 'resources/views/emails/maintenance/overdue.blade.php'),
    path.join(root, 'resources/views/emails/maintenance/reminder.blade.php'),
  ].map((file) => readFileSync(file, 'utf8')).join('\n')

  assert.doesNotMatch(generatedOutputSources, /\b(?:Activity Logs|Created revision|Days Overdue|Log Name|Low Stock|No tasks scheduled|Out of Stock|Task Number|Upcoming Maintenance Tasks)\b/u)
})
