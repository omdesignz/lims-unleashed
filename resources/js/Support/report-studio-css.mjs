const DOCUMENT_ROOT_SELECTOR = '.studio-preview-document'
const DOCUMENT_ROOT_ALIASES = /^(?:html|body|:root|\.pdf-document|\.studio-document)(?=$|[\s.#:[>+~])/i
const NESTED_RULE_AT_RULES = new Set(['container', 'document', 'layer', 'media', 'scope', 'supports'])
const PRINT_MEDIA_PATTERN = /(?:^|[\s,(])print(?:$|[\s,)])/i

function consumeComment(css, index) {
  const end = css.indexOf('*/', index + 2)

  return end === -1 ? css.length : end + 2
}

function consumeQuotedString(css, index) {
  const quote = css[index]
  let cursor = index + 1

  while (cursor < css.length) {
    if (css[cursor] === '\\') {
      cursor += 2
      continue
    }

    if (css[cursor] === quote) {
      return cursor + 1
    }

    cursor += 1
  }

  return css.length
}

function findRuleBoundary(css, start) {
  let parentheses = 0
  let brackets = 0

  for (let cursor = start; cursor < css.length; cursor += 1) {
    const character = css[cursor]

    if (character === '/' && css[cursor + 1] === '*') {
      cursor = consumeComment(css, cursor) - 1
      continue
    }

    if (character === '"' || character === "'") {
      cursor = consumeQuotedString(css, cursor) - 1
      continue
    }

    if (character === '(') {
      parentheses += 1
    } else if (character === ')') {
      parentheses = Math.max(0, parentheses - 1)
    } else if (character === '[') {
      brackets += 1
    } else if (character === ']') {
      brackets = Math.max(0, brackets - 1)
    } else if (parentheses === 0 && brackets === 0 && (character === '{' || character === ';')) {
      return { index: cursor, character }
    }
  }

  return { index: css.length, character: '' }
}

function findMatchingBrace(css, openingBraceIndex) {
  let depth = 1

  for (let cursor = openingBraceIndex + 1; cursor < css.length; cursor += 1) {
    const character = css[cursor]

    if (character === '/' && css[cursor + 1] === '*') {
      cursor = consumeComment(css, cursor) - 1
      continue
    }

    if (character === '"' || character === "'") {
      cursor = consumeQuotedString(css, cursor) - 1
      continue
    }

    if (character === '{') {
      depth += 1
    } else if (character === '}') {
      depth -= 1

      if (depth === 0) {
        return cursor
      }
    }
  }

  return css.length - 1
}

function splitSelectorList(selectorList) {
  const selectors = []
  let start = 0
  let parentheses = 0
  let brackets = 0

  for (let cursor = 0; cursor < selectorList.length; cursor += 1) {
    const character = selectorList[cursor]

    if (character === '/' && selectorList[cursor + 1] === '*') {
      cursor = consumeComment(selectorList, cursor) - 1
      continue
    }

    if (character === '"' || character === "'") {
      cursor = consumeQuotedString(selectorList, cursor) - 1
      continue
    }

    if (character === '(') {
      parentheses += 1
    } else if (character === ')') {
      parentheses = Math.max(0, parentheses - 1)
    } else if (character === '[') {
      brackets += 1
    } else if (character === ']') {
      brackets = Math.max(0, brackets - 1)
    } else if (character === ',' && parentheses === 0 && brackets === 0) {
      selectors.push(selectorList.slice(start, cursor))
      start = cursor + 1
    }
  }

  selectors.push(selectorList.slice(start))

  return selectors
}

function scopeSelector(selector) {
  let value = selector.trim()

  if (!value || value.startsWith(DOCUMENT_ROOT_SELECTOR)) {
    return value
  }

  let usesDocumentRoot = false

  while (true) {
    const match = value.match(DOCUMENT_ROOT_ALIASES)

    if (!match) {
      break
    }

    usesDocumentRoot = true
    value = value.slice(match[0].length)

    if (/^\s+(?:html|body|:root|\.pdf-document|\.studio-document)(?=$|[\s.#:[>+~])/i.test(value)) {
      value = value.trimStart()
    }
  }

  if (usesDocumentRoot) {
    return `${DOCUMENT_ROOT_SELECTOR}${value}`
  }

  return `${DOCUMENT_ROOT_SELECTOR} ${value}`
}

function scopeSelectorList(selectorList) {
  return splitSelectorList(selectorList)
    .map(scopeSelector)
    .filter(Boolean)
    .join(', ')
}

function atRuleName(prelude) {
  return prelude.trim().match(/^@([\w-]+)/)?.[1]?.toLowerCase() || ''
}

function isPrintMediaRule(prelude) {
  return atRuleName(prelude) === 'media' && PRINT_MEDIA_PATTERN.test(prelude.replace(/^@media/i, ''))
}

/**
 * Scopes document CSS to the in-app paper preview while preserving nested CSS.
 * Print media rules are flattened because the preview represents printed output.
 */
export function scopeReportStudioPreviewCss(css) {
  const source = String(css || '')
  let output = ''
  let cursor = 0

  while (cursor < source.length) {
    const whitespaceStart = cursor

    while (cursor < source.length && /\s/.test(source[cursor])) {
      cursor += 1
    }

    output += source.slice(whitespaceStart, cursor)

    if (cursor >= source.length) {
      break
    }

    if (source[cursor] === '/' && source[cursor + 1] === '*') {
      const commentEnd = consumeComment(source, cursor)
      output += source.slice(cursor, commentEnd)
      cursor = commentEnd
      continue
    }

    const boundary = findRuleBoundary(source, cursor)
    const prelude = source.slice(cursor, boundary.index).trim()

    if (!prelude) {
      cursor = Math.min(source.length, boundary.index + 1)
      continue
    }

    if (boundary.character === ';') {
      output += `${prelude};`
      cursor = boundary.index + 1
      continue
    }

    if (boundary.character !== '{') {
      output += source.slice(cursor)
      break
    }

    const closingBrace = findMatchingBrace(source, boundary.index)
    const body = source.slice(boundary.index + 1, closingBrace)

    if (prelude.startsWith('@')) {
      const name = atRuleName(prelude)

      if (NESTED_RULE_AT_RULES.has(name)) {
        const scopedBody = scopeReportStudioPreviewCss(body)
        output += isPrintMediaRule(prelude) ? scopedBody : `${prelude}{${scopedBody}}`
      } else {
        output += `${prelude}{${body}}`
      }
    } else {
      output += `${scopeSelectorList(prelude)}{${body}}`
    }

    cursor = closingBrace + 1
  }

  return output
}
