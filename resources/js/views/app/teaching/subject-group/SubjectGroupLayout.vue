<template>
  <div class="activity-workspace space-y-6">
    <div class="flex flex-wrap items-center gap-3">
      <router-link
        to="/app/mis-asignaturas"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Mis asignaturas
      </router-link>
    </div>

    <Card v-if="loadError">
      <template #content>
        <p class="text-slate-600">No se pudo cargar el grupo o no tienes permiso para verlo.</p>
      </template>
    </Card>

    <template v-else>
      <div v-if="isLoading && !subjectGroup.id" class="flex justify-center py-16 text-blue-600">
        <i class="pi pi-spin pi-spinner text-3xl" aria-hidden="true" />
      </div>

      <template v-else-if="subjectGroup.id">
        <div class="border-b border-slate-200 pb-1">
          <h1 class="text-xl font-semibold text-slate-900 tracking-tight">
            {{ headerTitle }}
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">{{ headerSubtitle }}</p>
        </div>

        <Tabs
          :value="activeTabKey"
          scrollable
          class="activity-tabs w-full"
          @update:value="onTabChange"
        >
          <TabList>
            <Tab
              v-for="tab in visibleTabs"
              :key="tab.tabKey"
              :value="tab.tabKey"
              class="text-sm font-medium"
            >
              <span class="inline-flex items-center gap-2">
                <i :class="tab.icon" class="text-base opacity-80" aria-hidden="true" />
                {{ tab.label }}
              </span>
            </Tab>
          </TabList>
        </Tabs>

        <div class="activity-tab-panel pt-4">
          <router-view />
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { computed, provide, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authStore } from '@/store/auth'
import useSubjectGroups from '@/composables/subjectGroups'
import useSubjectGroupEnrollments from '@/composables/subjectGroupEnrollments'
import useActivities from '@/composables/activities'

const route = useRoute()
const router = useRouter()

const groupId = computed(() => Number(route.params.id))

const { subjectGroup, isLoading, getSubjectGroup } = useSubjectGroups()
const enrollmentsApi = useSubjectGroupEnrollments()
const { enrollments, isLoading: enrollmentsLoading, getEnrollments } = enrollmentsApi

const isStudentOnlyView = computed(() => {
  const roles = authStore().user?.roles?.map((r) => r.name) ?? []
  return roles.includes('student') && !roles.includes('teacher')
})
const {
  activities: groupActivities,
  isLoading: activitiesLoading,
  getActivitiesForSubjectGroup,
} = useActivities()

const loadError = ref(false)

const headerTitle = computed(() => {
  const s = subjectGroup.value.subject
  return s?.title || s?.name || 'Grupo de asignatura'
})

const headerSubtitle = computed(() => {
  const g = subjectGroup.value.group
  return g?.name || `Grupo #${subjectGroup.value.group_id}`
})

const allSubjectGroupTabs = [
  { tabKey: 'overview', name: 'app.subject-group.overview', label: 'Resumen', icon: 'pi pi-info-circle' },
  { tabKey: 'students', name: 'app.subject-group.students', label: 'Estudiantes', icon: 'pi pi-users' },
  { tabKey: 'activities', name: 'app.subject-group.activities', label: 'Actividades', icon: 'pi pi-inbox' },
]

const visibleTabs = computed(() => {
  if (isStudentOnlyView.value) {
    return allSubjectGroupTabs.filter((t) => t.tabKey !== 'students')
  }
  return allSubjectGroupTabs
})

const routeNameToTabKey = computed(() =>
  Object.fromEntries(visibleTabs.value.map((t) => [t.name, t.tabKey]))
)

const activeTabKey = ref(allSubjectGroupTabs[0].tabKey)

function resolveTabKeyFromRoute() {
  const metaKey = route.meta?.subjectGroupTab
  if (metaKey && visibleTabs.value.some((t) => t.tabKey === metaKey)) {
    return metaKey
  }
  return routeNameToTabKey.value[route.name] ?? visibleTabs.value[0]?.tabKey ?? 'overview'
}

watch(
  () => [route.name, route.meta?.subjectGroupTab, visibleTabs.value],
  () => {
    activeTabKey.value = resolveTabKeyFromRoute()
  },
  { immediate: true }
)

function onTabChange(nextKey) {
  if (!nextKey || nextKey === activeTabKey.value) return
  const tab = visibleTabs.value.find((t) => t.tabKey === nextKey)
  if (!tab) return
  router.push({
    name: tab.name,
    params: { id: String(groupId.value) },
  })
}

watch(
  () => [route.name, isStudentOnlyView.value, groupId.value],
  () => {
    if (isStudentOnlyView.value && route.name === 'app.subject-group.students') {
      router.replace({
        name: 'app.subject-group.activities',
        params: { id: String(groupId.value) },
      })
    }
  },
  { immediate: true }
)

provide('subjectGroupId', groupId)
provide('subjectGroup', subjectGroup)
provide(
  'subjectGroupListings',
  reactive({
    enrollments,
    enrollmentsLoading,
    groupActivities,
    activitiesLoading,
  })
)
provide('reloadSubjectGroupActivities', async () => {
  const id = groupId.value
  if (!id) return
  await getActivitiesForSubjectGroup(id)
})

async function loadGroup() {
  const id = groupId.value
  if (!id) return
  loadError.value = false
  try {
    await getSubjectGroup(id)
    if (!isStudentOnlyView.value) {
      await getEnrollments(id)
    } else {
      enrollments.value = []
    }
    await getActivitiesForSubjectGroup(id)
  } catch {
    loadError.value = true
  }
}

watch(
  () => [groupId.value, isStudentOnlyView.value],
  () => {
    loadGroup()
  },
  { immediate: true }
)
</script>

<style scoped>
.activity-workspace :deep(.p-card) {
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
}

.activity-workspace :deep(.activity-tabs .p-tablist-tab-list) {
  flex-wrap: wrap;
}
</style>
