/**
 * Sort deliverables: due_date ascending (null/invalid last), then id ascending.
 * Works with API rows ({ id, due_date }) and normalized composable objects.
 */
function parseDueTime(v) {
  if (v == null || v === '') return null
  const t = new Date(v).getTime()
  return Number.isFinite(t) ? t : null
}

function compareDeliverablesByDueDateThenId(a, b) {
  const ta = parseDueTime(a?.due_date)
  const tb = parseDueTime(b?.due_date)
  const ida = Number(a?.id) || 0
  const idb = Number(b?.id) || 0
  if (ta == null && tb == null) return ida - idb
  if (ta == null) return 1
  if (tb == null) return -1
  if (ta !== tb) return ta - tb
  return ida - idb
}

export function sortDeliverablesByDueDateThenId(items) {
  if (!Array.isArray(items)) return []
  return [...items].sort(compareDeliverablesByDueDateThenId)
}
