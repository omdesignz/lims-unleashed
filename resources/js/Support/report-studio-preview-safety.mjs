const controlCharacterPattern = /[\u0000-\u001f\u007f]/
const safeBase64ImageDataUrlPattern = /^data:image\/(?:png|jpe?g|gif|webp|avif|svg\+xml);base64,[a-z0-9+/=\s]+$/i

export function escapePreviewHtmlAttribute(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
}

export function escapePreviewHtmlText(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
}

export function safePreviewMediaUrl(value) {
  const source = String(value ?? '').trim()

  if (!source || controlCharacterPattern.test(source)) {
    return ''
  }

  if (safeBase64ImageDataUrlPattern.test(source)) {
    return source.replace(/\s+/g, '')
  }

  if (/^data:/i.test(source)) {
    return ''
  }

  try {
    const url = new URL(source, 'https://report-studio-preview.local')

    if (['http:', 'https:', 'blob:'].includes(url.protocol)) {
      return source
    }
  } catch {
    return ''
  }

  return ''
}

function escapePreviewCssString(value) {
  return String(value ?? '')
    .replace(/\\/g, '\\\\')
    .replace(/"/g, '\\"')
    .replace(/\n|\r|\f/g, '')
}

export function safePreviewCssUrl(value) {
  const mediaUrl = safePreviewMediaUrl(value)

  return mediaUrl ? `url("${escapePreviewCssString(mediaUrl)}")` : ''
}
