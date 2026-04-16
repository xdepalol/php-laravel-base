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
        <div v-if="showStartSprintButton" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
          <Button
            label="Iniciar sprint"
            icon="pi pi-play"
            size="small"
            :loading="sprintBusy"
            :disabled="sprintBusy || !canStartSprintFromEmpty"
            @click="onStartSprintFromTab"
          />
          <p v-if="firstStartableSprintPhase" class="text-slate-600">
            Fase:
            <router-link
              :to="startablePhaseDetailLink"
              class="text-blue-700 font-medium hover:underline"
            >
              {{ firstStartableSprintPhase.title || `Fase #${firstStartableSprintPhase.id}` }}
            </router-link>
          </p>
        </div>
        <p v-else>
          Cuando toque iniciar un sprint (y los anteriores estén cerrados), podrás hacerlo aquí o desde
          <router-link :to="phasesLink" class="text-blue-700 font-medium hover:underline">Fases</router-link>.
        </p>
        <p class="text-xs text-slate-500">
          Calendario y detalle por fase:
          <router-link :to="phasesLink" class="text-blue-700 font-medium hover:underline">Fases</router-link>.
        </p>
      </div>
      <div v-else class="space-y-4">
        <div
          class="rounded-xl border border-sky-200/80 bg-sky-50/80 px-4 py-3 shadow-sm"
        >
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
            <div class="flex flex-wrap items-center gap-2">
              <Tag :value="sprintStatusLabel(activeTeamPhaseTeam?.sprint_status?.value)" severity="info" />
              <Button
                v-if="showSprintAdvanceButton"
                size="small"
                :label="sprintAdvanceButtonLabel(sprintStatusCurrent)"
                :loading="sprintBusy"
                :disabled="!canAdvanceSprintStep || sprintBusy"
                @click="onAdvanceActiveSprint"
              />
            </div>
          </div>
          <div
            v-if="canForceSprintStep"
            class="mt-3 pt-3 border-t border-sky-200/70 space-y-2"
          >
            <p class="text-xs font-medium text-slate-700">Paso del sprint (profesorado)</p>
            <p class="text-xs text-slate-600">
              Podés fijar el sprint en un paso concreto (por ejemplo, retroceder si el equipo avanzó por error).
            </p>
            <div class="flex flex-wrap items-end gap-2">
              <div class="w-full min-w-[12rem] sm:w-64">
                <label class="text-xs text-slate-500 block mb-1">Estado</label>
                <Select
                  v-model="teacherSprintTarget"
                  :options="SPRINT_STATUS_STEP_OPTIONS"
                  option-label="label"
                  option-value="value"
                  class="w-full"
                />
              </div>
              <Button
                label="Establecer paso"
                icon="pi pi-sliders-h"
                size="small"
                outlined
                :loading="sprintBusy"
                :disabled="sprintBusy"
                @click="applyTeacherSprintStep"
              />
            </div>
          </div>
        </div>
        <p class="text-xs text-slate-500">
          Tareas de este equipo enlazadas a la fase.
          <template v-if="canKanbanDrag">
            Usá el asa <i class="pi pi-bars text-slate-400 text-[10px]" aria-hidden="true" /> para arrastrar entre columnas y cambiar el estado.
          </template>
          <template v-else> Podés ver el detalle con el botón de cada tarea.</template>
        </p>
        <div class="grid gap-3 md:grid-cols-3">
          <div
            v-for="meta in kanbanColMeta"
            :key="meta.key"
            :class="meta.panelClass"
            :data-column="meta.key"
          >
            <h3
              class="text-xs font-semibold uppercase tracking-wide mb-2"
              :class="meta.headingClass"
            >
              {{ meta.title }} ({{ columnLists[meta.key].length }})
            </h3>
            <draggable
              v-model="columnLists[meta.key]"
              class="kanban-list min-h-[3rem] space-y-2 list-none p-0 m-0"
              tag="ul"
              item-key="id"
              group="team-sprint-kanban"
              :handle="canKanbanDrag ? '.kanban-drag-handle' : undefined"
              :disabled="!canKanbanDrag"
              :animation="200"
              @change="(e) => onColumnChange(meta.key, e)"
            >
              <template #item="{ element: row }">
                <li
                  class="rounded-md border border-white bg-white px-2 py-2 shadow-sm text-sm flex items-start gap-1 min-w-0"
                  :data-task-id="row.id"
                >
                  <span
                    v-if="canKanbanDrag"
                    class="kanban-drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 shrink-0 mt-0.5"
                    title="Arrastrar"
                  >
                    <i class="pi pi-bars text-xs" aria-hidden="true" />
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="font-medium text-slate-800 break-words">{{ row.title }}</p>
                    <p v-if="assigneeLabel(row)" class="text-xs text-slate-500 mt-1">
                      {{ assigneeLabel(row) }}
                    </p>
                  </div>
                  <Button
                    v-if="canViewTaskDetail"
                    v-tooltip.top="'Ver detalle'"
                    icon="pi pi-eye"
                    text
                    rounded
                    size="small"
                    severity="secondary"
                    class="shrink-0"
                    @click.stop="openTaskDialog(row)"
                  />
                </li>
              </template>
            </draggable>
          </div>
        </div>
      </div>
    </template>
  </Card>

  <Dialog
    v-model:visible="taskDialog.open"
    modal
    header="Tarea del sprint"
    class="w-full max-w-2xl"
    :closable="!taskDialog.saving && !taskDialog.splitting"
    @hide="closeTaskDialog"
  >
    <div v-if="taskDialog.row" class="flex flex-col gap-4 pt-1">
      <div>
        <label class="text-sm font-medium text-slate-700 block mb-1">Título</label>
        <InputText
          v-model="taskForm.title"
          class="w-full"
          maxlength="150"
          :readonly="!canEditTaskDesc"
        />
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700 block mb-1">Descripción</label>
        <Editor
          v-if="canEditTaskDesc"
          v-model="taskForm.description"
          editor-style="min-height: 200px"
          class="w-full"
        />
        <div
          v-else
          class="border border-slate-200 rounded-md bg-slate-50/80 px-3 py-2 text-sm text-slate-700 min-h-[6rem] max-h-64 overflow-y-auto backlog-desc"
          v-html="taskForm.description || '<span class=\'text-slate-400\'>Sin descripción.</span>'"
        />
      </div>
      <div v-if="canEditAssignee || assigneeDisplayLabel" class="w-full max-w-md">
        <label class="text-sm font-medium text-slate-700 block mb-1">Asignada a</label>
        <Select
          v-if="canEditAssignee"
          v-model="taskForm.assigneeUserId"
          :options="memberSelectOptions"
          option-label="label"
          option-value="value"
          placeholder="Sin asignar"
          show-clear
          class="w-full"
        />
        <p v-else class="text-sm text-slate-700">{{ assigneeDisplayLabel }}</p>
      </div>
      <div v-if="canSplitSprintTask" class="border-t border-slate-200 pt-4 mt-2 space-y-3">
        <p class="text-sm font-medium text-slate-800">Dividir en dos partes del sprint</p>
        <p class="text-xs text-slate-600 leading-relaxed">
          Se crea otra tarea en el mismo sprint y el mismo ítem de backlog, en estado «Por hacer» y
          <strong>sin asignar</strong> para que el equipo redistribuya la parte nueva. Completá el título de la nueva
          parte: el botón <strong>Dividir en dos tareas</strong> deja de verse como secundario (mismo estilo que una
          acción principal). <strong>Guardar</strong> solo guarda título, descripción y asignación de <em>esta</em> tarea.
          Las pendientes al cerrar el sprint vuelven al backlog como «Por hacer».
        </p>
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Nueva tarea (segunda parte)</label>
          <InputText
            v-model="sprintSplitForm.titleB"
            class="w-full"
            maxlength="150"
            placeholder="Título de la nueva tarea…"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Renombrar esta tarea (opcional)</label>
          <InputText
            v-model="sprintSplitForm.titleA"
            class="w-full"
            maxlength="150"
            placeholder="Vacío = no cambiar el título actual"
          />
        </div>
      </div>
    </div>
    <template #footer>
      <div class="flex w-full flex-wrap items-center gap-3 justify-between">
        <div class="flex flex-1 flex-wrap gap-2 items-center justify-start min-w-0">
          <Button
            v-if="canSplitSprintTask"
            type="button"
            label="Dividir en dos tareas"
            icon="pi pi-plus-circle"
            :severity="splitDivideButtonSeverity"
            :outlined="splitDivideButtonOutlined"
            :size="splitDivideButtonSize"
            :loading="taskDialog.splitting"
            :disabled="taskDialog.saving || taskDialog.splitting || !String(sprintSplitForm.titleB || '').trim()"
            @click.stop="splitSprintTask"
          />
        </div>
        <div class="flex shrink-0 flex-wrap gap-2 items-center justify-end">
          <Button
            type="button"
            label="Cerrar"
            severity="secondary"
            text
            :disabled="taskDialog.saving || taskDialog.splitting"
            @click="taskDialog.open = false"
          />
          <Button
            v-if="canSaveTaskDialog"
            type="button"
            label="Guardar"
            icon="pi pi-check"
            :loading="taskDialog.saving"
            :disabled="taskDialog.splitting"
            @click.stop="saveTaskDialog"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { computed, inject, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAbility } from '@casl/vue'
import axios from 'axios'
import draggable from 'vuedraggable'
import { useToast } from '@/composables/useToast'
import useActivityPhases from '@/composables/activityPhases'
import useActivityTeams from '@/composables/activityTeams'
import { formatStudentDisplayName } from '@/utils/studentDisplayName'
import {
  nextSprintStatusValue,
  retroCompleteForFinish,
  SPRINT_STATUS_STEP_OPTIONS,
  sprintAdvanceButtonLabel,
  sprintNeverStartedForTeam,
  sprintStatusLabel,
  sprintTeamFinishedOnce,
} from '@/utils/phaseTeamSprint'

const { can } = useAbility()
const toast = useToast()
const canList = computed(() => can('phase-list'))
const showSprintAdvanceButton = computed(() => can('phase-edit') || can('phase-view'))
const canKanbanDrag = computed(() => can('task-edit'))
const canViewTaskDetail = computed(() => can('task-view') || can('task-edit'))
const canEditTaskDesc = computed(() => can('task-edit'))
const canEditAssignee = computed(() => can('phasetask-edit'))
const canForceSprintStep = computed(() => can('phase-sprint-set'))

const route = useRoute()
const activityId = inject('activityId')
const teamId = inject('teamId')
const activityRef = inject('activity', null)
const swal = inject('$swal', null)

const teamActivityPhases = inject('teamActivityPhases', null)
const { phases, refreshPhasesFromApi, patchPhaseTeam } = teamActivityPhases ?? useActivityPhases()
const { getTeamStudentsList } = useActivityTeams()

const loading = ref(false)
const sprintBusy = ref(false)
const suppressColumnSync = ref(false)
const teacherSprintTarget = ref(0)

const kanbanColMeta = [
  {
    key: 'todo',
    title: 'Por hacer',
    panelClass:
      'rounded-lg border border-amber-200/90 bg-amber-50/85 p-3 min-h-[8rem] shadow-sm',
    headingClass: 'text-amber-900/85',
  },
  {
    key: 'doing',
    title: 'En progreso',
    panelClass:
      'rounded-lg border border-violet-200/90 bg-violet-50/80 p-3 min-h-[8rem] shadow-sm',
    headingClass: 'text-violet-900/85',
  },
  {
    key: 'done',
    title: 'Hecha',
    panelClass:
      'rounded-lg border border-emerald-200/90 bg-emerald-50/80 p-3 min-h-[8rem] shadow-sm',
    headingClass: 'text-emerald-900/85',
  },
]

const columnLists = reactive({
  todo: [],
  doing: [],
  done: [],
})

const teamMembers = ref([])
const assigneeInitial = ref(null)

const taskDialog = reactive({
  open: false,
  saving: false,
  splitting: false,
  row: null,
})

const taskForm = ref({
  title: '',
  description: '',
  assigneeUserId: null,
})

const sprintSplitForm = reactive({
  titleA: '',
  titleB: '',
})

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

function sprintStatusValueForTeam(phase) {
  return sprintStatusValue(teamPhaseTeamRow(phase))
}

const canManageSprintFromTab = computed(() => {
  if (!teamId?.value || !activityRef?.value?.has_sprints || !canList.value) return false
  return can('phase-view') || can('phase-edit')
})

function precedingSprintPhasesCompletedForTeam(ps, phaseIndex) {
  const tid = Number(teamId?.value)
  if (!tid || !Array.isArray(ps)) return false
  for (let i = 0; i < phaseIndex; i++) {
    const p = ps[i]
    if (!p?.is_sprint) continue
    const pt = teamPhaseTeamRow(p)
    if (!sprintTeamFinishedOnce(pt)) return false
  }
  return true
}

const firstStartableSprintPhase = computed(() => {
  if (!canManageSprintFromTab.value) return null
  const list = phases.value || []
  const tid = Number(teamId?.value)
  if (!tid) return null
  for (let i = 0; i < list.length; i++) {
    const p = list[i]
    if (!p?.is_sprint) continue
    if (!precedingSprintPhasesCompletedForTeam(list, i)) continue
    const pt = teamPhaseTeamRow(p)
    if (sprintNeverStartedForTeam(pt)) return p
  }
  return null
})

const showStartSprintButton = computed(() => firstStartableSprintPhase.value != null)

const startablePhaseDetailLink = computed(() => {
  const p = firstStartableSprintPhase.value
  if (!p?.id) return phasesLink.value
  return {
    name: 'app.activity.team.phase.show',
    params: {
      activityId: String(activityId?.value),
      teamId: String(teamId?.value),
      phaseId: String(p.id),
    },
    query: { ...tabQuery.value },
  }
})

function canClickAdvanceSprintForPhase(phase) {
  if (!phase) return false
  const cur = sprintStatusValueForTeam(phase)
  if (nextSprintStatusValue(cur) === null) return false
  if (cur === 3 && !retroCompleteForFinish(teamPhaseTeamRow(phase) ?? {})) return false
  return true
}

const canStartSprintFromEmpty = computed(() =>
  canClickAdvanceSprintForPhase(firstStartableSprintPhase.value)
)

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

const sprintStatusCurrent = computed(() => sprintStatusValue(activeTeamPhaseTeam.value))

const canAdvanceSprintStep = computed(() => canClickAdvanceSprintForPhase(activeSprintPhase.value))

watch(
  () => sprintStatusCurrent.value,
  (v) => {
    teacherSprintTarget.value = v
  },
  { immediate: true }
)

function api422FirstMessage(error, fallback) {
  const d = error?.response?.data
  if (d?.errors && typeof d.errors === 'object') {
    const first = Object.values(d.errors)[0]
    if (Array.isArray(first) && first[0]) return String(first[0])
  }
  if (typeof d?.message === 'string') return d.message
  return fallback
}

async function onAdvanceTeamSprint(phase) {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid || !phase?.id) return
  const cur = sprintStatusValueForTeam(phase)
  if (cur === 3 && !retroCompleteForFinish(teamPhaseTeamRow(phase) ?? {})) {
    toast.error(
      'Retrospectiva',
      'Completa qué fue bien, qué mejorar y acciones en el detalle de la fase antes de finalizar.'
    )
    return
  }
  const next = nextSprintStatusValue(cur)
  if (next === null) return
  sprintBusy.value = true
  try {
    await patchPhaseTeam(aid, phase.id, tid, { sprint_status: next })
    toast.success('Sprint', `Estado: ${sprintStatusLabel(next)}`)
    await refreshPhasesFromApi(aid)
  } catch (e) {
    toast.error('Sprint', api422FirstMessage(e, 'No se pudo actualizar el sprint del equipo.'))
  } finally {
    sprintBusy.value = false
  }
}

async function confirmThenAdvanceTeamSprint(phase) {
  if (!phase?.id) return
  const cur = sprintStatusValueForTeam(phase)
  if (cur === 3 && !retroCompleteForFinish(teamPhaseTeamRow(phase) ?? {})) {
    toast.error(
      'Retrospectiva',
      'Completa qué fue bien, qué mejorar y acciones en el detalle de la fase antes de finalizar.'
    )
    return
  }
  const next = nextSprintStatusValue(cur)
  if (next === null) return

  const nextLabel = sprintStatusLabel(next)
  const title = Number(cur) === 4 ? '¿Iniciar sprint?' : '¿Avanzar paso del sprint?'
  const text = `El equipo pasará al estado «${nextLabel}». ¿Deseas continuar?`

  const run = async () => {
    await onAdvanceTeamSprint(phase)
  }

  if (!swal) {
    if (typeof window !== 'undefined' && window.confirm(`${title}\n\n${text}`)) await run()
    return
  }

  const result = await swal({
    icon: 'question',
    title,
    text,
    showCancelButton: true,
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar',
  })
  if (result.isConfirmed) await run()
}

async function onAdvanceActiveSprint() {
  const p = activeSprintPhase.value
  if (p) await confirmThenAdvanceTeamSprint(p)
}

async function onStartSprintFromTab() {
  const p = firstStartableSprintPhase.value
  if (p) await confirmThenAdvanceTeamSprint(p)
}

async function applyTeacherSprintStep() {
  const aid = activityId?.value
  const tid = teamId?.value
  const phase = activeSprintPhase.value
  if (!aid || !tid || !phase?.id || !canForceSprintStep.value) return
  const cur = sprintStatusCurrent.value
  const next = Number(teacherSprintTarget.value)
  if (next === Number(cur)) {
    toast.info('Sprint', 'El equipo ya está en ese paso.')
    return
  }
  const label = sprintStatusLabel(next)
  const run = async () => {
    sprintBusy.value = true
    try {
      await patchPhaseTeam(aid, phase.id, tid, { sprint_status: next })
      toast.success('Sprint', `Estado: ${label}`)
      await refreshPhasesFromApi(aid)
    } catch (e) {
      toast.error('Sprint', api422FirstMessage(e, 'No se pudo actualizar el paso del sprint.'))
    } finally {
      sprintBusy.value = false
    }
  }
  if (!swal) {
    if (typeof window !== 'undefined' && window.confirm(`¿Establecer el sprint en «${label}»?`)) await run()
    return
  }
  const result = await swal({
    icon: 'warning',
    title: '¿Establecer paso del sprint?',
    text: `El equipo pasará a «${label}». Usá esta acción solo cuando corresponda corregir el flujo.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, establecer',
    cancelButtonText: 'Cancelar',
  })
  if (result.isConfirmed) await run()
}

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
    const title = (t.title || `Tarea #${t.id}`).slice(0, 150)
    out.push({
      id: t.id,
      phase_task_id: pt.id,
      phase_task_position: Number(pt.position) || 0,
      backlog_item_id: t.backlog_item_id,
      title,
      description: t.description ?? '',
      position: Number(t.position) || 0,
      status: taskStatusNum(t),
      student: pt.student,
    })
  }
  return out
})

function syncColumnsFromTasks() {
  const todo = []
  const doing = []
  const done = []
  for (const row of tasksForKanban.value) {
    const c = { ...row }
    if (c.status === 1) doing.push(c)
    else if (c.status === 2 || c.status === 3) done.push(c)
    else todo.push(c)
  }
  columnLists.todo = todo
  columnLists.doing = doing
  columnLists.done = done
}

watch(
  () => tasksForKanban.value,
  () => {
    if (suppressColumnSync.value) return
    syncColumnsFromTasks()
  },
  { deep: true, immediate: true }
)

watch(
  () => [activityId?.value, teamId?.value],
  () => {
    teamMembers.value = []
  }
)

function columnKeyToStatus(key) {
  if (key === 'doing') return 1
  if (key === 'done') return 2
  return 0
}

async function persistTaskStatus(row, newStatus) {
  const aid = activityId?.value
  if (!aid || !row?.id) return
  suppressColumnSync.value = true
  try {
    await axios.put(`/api/activities/${aid}/tasks/${row.id}`, {
      backlog_item_id: row.backlog_item_id,
      title: (row.title || '').slice(0, 150),
      description: row.description || null,
      status: newStatus,
      position: row.position ?? 0,
    })
    toast.success('Tarea', 'Estado actualizado')
    await refreshPhasesFromApi(aid)
  } catch (e) {
    toast.error('Tarea', api422FirstMessage(e, 'No se pudo cambiar el estado de la tarea.'))
    await refreshPhasesFromApi(aid)
  } finally {
    suppressColumnSync.value = false
  }
}

function onColumnChange(columnKey, evt) {
  if (!evt.added) return
  const row = evt.added.element
  const newStatus = columnKeyToStatus(columnKey)
  if (Number(row.status) === newStatus) return
  void persistTaskStatus(row, newStatus)
}

function assigneeLabel(taskRow) {
  if (!taskRow.student) return ''
  return formatStudentDisplayName(taskRow.student, taskRow.student?.user_id)
}

const memberSelectOptions = computed(() => {
  const out = []
  for (const m of teamMembers.value || []) {
    const st = m.student
    const value = m.student_id ?? st?.user_id
    if (value == null) continue
    out.push({
      value: Number(value),
      label: formatStudentDisplayName(st, value),
    })
  }
  return out
})

const assigneeDisplayLabel = computed(() => {
  const uid = taskForm.value.assigneeUserId
  if (uid == null) return 'Sin asignar'
  const m = teamMembers.value.find(
    (x) => Number(x.student_id ?? x.student?.user_id) === Number(uid)
  )
  if (m?.student) return formatStudentDisplayName(m.student, uid)
  return `Usuario #${uid}`
})

const canSaveTaskDialog = computed(() => {
  if (!taskDialog.row) return false
  return canEditTaskDesc.value || canEditAssignee.value
})

/** Misma base que el Kanban: `task-edit` basta; el alta de la segunda parte la autoriza el servidor. */
const canSplitSprintTask = computed(
  () =>
    canKanbanDrag.value &&
    !!activeSprintPhase.value?.id &&
    !!taskDialog.row?.phase_task_id
)

/** Hay título para la segunda parte: quitar estilo secundario del botón Dividir. */
const splitSecondTitleEntered = computed(
  () => canSplitSprintTask.value && !!String(sprintSplitForm.titleB || '').trim()
)

const splitDivideButtonSeverity = computed(() => (splitSecondTitleEntered.value ? undefined : 'secondary'))
const splitDivideButtonOutlined = computed(() => !splitSecondTitleEntered.value)
const splitDivideButtonSize = computed(() => (splitSecondTitleEntered.value ? undefined : 'small'))

async function ensureTeamMembers() {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid) return
  if (teamMembers.value.length) return
  try {
    teamMembers.value = await getTeamStudentsList(aid, tid)
  } catch {
    teamMembers.value = []
  }
}

async function openTaskDialog(row) {
  taskDialog.row = row
  taskForm.value = {
    title: row.title || '',
    description: row.description ?? '',
    assigneeUserId: row.student?.user_id ?? null,
  }
  sprintSplitForm.titleA = ''
  sprintSplitForm.titleB = ''
  assigneeInitial.value = row.student?.user_id ?? null
  if (canEditAssignee.value) await ensureTeamMembers()
  taskDialog.open = true
}

function closeTaskDialog() {
  taskDialog.saving = false
  taskDialog.splitting = false
  taskDialog.row = null
  sprintSplitForm.titleA = ''
  sprintSplitForm.titleB = ''
}

async function saveTaskDialog() {
  const aid = activityId?.value
  const phase = activeSprintPhase.value
  const row = taskDialog.row
  if (!aid || !row?.id || !phase?.id) return
  const latest = tasksForKanban.value.find((t) => Number(t.id) === Number(row.id))
  const statusForPut = latest ? taskStatusNum(latest) : row.status
  taskDialog.saving = true
  try {
    if (canEditTaskDesc.value) {
      await axios.put(`/api/activities/${aid}/tasks/${row.id}`, {
        backlog_item_id: row.backlog_item_id,
        title: taskForm.value.title.trim().slice(0, 150),
        description: taskForm.value.description || null,
        status: statusForPut,
        position: latest?.position ?? row.position ?? 0,
      })
    }
    if (canEditAssignee.value) {
      const nextAssignee = taskForm.value.assigneeUserId ?? null
      if (nextAssignee !== assigneeInitial.value) {
        await axios.put(`/api/phase-tasks/${row.phase_task_id}`, {
          phase_id: phase.id,
          task_id: row.id,
          student_id: nextAssignee,
          position: latest?.phase_task_position ?? row.phase_task_position ?? 0,
        })
      }
    }
    toast.success('Tarea', 'Cambios guardados')
    await refreshPhasesFromApi(aid)
    taskDialog.open = false
  } catch (e) {
    toast.error('Tarea', api422FirstMessage(e, 'No se pudo guardar.'))
  } finally {
    taskDialog.saving = false
  }
}

async function splitSprintTask() {
  const aid = activityId?.value
  const tid = teamId?.value
  const phase = activeSprintPhase.value
  const row = taskDialog.row
  const titleB = String(sprintSplitForm.titleB || '').trim()
  const titleA = String(sprintSplitForm.titleA || '').trim()
  if (!aid || !tid || !phase?.id || !row?.id || !titleB) return
  taskDialog.splitting = true
  try {
    await axios.post(
      `/api/activities/${aid}/phases/${phase.id}/teams/${tid}/sprint-tasks/${row.id}/split`,
      {
        title_part_a: titleA || null,
        title_part_b: titleB,
      }
    )
    sprintSplitForm.titleA = ''
    sprintSplitForm.titleB = ''
    toast.success('Tarea', 'Se dividió en dos tareas del sprint.')
    await refreshPhasesFromApi(aid)
    taskDialog.open = false
  } catch (e) {
    toast.error('Tarea', api422FirstMessage(e, 'No se pudo dividir la tarea.'))
  } finally {
    taskDialog.splitting = false
  }
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

<style scoped>
.backlog-desc :deep(p) {
  margin: 0.25rem 0;
}
</style>
