function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max)
}

export function imagePositionCoordinates(position = 'center center') {
  const value = String(position || '').trim().toLowerCase()
  const tokens = value.split(/\s+/).filter(Boolean)
  const percentages = tokens
    .filter((token) => /^(?:100|[1-9]?\d)(?:\.\d{1,2})?%$/.test(token))
    .map((percentage) => Number(percentage.replace('%', '')))

  if (tokens.length === percentages.length && percentages.length >= 2) {
    return {
      x: clamp(Math.round(percentages[0]), 0, 100),
      y: clamp(Math.round(percentages[1]), 0, 100),
    }
  }

  let x = 50
  let y = 50

  tokens.forEach((token) => {
    if (token === 'left') {
      x = 0
    }

    if (token === 'right') {
      x = 100
    }

    if (token === 'top') {
      y = 0
    }

    if (token === 'bottom') {
      y = 100
    }
  })

  return { x, y }
}

export function imagePositionStringFromCoordinates(x, y) {
  return `${clamp(Math.round(Number(x) || 0), 0, 100)}% ${clamp(Math.round(Number(y) || 0), 0, 100)}%`
}
