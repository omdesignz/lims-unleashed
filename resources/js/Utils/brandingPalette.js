function clamp(value, min = 0, max = 255) {
  return Math.min(max, Math.max(min, Math.round(value)))
}

function hexToRgb(hex, fallback = '#143d37') {
  const normalized = String(hex || fallback).replace('#', '').trim()
  const safeHex = normalized.length === 3
    ? normalized.split('').map((char) => char + char).join('')
    : normalized

  if (!/^[0-9a-fA-F]{6}$/.test(safeHex)) {
    return hexToRgb(fallback, fallback)
  }

  return {
    r: parseInt(safeHex.slice(0, 2), 16),
    g: parseInt(safeHex.slice(2, 4), 16),
    b: parseInt(safeHex.slice(4, 6), 16),
  }
}

function rgbToString({ r, g, b }) {
  return `${clamp(r)} ${clamp(g)} ${clamp(b)}`
}

function mixRgb(color, target, ratio) {
  return {
    r: color.r + (target.r - color.r) * ratio,
    g: color.g + (target.g - color.g) * ratio,
    b: color.b + (target.b - color.b) * ratio,
  }
}

function darken(color, ratio) {
  return mixRgb(color, { r: 0, g: 0, b: 0 }, ratio)
}

function lighten(color, ratio) {
  return mixRgb(color, { r: 255, g: 255, b: 255 }, ratio)
}

export function buildBrandingCssVariables(branding = {}) {
  const primaryColor = branding.primary_color || branding.app_primary_color || '#143d37'
  const secondaryColor = branding.secondary_color || branding.app_secondary_color || '#07110f'
  const accentColor = branding.accent_color || branding.app_accent_color || '#d9b05f'

  const primary = hexToRgb(primaryColor, '#143d37')
  const secondary = hexToRgb(secondaryColor, '#07110f')
  const accent = hexToRgb(accentColor, '#d9b05f')

  const primaryPalette = {
    50: lighten(primary, 0.92),
    100: lighten(primary, 0.84),
    200: lighten(primary, 0.72),
    300: lighten(primary, 0.56),
    400: lighten(primary, 0.24),
    500: primary,
    600: darken(primary, 0.08),
    700: darken(primary, 0.18),
    800: mixRgb(primary, secondary, 0.55),
    900: secondary,
    950: darken(secondary, 0.28),
  }

  const accentPalette = {
    50: lighten(accent, 0.92),
    100: lighten(accent, 0.82),
    200: lighten(accent, 0.66),
    300: lighten(accent, 0.44),
    400: lighten(accent, 0.16),
    500: accent,
    600: darken(accent, 0.16),
  }

  return {
    '--brand-primary': primaryColor,
    '--brand-secondary': secondaryColor,
    '--brand-accent': accentColor,
    '--primary-50-rgb': rgbToString(primaryPalette[50]),
    '--primary-100-rgb': rgbToString(primaryPalette[100]),
    '--primary-200-rgb': rgbToString(primaryPalette[200]),
    '--primary-300-rgb': rgbToString(primaryPalette[300]),
    '--primary-400-rgb': rgbToString(primaryPalette[400]),
    '--primary-500-rgb': rgbToString(primaryPalette[500]),
    '--primary-600-rgb': rgbToString(primaryPalette[600]),
    '--primary-700-rgb': rgbToString(primaryPalette[700]),
    '--primary-800-rgb': rgbToString(primaryPalette[800]),
    '--primary-900-rgb': rgbToString(primaryPalette[900]),
    '--primary-950-rgb': rgbToString(primaryPalette[950]),
    '--accent-50-rgb': rgbToString(accentPalette[50]),
    '--accent-100-rgb': rgbToString(accentPalette[100]),
    '--accent-200-rgb': rgbToString(accentPalette[200]),
    '--accent-300-rgb': rgbToString(accentPalette[300]),
    '--accent-400-rgb': rgbToString(accentPalette[400]),
    '--accent-500-rgb': rgbToString(accentPalette[500]),
    '--accent-600-rgb': rgbToString(accentPalette[600]),
  }
}
