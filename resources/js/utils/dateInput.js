/**
 * Normaliza fechas ISO u otros formatos para usarlas en <input type="date"> (YYYY-MM-DD).
 */
export function toDateInputValue(value) {
  if (value == null || value === '') return null
  const s = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s
  const head = s.match(/^(\d{4}-\d{2}-\d{2})/)
  if (head) return head[1]
  const d = new Date(s)
  if (Number.isNaN(d.getTime())) return null
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
