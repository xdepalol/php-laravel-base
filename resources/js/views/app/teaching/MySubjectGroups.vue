<template>
  <div class="space-y-6">
    <Card>
      <template #title>Mis asignaturas</template>
      <template #subtitle>
        {{ pageSubtitle }}
        <span v-if="yearLabel" class="block mt-1 font-medium text-slate-700">{{ yearLabel }}</span>
      </template>
      <template #content>
        <div v-if="!academicYearId" class="text-slate-500 text-sm py-6 text-center">
          No hay curso académico seleccionado.
        </div>
        <div v-else-if="isLoading" class="flex justify-center py-12 text-blue-600">
          <i class="pi pi-spin pi-spinner text-3xl" aria-hidden="true" />
        </div>
        <div v-else-if="!mySubjectGroups.length" class="text-slate-500 text-sm py-8 text-center">
          {{ emptyMessage }}
        </div>
        <div
          v-else
          class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
          <Card
            v-for="sg in mySubjectGroups"
            :key="sg.id"
            class="subject-group-card border border-slate-200 shadow-sm"
          >
            <template #title>
              <div class="text-lg font-semibold text-slate-800 leading-snug">
                {{ subjectTitle(sg) }}
              </div>
            </template>
            <template #subtitle>
              <div class="flex items-center gap-2 text-slate-600">
                <i class="pi pi-users text-slate-400" />
                <span>{{ groupLabel(sg) }}</span>
              </div>
            </template>
            <template #content>
              <router-link
                :to="{ name: subjectGroupEntryRoute, params: { id: sg.id } }"
                class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700"
              >
                {{ entryLinkLabel }}
                <i class="pi pi-arrow-right text-xs" />
              </router-link>
            </template>
          </Card>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAcademicYearStore } from '@/store/academicYear'
import { authStore } from '@/store/auth'
import useSubjectGroups from '@/composables/subjectGroups'

const academicYearStore = useAcademicYearStore()
const { selectedAcademicYearId, currentAcademicYearLabel } = storeToRefs(academicYearStore)

const { user } = storeToRefs(authStore())

const isTeacher = computed(() => user.value?.roles?.some((r) => r.name === 'teacher'))
const isStudentView = computed(
  () => user.value?.roles?.some((r) => r.name === 'student') && !isTeacher.value
)

const pageSubtitle = computed(() => {
  if (isStudentView.value) {
    return 'Grupos del curso académico seleccionado en la barra superior en los que estás matriculado/a.'
  }
  return 'Grupos de asignatura en los que figuras como profesor para el curso académico seleccionado en la barra superior.'
})

const emptyMessage = computed(() => {
  if (isStudentView.value) {
    return 'No tienes matrículas activas en este curso académico.'
  }
  return 'No tienes grupos de asignatura asignados en este curso académico.'
})

const entryLinkLabel = computed(() => (isStudentView.value ? 'Ver actividades' : 'Gestionar grupo'))

const subjectGroupEntryRoute = computed(() =>
  isStudentView.value ? 'app.subject-group.activities' : 'app.subject-group.overview'
)

const academicYearId = computed(() => selectedAcademicYearId.value)

const yearLabel = computed(() => {
  if (!currentAcademicYearLabel.value) return ''
  return `Curso: ${currentAcademicYearLabel.value}`
})

const { mySubjectGroups, isLoading, getMySubjectGroupsForYear } = useSubjectGroups()

function subjectTitle(sg) {
  return sg.subject?.title || sg.subject?.name || 'Asignatura'
}

function groupLabel(sg) {
  return sg.group?.name || `Grupo #${sg.group_id}`
}

async function load() {
  if (!academicYearId.value) {
    mySubjectGroups.value = []
    return
  }
  await getMySubjectGroupsForYear(academicYearId.value)
}

watch(
  academicYearId,
  () => {
    load()
  },
  { immediate: true }
)
</script>

<style scoped>
.subject-group-card :deep(.p-card-body) {
  padding-top: 0.5rem;
}
</style>
