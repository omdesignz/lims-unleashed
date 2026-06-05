export const proposalTemplatePageSizeCatalog = {
  A4: { width: 210, height: 297 },
  Letter: { width: 216, height: 279 },
  Legal: { width: 216, height: 356 },
}

function numericSetting(value, fallback) {
  const number = Number(value)

  return Number.isFinite(number) ? number : fallback
}

function clamp(value, min, max) {
  return Math.min(max, Math.max(min, value))
}

export function proposalTemplatePreviewPageDimensions(exportSettings = {}) {
  const selectedSize = exportSettings.paper_size === 'custom'
    ? {
        width: numericSetting(exportSettings.custom_page_width, proposalTemplatePageSizeCatalog.A4.width),
        height: numericSetting(exportSettings.custom_page_height, proposalTemplatePageSizeCatalog.A4.height),
      }
    : (proposalTemplatePageSizeCatalog[exportSettings.paper_size] || proposalTemplatePageSizeCatalog.A4)

  if (exportSettings.orientation === 'L') {
    return {
      width: Math.max(selectedSize.width, selectedSize.height),
      height: Math.min(selectedSize.width, selectedSize.height),
    }
  }

  return {
    width: Math.min(selectedSize.width, selectedSize.height),
    height: Math.max(selectedSize.width, selectedSize.height),
  }
}

export function proposalTemplatePreviewPageFormatLabel(exportSettings = {}) {
  const dimensions = proposalTemplatePreviewPageDimensions(exportSettings)
  const paperSize = exportSettings.paper_size === 'custom'
    ? `${dimensions.width} × ${dimensions.height} mm`
    : String(exportSettings.paper_size || 'A4').toUpperCase()
  const orientation = exportSettings.orientation === 'L' ? 'Paisagem' : 'Retrato'

  return `${paperSize} · ${orientation}`
}

export function proposalTemplatePreviewMarginPercent(value, axisSize, fallback = 0) {
  const margin = numericSetting(value, fallback)

  if (!axisSize) {
    return '0%'
  }

  return `${clamp((margin / axisSize) * 100, 0, 45)}%`
}

export function proposalTemplatePreviewMarginStyle(exportSettings = {}, pageNumber = 1) {
  const dimensions = proposalTemplatePreviewPageDimensions(exportSettings)
  const topMargin = pageNumber === 1
    ? numericSetting(exportSettings.first_page_margin_top, exportSettings.margin_top)
    : numericSetting(exportSettings.margin_top, 22)

  return {
    top: proposalTemplatePreviewMarginPercent(topMargin, dimensions.height, 22),
    right: proposalTemplatePreviewMarginPercent(exportSettings.margin_right, dimensions.width, 14),
    bottom: proposalTemplatePreviewMarginPercent(exportSettings.margin_bottom, dimensions.height, 18),
    left: proposalTemplatePreviewMarginPercent(exportSettings.margin_left, dimensions.width, 14),
  }
}
