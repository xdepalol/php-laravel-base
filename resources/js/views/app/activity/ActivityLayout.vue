<template>
  <div class="activity-workspace space-y-6">
    <div class="flex flex-wrap items-center gap-3">
      <router-link
        v-if="backToSubjectGroupId"
        :to="{ name: 'app.subject-group.overview', params: { id: backToSubjectGroupId } }"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Volver al grupo
      </router-link>
      <router-link
        v-else
        to="/app/mis-asignaturas"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Mis asignaturas
      </router-link>
    </div>

    <Card v-if="loadError">
      <template #content>
        <p class="text-slate-600">No se pudo cargar la actividad o no tienes permiso para verla.</p>
      </template>
    </Card>

    <template v-else>
      <div v-if="isLoading && !activity.id" class="flex justify-center py-16 text-blue-600">
        <i class="pi pi-spin pi-spinner text-3xl" aria-hidden="true" />
      </div>

      <template v-else-if="activity.id">
        <div class="border-b border-slate-200 pb-1">
          <h1 class="text-xl font-semibold text-slate-900 tracking-tight">
            {{ activity.title || 'Actividad' }}
          </h1>
          <p v-if="statusLabel" class="text-sm text-slate-500 mt-0.5">{{ statusLabel }}</p>
        </div>

        <Tabs
          :value="activeTabKey"
          scrollable
          class="activity-tabs w-full"
          @update:value="onTabChange"
        >
          <TabList>
            <Tab
              v-for="tab in tabs"
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
import { computed, provide, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import useActivities from '@/composables/activities'

const route = useRoute()
const router = useRouter()

const routeActivityId = computed(() => Number(route.params.activityId))

const backToSubjectGroupId = computed(() => {
  const q = route.query.fromSubjectGroup
  const n = Number(q)
  return Number.isFinite(n) && n > 0 ? n : null
})

/** Keep ?fromSubjectGroup= when switching tabs */
const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const { activity, isLoading, getActivity } = useActivities()
const loadError = ref(false)

const statusLabel = computed(() => {
  const s = activity.value.status
  if (s == null) return ''
  if (typeof s === 'object') return s.name || ''
  return String(s)
})

const tabs = [
  { tabKey: 'overview', name: 'app.activity.overview', label: 'Resumen', icon: 'pi pi-info-circle' },
  { tabKey: 'teams', name: 'app.activity.teams', label: 'Equipos', icon: 'pi pi-users' },
  { tabKey: 'deliverables', name: 'app.activity.deliverables', label: 'Entregables', icon: 'pi pi-inbox' },
  { tabKey: 'phases', name: 'app.activity.phases', label: 'Fases', icon: 'pi pi-list' },
  { tabKey: 'backlog', name: 'app.activity.backlog', label: 'Backlog', icon: 'pi pi-table' },
  { tabKey: 'tasks', name: 'app.activity.tasks', label: 'Tareas', icon: 'pi pi-check-square' },
  { tabKey: 'roles', name: 'app.activity.roles', label: 'Roles', icon: 'pi pi-id-card' },
]

const routeNameToTabKey = Object.fromEntries(tabs.map((t) => [t.name, t.tabKey]))

const activeTabKey = ref(tabs[0].tabKey)

function resolveTabKeyFromRoute() {
  const metaKey = route.meta?.activityTab
  if (metaKey && tabs.some((t) => t.tabKey === metaKey)) return metaKey
  return routeNameToTabKey[route.name] ?? tabs[0].tabKey
}

watch(
  () => [route.name, route.meta?.activityTab],
  () => {
    activeTabKey.value = resolveTabKeyFromRoute()
  },
  { immediate: true }
)

function onTabChange(nextKey) {
  if (!nextKey || nextKey === activeTabKey.value) return
  const tab = tabs.find((t) => t.tabKey === nextKey)
  if (!tab) return
  router.push({
    name: tab.name,
    params: { activityId: routeActivityId.value },
    query: { ...tabQuery.value },
  })
}

provide(
  'activityId',
  computed(() => routeActivityId.value)
)
provide('activity', activity)

async function loadActivity() {
  const id = routeActivityId.value
  if (!id) return
  loadError.value = false
  try {
    await getActivity(id)
  } catch {
    loadError.value = true
  }
}

watch(
  routeActivityId,
  () => {
    loadActivity()
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
