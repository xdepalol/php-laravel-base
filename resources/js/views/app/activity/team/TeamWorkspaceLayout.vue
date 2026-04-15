<template>
  <div class="activity-workspace space-y-6">
    <div class="flex flex-wrap items-center gap-3">
      <router-link
        :to="{ name: 'app.activity.teams', params: { activityId: routeActivityId }, query: { ...tabQuery } }"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
      >
        <i class="pi pi-arrow-left" />
        Equipos de la actividad
      </router-link>
    </div>

    <Card v-if="loadError">
      <template #content>
        <p class="text-slate-600">No se pudo cargar el equipo o la actividad.</p>
      </template>
    </Card>

    <template v-else>
      <div v-if="isBootstrapping" class="flex justify-center py-16 text-blue-600">
        <i class="pi pi-spin pi-spinner text-3xl" aria-hidden="true" />
      </div>

      <template v-else-if="team.id && activity.id">
        <div class="border-b border-slate-200 pb-1">
          <h1 class="text-xl font-semibold text-slate-900 tracking-tight">
            {{ team.name || 'Equipo' }}
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">
            {{ activity.title || 'Actividad' }}
            <span v-if="activity.has_backlog" class="text-slate-400"> · Backlog habilitado</span>
          </p>
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
import { computed, provide, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import useActivities from '@/composables/activities'
import useActivityTeams from '@/composables/activityTeams'

const route = useRoute()
const router = useRouter()

const routeActivityId = computed(() => Number(route.params.activityId))
const routeTeamId = computed(() => Number(route.params.teamId))

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const { activity, getActivity, isLoading: activityLoading } = useActivities()
const { team, getTeam, isLoading: teamLoading } = useActivityTeams()

const loadError = ref(false)

const isBootstrapping = computed(
  () => (activityLoading.value || teamLoading.value) && (!team.value?.id || !activity.value?.id)
)

const allTabs = [
  { tabKey: 'overview', name: 'app.activity.team.overview', label: 'Resumen', icon: 'pi pi-info-circle' },
  {
    tabKey: 'phases',
    name: 'app.activity.team.phases',
    label: 'Fases',
    icon: 'pi pi-list',
    needsSprints: true,
  },
  { tabKey: 'backlog', name: 'app.activity.team.backlog', label: 'Backlog', icon: 'pi pi-table', needsBacklog: true },
  { tabKey: 'tasks', name: 'app.activity.team.tasks', label: 'Tareas', icon: 'pi pi-check-square', needsBacklog: true },
  {
    tabKey: 'deliverables',
    name: 'app.activity.team.deliverables',
    label: 'Entregas',
    icon: 'pi pi-inbox',
  },
]

const visibleTabs = computed(() => {
  const hb = !!activity.value?.has_backlog
  const sp = !!activity.value?.has_sprints
  return allTabs.filter((t) => {
    if (t.needsBacklog && !hb) return false
    if (t.needsSprints && !sp) return false
    return true
  })
})

const routeNameToTabKey = computed(() => ({
  ...Object.fromEntries(allTabs.map((t) => [t.name, t.tabKey])),
  'app.activity.team.phase.show': 'phases',
}))

const activeTabKey = ref(allTabs[0].tabKey)

function resolveTabKeyFromRoute() {
  const metaKey = route.meta?.teamTab
  if (metaKey && allTabs.some((t) => t.tabKey === metaKey)) {
    const tab = allTabs.find((t) => t.tabKey === metaKey)
    if (tab?.needsBacklog && !activity.value?.has_backlog) return allTabs[0].tabKey
    if (tab?.needsSprints && !activity.value?.has_sprints) return allTabs[0].tabKey
    return metaKey
  }
  return routeNameToTabKey.value[route.name] ?? allTabs[0].tabKey
}

watch(
  () => [
    route.name,
    route.meta?.teamTab,
    activity.value?.has_backlog,
    activity.value?.has_sprints,
    activity.value?.id,
  ],
  () => {
    if (
      activity.value?.id &&
      !activity.value?.has_backlog &&
      (route.name === 'app.activity.team.backlog' || route.name === 'app.activity.team.tasks')
    ) {
      router.replace({
        name: 'app.activity.team.overview',
        params: { activityId: String(routeActivityId.value), teamId: String(routeTeamId.value) },
        query: { ...tabQuery.value },
      })
      return
    }
    if (
      activity.value?.id &&
      !activity.value?.has_sprints &&
      (route.name === 'app.activity.team.phases' || route.name === 'app.activity.team.phase.show')
    ) {
      router.replace({
        name: 'app.activity.team.overview',
        params: { activityId: String(routeActivityId.value), teamId: String(routeTeamId.value) },
        query: { ...tabQuery.value },
      })
      return
    }
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
    params: { activityId: String(routeActivityId.value), teamId: String(routeTeamId.value) },
    query: { ...tabQuery.value },
  })
}

provide(
  'activityId',
  computed(() => routeActivityId.value)
)
provide('activity', activity)
provide(
  'teamId',
  computed(() => routeTeamId.value)
)
provide('team', team)

async function bootstrap() {
  const aid = routeActivityId.value
  const tid = routeTeamId.value
  if (!aid || !tid) return
  loadError.value = false
  try {
    await Promise.all([getActivity(aid), getTeam(aid, tid)])
  } catch {
    loadError.value = true
  }
}

watch([routeActivityId, routeTeamId], bootstrap, { immediate: true })

async function reloadTeam() {
  const aid = routeActivityId.value
  const tid = routeTeamId.value
  if (!aid || !tid) return
  await getTeam(aid, tid)
}

provide('reloadTeam', reloadTeam)
</script>

<style scoped>
.activity-workspace :deep(.p-card) {
  box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
}

.activity-workspace :deep(.activity-tabs .p-tablist-tab-list) {
  flex-wrap: wrap;
}
</style>
