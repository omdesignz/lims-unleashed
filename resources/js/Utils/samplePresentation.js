export const sampleStatusLabels = { POR_INICIAR: 'Por iniciar', EN_PROGRESO: 'Em análise', EN_PAUSA: 'Em espera', COMPLETADO: 'Concluída', CANCELADO: 'Cancelada' }
export const sampleStatusTones = { POR_INICIAR: 'received', EN_PROGRESO: 'analysis', EN_PAUSA: 'review', COMPLETADO: 'complete', CANCELADO: 'hold' }
export const sampleTypeLabels = {
  ROTINA: 'Rotina', MATERIA_PRIMA: 'Matéria-prima', RAW_MATERIAL: 'Matéria-prima',
  PRODUTO_ACABADO: 'Produto acabado', ESTABILIDADE: 'Estabilidade', CONTRAPROVA: 'Contraprova',
  COUNTER_ANALYSIS: 'Contra-análise', INTERLABORATORIAL: 'Interlaboratorial', RETENCAO: 'Retenção', AGUA: 'Água',
}

export function sampleDate(value, includeTime = false) {
  if (!value) return 'Por registar'
  const date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(value) ? `${value}T12:00:00` : value)
  if (Number.isNaN(date.getTime())) return 'Data inválida'
  return new Intl.DateTimeFormat('pt-AO', {
    day: '2-digit', month: 'short', year: 'numeric',
    ...(includeTime ? { hour: '2-digit', minute: '2-digit' } : {}),
  }).format(date)
}

export function sampleText(value, fallback = 'Não registado') {
  if (Array.isArray(value)) return value.filter(item => typeof item === 'string' && item.trim()).join(', ') || fallback
  if (typeof value === 'number') return String(value)
  return typeof value === 'string' && value.trim() ? value : fallback
}

export function approvedResultCounts(analyses = []) {
  return analyses.reduce((counts, analysis) => ({
    approved: counts.approved + Number(analysis.results_summary?.approved || 0),
    total: counts.total + Number(analysis.results_summary?.total || 0),
  }), { approved: 0, total: 0 })
}

export const finalDecisionLabels = {
  released: 'Liberada para uso', rejected: 'Rejeitada', quarantined: 'Em quarentena',
  investigation_required: 'Investigação requerida', trend_recorded: 'Registada para tendência',
}
