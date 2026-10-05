/**
 * Where the body of a document breaks across pages on the Studio canvas.
 *
 * The canvas lays the body out once, as one long column, and shows a window of
 * it on each page. These helpers find where each window starts: at the edge of
 * a row, a paragraph or a block that must stay whole, never through a line.
 */

const atomicSelector = [
  'tr', 'p', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
  'img', 'svg', 'hr', 'pre', 'blockquote', 'figure',
  '.doc-keep', '.doc-end', '.doc-auth', '.doc-section-title', '.studio-avoid-break',
].join(',')

const headingSelector = 'h1,h2,h3,h4,h5,h6,.doc-section-title'

/**
 * The pieces of a laid-out body that a page break may fall between, in reading
 * order, measured from the top of the body.
 *
 * @param {Element} root
 * @returns {Array<{top: number, bottom: number, heading: boolean}>}
 */
export function collectFlowBlocks(root) {
  if (!root) {
    return []
  }

  const origin = root.getBoundingClientRect().top
  const blocks = []

  const visit = (element) => {
    const rect = element.getBoundingClientRect()

    if (rect.height <= 0) {
      return
    }

    if (element.matches(atomicSelector) || element.children.length === 0) {
      blocks.push({
        top: rect.top - origin,
        bottom: rect.bottom - origin,
        heading: element.matches(headingSelector),
      })

      return
    }

    Array.from(element.children).forEach(visit)
  }

  Array.from(root.children).forEach(visit)

  return blocks
}

/**
 * Where each page of a body starts, measured from the top of the body.
 *
 * A block that does not fit in what is left of a page starts the next one; a
 * heading is never left alone at the foot of a page; a block taller than a
 * whole page is the only thing cut through.
 *
 * @param {Array<{top: number, bottom: number, heading?: boolean}>} blocks
 * @param {number} totalHeight  height of the whole body
 * @param {number} firstHeight  room for the body on its first page
 * @param {number} nextHeight   room for the body on each page after it
 * @returns {number[]} page starts; always begins with 0
 */
export function paginateFlow(blocks, totalHeight, firstHeight, nextHeight) {
  const starts = [0]
  const room = (pageIndex) => Math.max(pageIndex === 0 ? firstHeight : nextHeight, 0)

  if (!(totalHeight > 0) || room(0) < 24 || room(1) < 24) {
    return starts
  }

  // Half a pixel of slack: sub-pixel layout must not push a block that fits onto a new page.
  const fits = (bottom) => bottom - starts[starts.length - 1] <= room(starts.length - 1) + 0.5

  blocks.forEach((block, index) => {
    if (fits(block.bottom)) {
      return
    }

    let breakAt = block.top
    const previous = blocks[index - 1]

    // Keep a heading with what it introduces.
    if (previous?.heading && previous.top > starts[starts.length - 1] && block.bottom - previous.top <= room(starts.length) + 0.5) {
      breakAt = previous.top
    }

    if (breakAt > starts[starts.length - 1]) {
      starts.push(breakAt)
    }

    // Taller than a page: nothing to break between, so it is cut at the page edge.
    while (!fits(block.bottom)) {
      starts.push(starts[starts.length - 1] + room(starts.length - 1))
    }
  })

  // Nothing to break between (bare text): the body is cut at each page edge.
  if (blocks.length === 0) {
    while (totalHeight - starts[starts.length - 1] > room(starts.length - 1) + 0.5) {
      starts.push(starts[starts.length - 1] + room(starts.length - 1))
    }
  }

  return starts
}

/**
 * The pages a document shows on the canvas: one or more for each part of the
 * body between explicit page breaks.
 *
 * @param {string[]} segments  body HTML between explicit page breaks
 * @param {number[][]} breaks  page starts for each segment, from `paginateFlow`
 * @returns {Array<{content: string, segmentNumber: number, flowIndex: number, offset: number, limit: number|null}>}
 */
export function flowPages(segments, breaks = []) {
  return segments.flatMap((content, segmentIndex) => {
    const starts = Array.isArray(breaks[segmentIndex]) && breaks[segmentIndex].length ? breaks[segmentIndex] : [0]

    return starts.map((offset, flowIndex) => ({
      content,
      segmentNumber: segmentIndex + 1,
      flowIndex,
      offset,
      limit: flowIndex < starts.length - 1 ? starts[flowIndex + 1] - offset : null,
    }))
  })
}
