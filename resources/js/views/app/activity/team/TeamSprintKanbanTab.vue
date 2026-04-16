<template>
  <Card>
    <template #title>Sprint — tablero</template>
    <template #content>
      <p v-if="!canList" class="text-sm text-amber-800">
        No tienes permiso para ver las fases de esta actividad.
      </p>
      <div v-else-if="loading" class="flex justify-center py-12 text-slate-500">
        <i class="pi pi-spin pi-spinner text-2xl" aria-hidden="true" />
      </div>
      <div v-else-if="!activeSprintPhase" class="space-y-3 text-sm text-slate-600">
        <p>
          No hay ningún sprint activo para este equipo (asignación, desarrollo, revisión o retrospectiva).
        </p>
        <p>
          Iniciá o avanzá el sprint desde
          <router-link :to="phasesLink" class="text-blue-700 font-medium hover:underline">Fases</router-link>.
        </p>
      </div>
      <div v-else class="space-y-4">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
          <p class="text-sm text-slate-700">
            <span class="text-slate-500">Sprint en curso:</span>
            <router-link
              :to="phaseDetailLink"
              class="font-medium text-blue-700 hover:underline"
            >
              {{ activeSprintPhase.title || `Fase #${activeSprintPhase.id}` }}
            </router-link>
          </p>
          <Tag :value="sprintStatusLabel(activeTeamPhaseTeam?.sprint_status?.value)" severity="info" />
        </div>
        <p class="text-xs text-slate-500">
          Tareas de este equipo enlazadas a la fase (solo lectura). Para mover tareas según las reglas del
          curso usá el backlog o las herramientas que habilite el profesorado.
        </p>
        <div class="grid gap-3 md:grid-cols-3">
          <div
            v-for="col in kanbanColumns"
            :key="col.key"
            class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 min-h-[8rem]"
          >
            <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
              {{ col.title }} ({{ col.tasks.length }})
            </h3>
            <div v-if="!col.tasks.length" class="text-xs text-slate-400 py-2">Vacío</div>
            <ul v-else class="space-y-2">
              <li
                v-for="t in col.tasks"
                :key="t.id"
                class="rounded-md border border-white bg-white px-2 py-2 shadow-sm text-sm"
              >
                <p class="font-medium text-slate-800 break-words">{{ t.title }}</p>
                <p v-if="assigneeLabel(t)" class="text-xs text-slate-500 mt-1">
                  {{ assigneeLabel(t) }}
                </p>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAbility } from '@casl/vue'
import useActivityPhases from '@/composables/activityPhases'
import { formatStudentDisplayName } from '@/utils/studentDisplayName'
import { sprintStatusLabel } from '@/utils/phaseTeamSprint'

const { can } = useAbility()
const canList = computed(() => can('phase-list'))

const route = useRoute()
const activityId = inject('activityId')
const teamId = inject('teamId')

const teamActivityPhases = inject('teamActivityPhases', null)
const { phases, refreshPhasesFromApi } = teamActivityPhases ?? useActivityPhases()
const loading = ref(false)

const tabQuery = computed(() => {
  const raw = route.query.fromSubjectGroup
  if (raw == null || raw === '') return {}
  return { fromSubjectGroup: String(raw) }
})

const phasesLink = computed(() => ({
  name: 'app.activity.team.phases',
  params: {
    activityId: String(activityId?.value),
    teamId: String(teamId?.value),
  },
  query: { ...tabQuery.value },
}))

function teamPhaseTeamRow(phase) {
  const tid = Number(teamId?.value)
  if (!tid || !phase?.phase_teams?.length) return null
  return phase.phase_teams.find((p) => Number(p.team_id) === tid) ?? null
}

function sprintStatusValue(pt) {
  const v = pt?.sprint_status?.value
  return v !== undefined && v !== null ? Number(v) : 4
}

function isActiveSprintTeamStatus(pt) {
  const v = sprintStatusValue(pt)
  return v >= 0 && v <= 3
}

const activeSprintPhase = computed(() => {
  const list = phases.value || []
  for (const p of list) {
    if (!p?.is_sprint) continue
    const pt = teamPhaseTeamRow(p)
    if (isActiveSprintTeamStatus(pt)) {
      return p
    }
  }
  return null
})

const activeTeamPhaseTeam = computed(() =>
  activeSprintPhase.value ? teamPhaseTeamRow(activeSprintPhase.value) : null
)

const phaseDetailLink = computed(() => ({
  name: 'app.activity.team.phase.show',
  params: {
    activityId: String(activityId?.value),
    teamId: String(teamId?.value),
    phaseId: String(activeSprintPhase.value?.id ?? ''),
  },
  query: { ...tabQuery.value },
}))

function taskBelongsToTeam(ptRow, tid) {
  const team = ptRow?.task?.backlog_item?.team_id ?? ptRow?.task?.backlog_item?.team?.id
  return Number(team) === tid
}

function taskStatusNum(task) {
  const s = task?.status
  if (s == null) return 0
  return typeof s === 'object' ? Number(s.value) : Number(s)
}

const teamPhaseTasks = computed(() => {
  const phase = activeSprintPhase.value
  const tid = Number(teamId?.value)
  if (!phase?.phase_tasks?.length || !tid) return []
  return phase.phase_tasks.filter((pt) => {
    const t = pt.task
    if (!t) return false
    return taskBelongsToTeam(pt, tid)
  })
})

const tasksForKanban = computed(() => {
  const out = []
  for (const pt of teamPhaseTasks.value) {
    const t = pt.task
    if (!t) continue
    out.push({
      id: t.id,
      phase_task_id: pt.id,
      title: t.title || `Tarea #${t.id}`,
      status: taskStatusNum(t),
      student: pt.student,
    })
  }
  return out
})

const kanbanColumns = computed(() => {
  const todo = []
  const doing = []
  const done = []
  for (const t of tasksForKanban.value) {
    if (t.status === 1) doing.push(t)
    else if (t.status === 2 || t.status === 3) done.push(t)
    else todo.push(t)
  }
  return [
    { key: 'todo', title: 'Por hacer', tasks: todo },
    { key: 'doing', title: 'En progreso', tasks: doing },
    { key: 'done', title: 'Hecha', tasks: done },
  ]
})

function assigneeLabel(taskRow) {
  if (!taskRow.student) return ''
  return formatStudentDisplayName(taskRow.student, taskRow.student?.id)
}

async function load() {
  const aid = activityId?.value
  if (!aid || !canList.value) return
  loading.value = true
  try {
    await refreshPhasesFromApi(aid)
  } finally {
    loading.value = false
  }
}

watch(
  () => [activityId?.value, teamId?.value, canList.value],
  () => load(),
  { immediate: true }
)
</script>
