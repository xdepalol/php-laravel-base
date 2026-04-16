<template>
  <div class="space-y-3">
    <p v-if="capturedLabel" class="text-xs text-slate-500">
      Capturado: <time :datetime="snapshot?.captured_at">{{ capturedLabel }}</time>
    </p>
    <div class="grid gap-3 md:grid-cols-3">
      <div
        v-for="col in normalizedColumns"
        :key="col.key"
        :class="col.panelClass"
      >
        <h3
          class="text-xs font-semibold uppercase tracking-wide mb-2"
          :class="col.headingClass"
        >
          {{ col.title }} ({{ col.cards.length }})
        </h3>
        <ul class="kanban-snapshot-list space-y-2 list-none p-0 m-0 min-h-[3rem]">
          <li
            v-for="(card, idx) in col.cards"
            :key="cardKey(col.key, card, idx)"
            class="rounded-md border border-white bg-white px-2 py-2 shadow-sm text-sm text-slate-800"
          >
            <p class="font-medium break-words">{{ card.title || '—' }}</p>
            <p v-if="assigneeLine(card)" class="text-xs text-slate-500 mt-1">
              {{ assigneeLine(card) }}
            </p>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  /** JSON `kanban_snapshot` de `phase_teams` (p. ej. versión 1 del builder PHP). */
  snapshot: { type: Object, required: true },
})

const COL_STYLES = {
  todo: {
    panelClass:
      'rounded-lg border border-amber-200/90 bg-amber-50/85 p-3 min-h-[8rem] shadow-sm',
    headingClass: 'text-amber-900/85',
  },
  doing: {
    panelClass:
      'rounded-lg border border-violet-200/90 bg-violet-50/80 p-3 min-h-[8rem] shadow-sm',
    headingClass: 'text-violet-900/85',
  },
  done: {
    panelClass:
      'rounded-lg border border-emerald-200/90 bg-emerald-50/80 p-3 min-h-[8rem] shadow-sm',
    headingClass: 'text-emerald-900/85',
  },
}

const normalizedColumns = computed(() => {
  const raw = props.snapshot?.columns
  if (!Array.isArray(raw)) return []
  return raw.map((c) => {
    const key = String(c?.key ?? '')
    const st = COL_STYLES[key] || {
      panelClass: 'rounded-lg border border-slate-200 bg-slate-50/80 p-3 min-h-[8rem] shadow-sm',
      headingClass: 'text-slate-800',
    }
    const cards = Array.isArray(c?.cards) ? c.cards : []
    return {
      key: key || 'col',
      title: c?.title || key || 'Columna',
      cards,
      panelClass: st.panelClass,
      headingClass: st.headingClass,
    }
  })
})

const capturedLabel = computed(() => {
  const raw = props.snapshot?.captured_at
  if (raw == null || raw === '') return ''
  try {
    const d = new Date(String(raw))
    if (Number.isNaN(d.getTime())) return String(raw)
    return d.toLocaleString(undefined, {
      dateStyle: 'medium',
      timeStyle: 'short',
    })
  } catch {
    return String(raw)
  }
})

function assigneeLine(card) {
  const a = card?.assignee
  if (!a || typeof a !== 'object') return ''
  return a.display_name ? String(a.display_name) : ''
}

function cardKey(colKey, card, idx) {
  const r = card?.ref
  if (r && (r.phase_task_id != null || r.task_id != null)) {
    return `${colKey}-${r.phase_task_id ?? ''}-${r.task_id ?? ''}`
  }
  return `${colKey}-${idx}`
}
</script>
