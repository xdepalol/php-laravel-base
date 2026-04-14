import { formatUtcIso } from '@/utils/datetime'

const DEFAULT_EMPTY = '—'

function normalizeInput(value) {
  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    return value.toISOString()
  }
  return value
}

/** Actualiza el texto del elemento usando formatUtcIso (UTC API → hora local). */
function apply(el, binding) {
  const raw = binding.value
  let variant = 'datetime'
  if (['date', 'time', 'datetime'].includes(binding.arg)) {
    variant = binding.arg
  }

  let value = raw
  let empty = DEFAULT_EMPTY
  if (raw != null && typeof raw === 'object' && !Array.isArray(raw) && !(raw instanceof Date)) {
    value = raw.value
    if (raw.variant && ['date', 'time', 'datetime'].includes(raw.variant)) {
      variant = raw.variant
    }
    if (raw.empty != null) empty = raw.empty
  }

  value = normalizeInput(value)
  const text = formatUtcIso(value, variant) ?? empty
  el.textContent = text
}

/** v-local-date, v-local-date:date, v-local-date:time, v-local-date:datetime */
export default {
  mounted: apply,
  updated: apply,
}
