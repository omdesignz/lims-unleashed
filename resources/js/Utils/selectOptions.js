function normalizeResults(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (Array.isArray(payload?.data)) {
    return payload.data
  }

  return []
}

function buildUrl(path, query, params = {}) {
  const searchParams = new URLSearchParams()
  searchParams.set('q', query ?? '')

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      searchParams.set(key, value)
    }
  })

  return `${path}?${searchParams.toString()}`
}

export async function loadSelectOptions(path, query, setOptions, mapper, params = {}) {
  try {
    const response = await fetch(buildUrl(path, query, params), {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
      setOptions([])

      return []
    }

    const options = normalizeResults(await response.json())
      .map(mapper)
      .filter(option => option?.value !== undefined && option?.label)

    setOptions(options)

    return options
  } catch {
    setOptions([])

    return []
  }
}

export const optionMappers = {
  address: item => ({ value: item.id, label: item.address }),
  code: item => ({ value: item.id, label: item.code }),
  name: item => ({ value: item.id, label: item.name }),
  numberPlate: item => ({ value: item.id, label: item.number_plate }),
}
