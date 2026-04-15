<template>
  <Card class="shadow-sm border border-slate-200/80 h-full">
    <template #title>
      <div class="flex items-start gap-2">
        <span
          v-if="dragHandleClass"
          :class="dragHandleClass"
          class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 mt-0.5 shrink-0"
          title="Arrastrar"
        >
          <i class="pi pi-bars" aria-hidden="true" />
        </span>
        <div class="min-w-0 flex-1">
          <span class="text-sm font-semibold text-slate-900 leading-snug break-words">{{ row.title || '—' }}</span>
          <div class="flex flex-wrap gap-1.5 mt-2">
            <Tag :value="scopeLabel" severity="secondary" class="text-xs" />
            <Tag :value="priorityTag" severity="info" class="text-xs" />
            <Tag :value="statusLabel" severity="secondary" class="text-xs" />
            <Tag v-if="row.points != null" :value="`${row.points} pt`" severity="secondary" class="text-xs" />
          </div>
        </div>
      </div>
    </template>
    <template #content>
      <p v-if="snippet" class="text-xs text-slate-600 line-clamp-4">{{ snippet }}</p>
      <p v-else class="text-xs text-slate-400">Sin descripción.</p>
      <div v-if="isTeamRow && canEditTeamBacklog" class="flex justify-end gap-1 mt-3 pt-2 border-t border-slate-100">
        <Button
          v-tooltip.top="'Editar'"
          icon="pi pi-pencil"
          text
          rounded
          severity="secondary"
          size="small"
          @click="$emit('edit')"
        />
        <Button
          v-tooltip.top="'Eliminar'"
          icon="pi pi-trash"
          text
          rounded
          severity="danger"
          size="small"
          @click="$emit('delete')"
        />
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  row: { type: Object, required: true },
  scopeLabel: { type: String, required: true },
  isTeamRow: { type: Boolean, default: false },
  snippet: { type: String, default: '' },
  canEditTeamBacklog: { type: Boolean, default: false },
  dragHandleClass: { type: String, default: '' },
})

defineEmits(['edit', 'delete'])

const BACKLOG_STATUS_LABELS = {
  0: 'Backlog',
  1: 'En curso',
  2: 'Hecho',
  3: 'Cancelado',
}

const priorityVal = computed(() => {
  const p = props.row?.priority
  const v = typeof p === 'object' && p !== null ? p.value : p
  return v ?? null
})

const priorityTag = computed(() => {
  const v = priorityVal.value
  return v == null ? '—' : `P${v}`
})

const statusLabel = computed(() => {
  const s = props.row?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return '—'
  return BACKLOG_STATUS_LABELS[v] ?? String(v)
})
</script>
