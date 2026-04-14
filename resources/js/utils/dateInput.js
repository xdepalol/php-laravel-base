import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'

dayjs.extend(utc)

/**
 * Normaliza valores para <input type="date"> (YYYY-MM-DD).
 * Para fechas solo-día (Y-m-d) devuelve el mismo día.
 * Para ISO con hora usa la **fecha en UTC** coherente con almacenamiento UTC en servidor.
 */
export function toDateInputValue(value) {
  if (value == null || value === '') return null
  const s = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s
  return toUtcDateInputValue(s)
}

/**
 * Instant UTC (ISO) → componente fecha del calendario en UTC (YYYY-MM-DD).
 * Adecuado para `due_date` y otros `datetime` guardados en UTC.
 */
export function toUtcDateInputValue(value) {
  if (value == null || value === '') return null
  const s = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s
  const d = dayjs.utc(s)
  if (!d.isValid()) return null
  return d.format('YYYY-MM-DD')
}
