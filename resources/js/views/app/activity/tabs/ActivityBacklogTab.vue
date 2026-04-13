<template>
  <Card>
    <template #title>Backlog</template>
    <template #content>
      <DataTable :value="backlogItems" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay ítems en el backlog.</span>
        </template>
        <Column field="title" header="Título" />
        <Column field="team_id" header="Equipo" class="w-24">
          <template #body="{ data }">
            {{ data.team_id ?? '—' }}
          </template>
        </Column>
        <Column header="Prioridad" class="w-28">
          <template #body="{ data }">
            {{ backlogPriorityLabel(data.priority) }}
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag :value="backlogStatusLabel(data)" severity="secondary" />
          </template>
        </Column>
        <Column field="points" header="Pts" class="w-20">
          <template #body="{ data }">
            {{ data.points ?? '—' }}
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { inject, watch } from 'vue'
import useActivityBacklogItems from '@/composables/activityBacklogItems'

const BACKLOG_STATUS_LABELS = {
  0: 'Backlog',
  1: 'En curso',
  2: 'Hecho',
  3: 'Cancelado',
}

const activityId = inject('activityId')
const { backlogItems, isLoading, getBacklogItems } = useActivityBacklogItems()

function backlogPriorityLabel(p) {
  const v = typeof p === 'object' && p !== null ? p.value : p
  if (v == null) return '—'
  return `P${v}`
}

function backlogStatusLabel(row) {
  const s = row?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return '—'
  return BACKLOG_STATUS_LABELS[v] ?? String(v)
}

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getBacklogItems(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
