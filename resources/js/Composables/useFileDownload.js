import { ref } from 'vue'

export function useFileDownload() {
  const processing = ref(false)
  const error = ref('')

  async function download(url, options = {}) {
    if (processing.value) return false
    processing.value = true
    error.value = ''
    try {
      const response = await fetch(url, {
        ...options,
        headers: { ...options.headers, Accept: 'application/json' },
        credentials: 'same-origin',
      })
      if (!response.ok) {
        const body = await response.json().catch(() => ({}))
        error.value = Object.values(body.errors ?? {}).flat().join(' ') || 'Não foi possível exportar. Verifique a autorização e os filtros.'
        return false
      }
      const disposition = response.headers.get('Content-Disposition') ?? ''
      const filename = disposition.match(/filename="?([^";]+)"?/)?.[1]
      if (!filename || !disposition.toLowerCase().includes('attachment')) {
        error.value = 'A sessão ou o ficheiro já não está disponível. Actualize a página e tente novamente.'
        return false
      }
      const blobUrl = URL.createObjectURL(await response.blob())
      const anchor = document.createElement('a')
      try {
        anchor.href = blobUrl
        anchor.download = filename.replace(/[\\/]/g, '_')
        document.body.appendChild(anchor)
        anchor.click()
      } finally {
        anchor.remove()
        setTimeout(() => URL.revokeObjectURL(blobUrl), 1000)
      }
      return true
    } catch {
      error.value = 'Falha de ligação. Os filtros foram mantidos; tente novamente.'
      return false
    } finally {
      processing.value = false
    }
  }

  return { download, processing, error }
}
