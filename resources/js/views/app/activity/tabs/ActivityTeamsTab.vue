<template>
  <Card>
    <template #title>Equipos</template>
    <template #content>
      <DataTable :value="teams" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay equipos en esta actividad.</span>
        </template>
        <Column field="name" header="Nombre" />
        <Column field="id" header="ID" class="w-24" />
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { inject, watch } from 'vue'
import useActivityTeams from '@/composables/activityTeams'

const activityId = inject('activityId')
const { teams, isLoading, getTeams } = useActivityTeams()

watch(
  () => activityId?.value,
  async (id) => {
    if (!id) return
    try {
      await getTeams(id)
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
