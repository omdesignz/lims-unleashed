/**
 * The column name a stacked cell shows as its label. A header that only holds
 * screen-reader text names an action column, which needs no visible label.
 *
 * @param {Element|undefined} header
 * @returns {{ label: string, action: boolean }}
 */
export function headerLabel(header) {
    if (!header) {
        return { label: '', action: true }
    }

    const text = header.textContent.replace(/\s+/g, ' ').trim()
    const hidden = [...header.querySelectorAll('.sr-only')].map((node) => node.textContent.replace(/\s+/g, ' ').trim()).join(' ').trim()

    return { label: hidden && hidden === text ? '' : text, action: !text || (Boolean(hidden) && hidden === text) }
}

/**
 * Gives every body cell its column name so the row can stack on phones. Returns
 * false, leaving the table untouched, when the table stacks itself or the header
 * is not a single plain row (grouped or spanning headers describe a matrix that
 * must keep its grid).
 *
 * @param {HTMLTableElement|null} table
 * @returns {boolean}
 */
export function labelStackedCells(table) {
    const headRows = table?.tHead?.rows ?? []

    // A table that lays out its own stacked rows (the records register) is left alone.
    if (table?.classList?.contains('pl-stack-table') || headRows.length !== 1) {
        return false
    }

    const headers = [...headRows[0].cells]

    if (!headers.length || headers.some((cell) => cell.colSpan > 1 || cell.rowSpan > 1)) {
        return false
    }

    const columns = headers.map(headerLabel)
    // The record's title is its first named column (a leading selection box is an action).
    const titleIndex = Math.max(0, columns.findIndex((column) => !column.action))

    for (const body of table.tBodies) {
        for (const row of body.rows) {
            const cells = [...row.cells]

            // A row spanning the whole table (an empty state, a group heading) is a note, not a record.
            if (cells.length === 1 && cells[0].colSpan > 1) {
                cells[0].dataset.cell = 'note'
                continue
            }

            cells.forEach((cell, index) => {
                const column = columns[index] ?? { label: '', action: true }

                cell.dataset.label = column.label
                cell.dataset.cell = index === titleIndex ? 'title' : column.action ? 'action' : 'field'
            })
        }
    }

    return true
}
