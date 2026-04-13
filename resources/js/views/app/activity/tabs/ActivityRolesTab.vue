<template>
  <Card>
    <template #title>Roles de actividad</template>
    <template #content>
      <p v-if="filterTypeId" class="text-sm text-slate-600 mb-4">
        Filtrados por el tipo de rol configurado en esta actividad (ID {{ filterTypeId }}).
      </p>
      <DataTable :value="filteredRoles" :loading="isLoading" data-key="id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay roles que mostrar.</span>
        </template>
        <Column field="name" header="Nombre" />
        <Column field="description" header="Descripción">
          <template #body="{ data }">
            <span class="line-clamp-2">{{ data.description || '—' }}</span>
          </template>
        </Column>
        <Column header="Obligatorio" class="w-28">
          <template #body="{ data }">
            {{ data.is_mandatory ? 'Sí' : 'No' }}
          </template>
        </Column>
        <Column field="max_per_team" header="Máx. / equipo" class="w-32" />
        <Column field="position" header="Orden" class="w-24" />
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, watch } from 'vue'
import useActivityRoles from '@/composables/activityRoles'

const activityRef = inject('activity')
const { activityRoles, isLoading, getActivityRoles } = useActivityRoles()

const filterTypeId = computed(() => activityRef?.value?.activity_role_type_id ?? null)

const filteredRoles = computed(() => {
  const list = activityRoles.value || []
  const tid = filterTypeId.value
  if (tid == null) return list
  return list.filter((r) => Number(r.activity_role_type_id) === Number(tid))
})

watch(
  () => activityRef?.value?.id,
  async (id) => {
    if (!id) return
    try {
      await getActivityRoles()
    } catch {
      /* toast en composable */
    }
  },
  { immediate: true }
)
</script>
