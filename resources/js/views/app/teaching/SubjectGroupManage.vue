<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center gap-3">
      <router-link
        to="/app/mis-asignaturas"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Volver a mis asignaturas
      </router-link>
    </div>

    <Card v-if="isLoading && !subjectGroup.id">
      <template #content>
        <div class="flex justify-center py-12 text-blue-600">
          <i class="pi pi-spin pi-spinner text-3xl" aria-hidden="true" />
        </div>
      </template>
    </Card>

    <Card v-else-if="subjectGroup.id">
      <template #title>
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <span>{{ headerTitle }}</span>
        </div>
      </template>
      <template #subtitle>{{ headerSubtitle }}</template>
      <template #content>
        <p class="text-sm text-slate-600 mb-6">
          Datos del grupo de asignatura y listado de matrículas.
        </p>

        <h3 class="text-sm font-semibold text-slate-800 mb-3">Matrículas</h3>
        <DataTable
          :value="enrollments"
          :loading="enrollmentsLoading"
          data-key="id"
          striped-rows
          class="text-sm"
        >
          <template #empty>
            <span class="text-slate-500">No hay matrículas en este grupo.</span>
          </template>
          <Column field="student.user.name" header="Estudiante">
            <template #body="{ data }">
              {{ studentDisplayName(data) }}
            </template>
          </Column>
          <Column field="status.label" header="Estado">
            <template #body="{ data }">
              <Tag
                v-if="data.status"
                :value="data.status.label || data.status.name || '—'"
                severity="secondary"
              />
              <span v-else>—</span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Card v-else>
      <template #content>
        <p class="text-slate-600">No se encontró el grupo de asignatura.</p>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import useSubjectGroups from '@/composables/subjectGroups'
import useSubjectGroupEnrollments from '@/composables/subjectGroupEnrollments'

const route = useRoute()
const groupId = computed(() => Number(route.params.id))

const { subjectGroup, isLoading, getSubjectGroup } = useSubjectGroups()
const enrollmentsApi = useSubjectGroupEnrollments()
const { enrollments, isLoading: enrollmentsLoading, getEnrollments } = enrollmentsApi

const headerTitle = computed(() => {
  const s = subjectGroup.value.subject
  return s?.title || s?.name || 'Grupo de asignatura'
})

const headerSubtitle = computed(() => {
  const g = subjectGroup.value.group
  const gn = g?.name || `Grupo #${subjectGroup.value.group_id}`
  return gn
})

function studentDisplayName(row) {
  const u = row.student?.user
  if (!u) return '—'
  const parts = [u.name, u.surname1, u.surname2].filter(Boolean)
  return parts.length ? parts.join(' ') : u.email || '—'
}

async function loadGroup() {
  const id = groupId.value
  if (!id) return
  try {
    await getSubjectGroup(id)
    await getEnrollments(id)
  } catch {
    /* toasts en composables */
  }
}

onMounted(loadGroup)

watch(groupId, () => {
  loadGroup()
})
</script>
