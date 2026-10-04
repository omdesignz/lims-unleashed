/**
 * How far apart two chart colours look, as Euclidean distance in OKLab ×100,
 * for normal vision and under simulated protanopia and deuteranopia
 * (Machado, Oliveira & Fernandes 2009, severity 1.0). These are the measures
 * the categorical palette in `Support/charts.js` was validated with; the chart
 * builder uses them to warn when chosen colours stop being distinguishable.
 */
const MACHADO = {
    protan: [[0.152286, 1.052583, -0.204868], [0.114503, 0.786281, 0.099216], [-0.003882, -0.048116, 1.051998]],
    deutan: [[0.367322, 0.860646, -0.227968], [0.280085, 0.672501, 0.047413], [-0.01182, 0.04294, 0.968881]],
}

/** Neighbouring series closer than this are hard to tell apart even with full colour vision. */
export const NORMAL_VISION_FLOOR = 15

/** Neighbouring series closer than this collapse for colour-blind readers. */
export const CVD_FLOOR = 6

const toLinear = (hex) => [0, 2, 4]
    .map((offset) => parseInt(hex.replace('#', '').slice(offset, offset + 2), 16) / 255)
    .map((channel) => (channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4))

function oklab([r, g, b]) {
    const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b)
    const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b)
    const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b)

    return [
        0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s,
        1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s,
        0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s,
    ]
}

function simulate(linear, kind) {
    const matrix = MACHADO[kind]
    const clamp = (value) => Math.max(0, Math.min(1, value))

    return matrix.map((row) => clamp(row[0] * linear[0] + row[1] * linear[1] + row[2] * linear[2]))
}

const isHex = (value) => typeof value === 'string' && /^#[0-9a-f]{6}$/i.test(value)

/**
 * @param {string} first hex colour
 * @param {string} second hex colour
 * @param {'protan'|'deutan'|null} kind simulated vision, or null for normal vision
 */
export function colourDistance(first, second, kind = null) {
    const a = kind ? simulate(toLinear(first), kind) : toLinear(first)
    const b = kind ? simulate(toLinear(second), kind) : toLinear(second)
    const [l1, a1, b1] = oklab(a)
    const [l2, a2, b2] = oklab(b)

    return 100 * Math.hypot(l1 - l2, a1 - a2, b1 - b2)
}

/**
 * Pairs of neighbouring series whose colours readers would confuse.
 *
 * @param {Array<{ name: string, color: string }>} series in drawing order
 * @returns {Array<{ first: string, second: string, reason: 'normal'|'cvd' }>}
 */
export function confusablePairs(series) {
    const pairs = []

    for (let index = 1; index < series.length; index += 1) {
        const first = series[index - 1]
        const second = series[index]

        if (!isHex(first?.color) || !isHex(second?.color)) {
            continue
        }

        if (colourDistance(first.color, second.color) < NORMAL_VISION_FLOOR) {
            pairs.push({ first: first.name, second: second.name, reason: 'normal' })
        } else if (Math.min(colourDistance(first.color, second.color, 'protan'), colourDistance(first.color, second.color, 'deutan')) < CVD_FLOOR) {
            pairs.push({ first: first.name, second: second.name, reason: 'cvd' })
        }
    }

    return pairs
}
