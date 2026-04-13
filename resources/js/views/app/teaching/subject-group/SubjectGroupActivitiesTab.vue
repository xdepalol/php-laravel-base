<template>
  <Card>
    <template #title>Actividades</template>
    <template #content>
      <DataTable
        :value="listings.groupActivities"
        :loading="listings.activitiesLoading"
        data-key="id"
        striped-rows
        class="text-sm"
      >
        <template #empty>
          <span class="text-slate-500">No hay actividades asociadas a este grupo.</span>
        </template>
        <Column field="title" header="Título">
          <template #body="{ data }">
            <router-link
              :to="{
                name: 'app.activity.overview',
                params: { activityId: data.id },
                query: { fromSubjectGroup: String(groupId) },
              }"
              class="text-blue-700 hover:underline font-medium"
            >
              {{ data.title || `Actividad #${data.id}` }}
            </router-link>
          </template>
        </Column>
        <Column header="Estado" class="w-36">
          <template #body="{ data }">
            <Tag
              v-if="data.status"
              :value="data.status.name || data.status.label || '—'"
              severity="secondary"
            />
            <span v-else>—</span>
          </template>
        </Column>
        <Column header="Tipo" class="w-44">
          <template #body="{ data }">
            {{ data.type?.label || data.type?.name || '—' }}
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject } from 'vue'

const groupIdRef = inject('subjectGroupId')
const listings = inject('subjectGroupListings')

const groupId = computed(() => groupIdRef?.value ?? '')
</script>
