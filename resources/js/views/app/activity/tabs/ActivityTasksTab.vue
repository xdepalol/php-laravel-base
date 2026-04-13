<template>
  <Card>
    <template #title>Tareas</template>
    <template #content>
      <DataTable :value="tasks" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay tareas.</span>
        </template>
        <Column field="title" header="Título" />
        <Column field="backlog_item_id" header="Ítem backlog" class="w-32" />
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag :value="taskStatusLabel(data)" severity="secondary" />
          </template>
        </Column>
        <Column field="position" header="Orden" class="w-24" />
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { inject, watch } from 'vue'
import useActivityTasks from '@/composables/activityTasks'

const TASK_STATUS_LABELS = {
  0: 'Por hacer',
  1: 'En progreso',
  2: 'Hecha',
  3: 'Cancelada',
}

const activityId = inject('activityId')
const { tasks, isLoading, getTasks } = useActivityTasks()

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
