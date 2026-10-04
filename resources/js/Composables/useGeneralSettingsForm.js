export function generalSettingsFormData(settings, revision) {
  return { ...settings, app_private_key: '', settings_revision: revision }
}

export function generalSettingsPayload(data) {
  const payload = { ...data }
  if (typeof payload.app_private_key !== 'string' || !payload.app_private_key.trim()) {
    delete payload.app_private_key
  }
  return payload
}
