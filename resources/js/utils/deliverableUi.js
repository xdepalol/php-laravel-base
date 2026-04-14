/**
 * Etiqueta corta para cabeceras de matriz (entregables).
 * Prioriza `short_code` (único por actividad); si falta, id + título truncado.
 */
export function deliverableMatrixHeaderLabel(d) {
  if (!d?.id) return '—'
  const code = (d.short_code || '').trim()
  if (code) return code
  const title = (d.title || '').trim()
  const short =
    title.length > 14 ? `${title.slice(0, 12)}…` : title || `Entregable`
  return `#${d.id} · ${short}`
}
