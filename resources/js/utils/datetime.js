/**
 * Fechas en API: instantes en UTC (ISO 8601 con Z). Fechas “solo día” (Y-m-d) son
 * días de calendario sin zona (actividades). Este módulo centraliza formato en
 * hora local del navegador vía dayjs.
 */
import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import 'dayjs/locale/es'

dayjs.extend(utc)
dayjs.locale('es')

/**
 * @param {string|Date|null|undefined} value
 * @param {'date'|'datetime'|'time'} [variant]
 * @returns {string|null}
 */
export function formatUtcIso(value, variant = 'datetime') {
  if (value == null || value === '') return null
  const s = String(value).trim()

  // Solo fecha calendario (p. ej. start_date / end_date de actividad): sin conversión UTC.
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
    const cal = dayjs(s, 'YYYY-MM-DD', true)
    if (!cal.isValid()) return null
    if (variant === 'time') return null
    return cal.format('DD/MM/YYYY')
  }

  const d = dayjs.utc(s)
  if (!d.isValid()) return null
  const local = d.local()

  if (variant === 'date') return local.format('DD/MM/YYYY')
  if (variant === 'time') return local.format('HH:mm')
  return local.format('DD/MM/YYYY HH:mm')
}

/**
 * Normaliza `due_date` desde la API a string ISO UTC (conserva hora; Y-m-d → medianoche UTC).
 */
export function normalizeDueDateFromApi(v) {
  if (v == null || v === '') return null
  const s = String(v).trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return `${s}T00:00:00.000Z`
  const d = dayjs.utc(s)
  return d.isValid() ? d.toISOString() : null
}

/** ISO UTC → `Date` para PrimeVue DatePicker (instante absoluto). */
export function utcIsoToJsDate(iso) {
  if (!iso) return null
  const d = new Date(String(iso))
  return Number.isNaN(d.getTime()) ? null : d
}

/** Valor del DatePicker → ISO UTC para el payload. */
export function jsDateToUtcIso(date) {
  if (!date || !(date instanceof Date) || Number.isNaN(date.getTime())) return null
  return date.toISOString()
}
