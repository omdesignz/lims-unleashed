import { Boxes, Briefcase, FileBadge, FlaskConical, House, Microscope, Settings2, ShieldCheck } from '@lucide/vue'

/**
 * A glyph and a short name for each area. The phone bar is too narrow for the full
 * area names, so it pairs the glyph with the short name; the full name stays the
 * accessible label.
 */
export const areaGlyphs = {
    home: { icon: House, short: 'Início' },
    samples: { icon: FlaskConical, short: 'Amostras' },
    analysis: { icon: Microscope, short: 'Análise' },
    certificates: { icon: FileBadge, short: 'Certif.' },
    commercial: { icon: Briefcase, short: 'Comercial' },
    inventory: { icon: Boxes, short: 'Inventário' },
    quality: { icon: ShieldCheck, short: 'Qualidade' },
    admin: { icon: Settings2, short: 'Admin' },
}

/**
 * The phone bar holds a fixed number of areas. The area the person is in always
 * keeps a place, taking the last slot when it is not among the first areas.
 *
 * @param {Array<{ key: string }>} areas
 * @param {string|null} activeKey
 * @param {number} slots
 * @returns {Array<{ key: string }>}
 */
export function bottomBarAreas(areas, activeKey, slots = 4) {
    const leading = areas.slice(0, slots)

    if (!activeKey || leading.some((area) => area.key === activeKey)) {
        return leading
    }

    const active = areas.find((area) => area.key === activeKey)

    return active ? [...leading.slice(0, slots - 1), active] : leading
}
