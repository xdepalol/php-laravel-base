<template>
  <Card>
    <template #title>Fases</template>
    <template #content>
      <DataTable :value="phases" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay fases definidas.</span>
        </template>
        <Column field="title" header="Título" />
        <Column header="Sprint" class="w-28">
          <template #body="{ data }">
            <Tag v-if="data.is_sprint" value="Sí" severity="info" />
            <span v-else class="text-slate-500">No</span>
          </template>
        </Column>
        <Column header="Inicio" class="whitespace-nowrap w-32">
          <template #body="{ data }">
            {{ data.start_date || '—' }}
          </template>
        </Column>
        <Column header="Fin" class="whitespace-nowrap w-32">
          <template #body="{ data }">
            {{ data.end_date || '—' }}
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { inject, watch } from 'vue'
import useActivityPhases from '@/composables/activityPhases'

const activityId = inject('activityId')
const { phases, isLoading, getPhases } = useActivityPhases()

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getPhases(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
