<template>
  <Card>
    <template #title>Tareas</template>
    <template #content>
      <p class="text-sm text-slate-600 mb-4">
        Tareas vinculadas a ítems de backlog compartidos o del equipo.
      </p>
      <DataTable :value="filteredTasks" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay tareas que mostrar.</span>
        </template>
        <Column field="title" header="Título" />
        <Column header="Backlog" class="w-40">
          <template #body="{ data }">
            {{ backlogTitle(data) }}
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag :value="taskStatusLabel(data)" severity="secondary" />
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, watch } from 'vue'
import useActivityTasks from '@/composables/activityTasks'

const activityId = inject('activityId')
const teamId = inject('teamId')
const { tasks, isLoading, getTasks } = useActivityTasks()

const TASK_STATUS_LABELS = {
  0: 'Por hacer',
  1: 'En progreso',
  2: 'Hecha',
  3: 'Cancelada',
}

const filteredTasks = computed(() => {
  const tid = teamId?.value
  return (tasks.value || []).filter((t) => {
    const bi = t.backlog_item || t.backlogItem
    const bid = bi?.team_id ?? bi?.team?.id
    return bid == null || Number(bid) === Number(tid)
  })
})

function backlogTitle(row) {
  const bi = row.backlog_item || row.backlogItem
  return bi?.title || `#${row.backlog_item_id ?? '—'}`
}

function taskStatusLabel(row) {
  const s = row?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return '—'
  return TASK_STATUS_LABELS[v] ?? String(v)
}

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getTasks(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
