export const studioUploadAssetKinds = Object.freeze([
  'uploaded_asset',
  'uploaded_background',
  'uploaded_chart',
  'uploaded_image',
  'uploaded_signature',
  'uploaded_stamp',
])

export function uploadedStudioAssetKind(target = {}) {
  const scope = String(target?.scope || '')
  const field = String(target?.field || '')
  const blockKind = String(target?.blockKind || '')

  if (scope === 'document-background' || field === 'background_image') {
    return 'uploaded_background'
  }

  if (scope === 'new-canvas-block') {
    if (blockKind === 'signature') {
      return 'uploaded_signature'
    }

    if (blockKind === 'stamp') {
      return 'uploaded_stamp'
    }

    if (blockKind === 'chart_snapshot') {
      return 'uploaded_chart'
    }

    return 'uploaded_image'
  }

  if (field === 'signature_image') {
    return 'uploaded_signature'
  }

  if (field === 'chart_image_url') {
    return 'uploaded_chart'
  }

  if (scope === 'asset-url') {
    return 'uploaded_asset'
  }

  return 'uploaded_image'
}
