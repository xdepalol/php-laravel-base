<template>
  <Card>
    <template #title>Entregables</template>
    <template #content>
      <DataTable :value="deliverables" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay entregables.</span>
        </template>
        <Column field="title" header="Título" />
        <Column field="due_date" header="Entrega" class="whitespace-nowrap">
          <template #body="{ data }">
            {{ data.due_date || '—' }}
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag :value="deliverableStatusLabel(data)" severity="secondary" />
          </template>
        </Column>
        <Column header="Ámbito" class="w-32">
          <template #body="{ data }">
            {{ data.is_group_deliverable ? 'Grupo' : 'Individual' }}
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { inject, watch } from 'vue'
import useActivityDeliverables from '@/composables/activityDeliverables'

const DELIVERABLE_STATUS_LABELS = {
  0: 'Borrador',
  1: 'Publicado',
  2: 'Cerrado',
}

const activityId = inject('activityId')
const { deliverables, isLoading, getDeliverables } = useActivityDeliverables()

function deliverableStatusLabel(row) {
  const s = row?.status
  const v = typeof s === 'object' && s !== null ? s.value : s
  if (v == null) return '—'
  return DELIVERABLE_STATUS_LABELS[v] ?? String(v)
}

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getDeliverables(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
